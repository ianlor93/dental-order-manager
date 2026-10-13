<?php

namespace App\Console\Commands;

use App\Mail\EnamelNotification;
use App\Models\Order;
use App\Models\MdrDocumentHash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Log, Storage, Mail};

/**
 * mdr:verify-integrity
 *
 * Verifica l'integrità crittografica dei file negli ordini archiviati (is_locked=true).
 *
 * Per ogni ordine archiviato:
 *   1. Recupera i file su ARCH_L
 *   2. Ricalcola SHA-256
 *   3. Confronta con l'hash salvato nel DB (OrderFile.hash_sha256)
 *   4. Registra il risultato nel report
 *
 * Il report viene scritto su ARCH_L come:
 *   INTEGRITY_REPORT_{YYYY-MM}.txt
 *
 * Se vengono rilevate anomalie, invia email al PRRC.
 *
 * MDR Art. 10(8): i documenti tecnici devono essere conservati integri per 10-15 anni.
 * ISO 13485 §4.2.5: i registri devono essere leggibili, identificabili e recuperabili.
 *
 * Schedulato mensilmente in bootstrap/app.php.
 * Eseguibile manualmente: php artisan mdr:verify-integrity
 * Dry-run (solo output, nessun file scritto): php artisan mdr:verify-integrity --dry-run
 */
class VerifyArchiveIntegrity extends Command
{
    protected $signature   = 'mdr:verify-integrity {--dry-run : Esegui senza scrivere il report su ARCH_L}';
    protected $description = 'Verifica integrità SHA-256 dei file archiviati (MDR Art. 10.8) e genera report mensile';

    private int $checked   = 0;
    private int $ok        = 0;
    private int $tampered  = 0;
    private int $missing   = 0;
    private int $noHash    = 0;
    // Contatori dedicati ai PDF MDR verificati contro il ledger MdrDocumentHash (append-only)
    private int $mdrChecked = 0;
    private int $mdrOk      = 0;
    private int $mdrTampered= 0;
    private int $mdrMissing = 0;
    private int $mdrNoLedger= 0;
    private int $mdrRegenChain = 0; // file con catena >1 (rigenerazioni CAPA/manual legittime)
    private array $anomalies = [];

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $month   = now()->format('Y-m');
        $startAt = now();

        $this->info("MDR Integrity Verification — {$month}");
        $this->info($dryRun ? '(DRY-RUN: nessun file verrà scritto)' : '');
        $this->newLine();

        // Recupera solo gli ordini completamente archiviati su ARCH_L:
        // - status='archived' → workflow completato
        // - archived_at NOT NULL → timestamp di archiviazione presente
        // - is_locked=true → lock MDR attivo
        // Gli ordini con is_locked=true ma archived_at NULL sono ancora in lavorazione
        // su altri dischi (ING_L/BAC_L/FIN_L) e non vanno verificati qui.
        $orders = Order::where('is_locked', true)
            ->where('status', 'archived')
            ->whereNotNull('archived_at')
            ->with('files')
            ->orderBy('code')
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('Nessun ordine archiviato trovato.');
            return self::SUCCESS;
        }

        $this->info("Ordini archiviati da verificare: {$orders->count()}");
        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $ordersResults = [];

        foreach ($orders as $order) {
            $orderOk  = true;
            $fileRows = [];

            if ($order->files->isEmpty()) {
                $ordersResults[] = [
                    'code'           => $order->code,
                    'archived_at'    => $order->archived_at?->format('d/m/Y') ?? 'N/D',
                    'retention_until'=> $order->legal_retention_until?->format('d/m/Y') ?? 'N/D',
                    'skip'           => true,
                    'has_anomaly'    => false,
                    'files'          => [],
                ];
                $bar->advance();
                continue;
            }

            foreach ($order->files as $file) {
                $this->checked++;
                $archPath = $order->directory_name . '/assets_originali/' . basename($file->file_path);

                if (empty($file->hash_sha256)) {
                    $this->noHash++;
                    $fileRows[] = ['name' => $file->original_name, 'status' => 'no_hash', 'hash' => null, 'expected' => null, 'actual' => null];
                    continue;
                }

                if (!Storage::disk('unraid_arch_l')->exists($archPath)) {
                    $this->missing++;
                    $orderOk    = false;
                    $fileRows[] = ['name' => $file->original_name, 'status' => 'missing', 'hash' => null, 'expected' => null, 'actual' => null];
                    $this->anomalies[] = "[MISSING] Ordine {$order->code} — {$file->original_name}";
                    Log::error('MDR-INTEGRITY-MISSING: file non trovato', ['order_code' => $order->code, 'file' => $file->original_name, 'arch_path' => $archPath]);
                    continue;
                }

                $fullPath   = Storage::disk('unraid_arch_l')->path($archPath);
                $actualHash = hash_file('sha256', $fullPath);

                if ($actualHash === $file->hash_sha256) {
                    $this->ok++;
                    $fileRows[] = ['name' => $file->original_name, 'status' => 'ok', 'hash' => $file->hash_sha256, 'expected' => null, 'actual' => null];
                } else {
                    $this->tampered++;
                    $orderOk    = false;
                    $fileRows[] = ['name' => $file->original_name, 'status' => 'tampered', 'hash' => null, 'expected' => $file->hash_sha256, 'actual' => $actualHash];
                    $this->anomalies[] = "[TAMPERED] Ordine {$order->code} — {$file->original_name}";
                    Log::critical('MDR-INTEGRITY-TAMPERED: hash non corrisponde', ['order_code' => $order->code, 'file' => $file->original_name, 'hash_expected' => $file->hash_sha256, 'hash_actual' => $actualHash]);
                }
            }

            // === Verifica PDF MDR contro ledger append-only MdrDocumentHash ===
            // Per ogni PDF MDR atteso (4 file): recupera hash corrente dal ledger,
            // ricalcola SHA-256 dal file su ARCH_L, confronta.
            foreach ($order->requiredMdrDocuments() as $mdrPdf) {
                $this->mdrChecked++;
                $relPath = $order->directory_name . '/' . $mdrPdf;

                if (!Storage::disk('unraid_arch_l')->exists($relPath)) {
                    $this->mdrMissing++;
                    $orderOk = false;
                    $fileRows[] = ['name' => $mdrPdf, 'status' => 'mdr_missing', 'hash' => null, 'expected' => null, 'actual' => null];
                    $this->anomalies[] = "[MDR-MISSING] Ordine {$order->code} — {$mdrPdf}";
                    Log::error('MDR-INTEGRITY-MDR-MISSING', ['order' => $order->code, 'file' => $mdrPdf]);
                    continue;
                }

                $current = MdrDocumentHash::currentFor($order->id, $mdrPdf);
                if (!$current) {
                    // NON è un'anomalia di integrità — è uno stato transitorio atteso per i PDF MDR
                    // generati prima del 2026-05-27 (quando il ledger non esisteva). Solo warning,
                    // NON aggiunto ad $anomalies (che triggerano notifica PRRC).
                    // Per "sigillare" questi file nel ledger, eseguire: php artisan mdr:backfill-hash-ledger
                    $this->mdrNoLedger++;
                    $fileRows[] = ['name' => $mdrPdf, 'status' => 'mdr_no_ledger', 'hash' => null, 'expected' => null, 'actual' => null];
                    Log::warning('MDR-INTEGRITY-MDR-NO-LEDGER', ['order' => $order->code, 'file' => $mdrPdf]);
                    continue;
                }

                $actualHash = hash_file('sha256', Storage::disk('unraid_arch_l')->path($relPath));
                if ($actualHash === $current->sha256) {
                    $this->mdrOk++;
                    $chainLen = MdrDocumentHash::where('order_id', $order->id)->where('file_name', $mdrPdf)->count();
                    if ($chainLen > 1) $this->mdrRegenChain++;
                    $fileRows[] = [
                        'name'     => $mdrPdf . ($chainLen > 1 ? " (catena: {$chainLen})" : ''),
                        'status'   => 'mdr_ok',
                        'hash'     => $current->sha256,
                        'expected' => null,
                        'actual'   => null,
                    ];
                } else {
                    $this->mdrTampered++;
                    $orderOk = false;
                    $fileRows[] = [
                        'name'     => $mdrPdf,
                        'status'   => 'mdr_tampered',
                        'hash'     => null,
                        'expected' => $current->sha256,
                        'actual'   => $actualHash,
                    ];
                    $this->anomalies[] = "[MDR-TAMPERED] Ordine {$order->code} — {$mdrPdf}";
                    Log::critical('MDR-INTEGRITY-MDR-TAMPERED', [
                        'order'           => $order->code,
                        'file'            => $mdrPdf,
                        'hash_expected'   => $current->sha256,
                        'hash_actual'     => $actualHash,
                        'ledger_source'   => $current->generation_source,
                        'ledger_capa_ref' => $current->capa_reference,
                    ]);
                }
            }

            $ordersResults[] = [
                'code'            => $order->code,
                'archived_at'     => $order->archived_at?->format('d/m/Y') ?? 'N/D',
                'retention_until' => $order->legal_retention_until?->format('d/m/Y') ?? 'N/D',
                'skip'            => false,
                'has_anomaly'     => !$orderOk,
                'files'           => $fileRows,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $duration = (int) $startAt->diffInSeconds(now());

        // --- Generazione PDF ---
        $reportFileName = "INTEGRITY_REPORT_{$month}.pdf";
        $viewData = [
            'month'         => $month,
            'generato_il'   => now(),
            'duration'      => $duration,
            'lab'           => config('lab.manufacturer.name'),
            'prrc_name'     => config('lab.prrc.name'),
            'prrc_role'     => config('lab.prrc.role'),
            'checked'       => $this->checked,
            'ok'            => $this->ok,
            'tampered'      => $this->tampered,
            'missing'       => $this->missing,
            'noHash'        => $this->noHash,
            'mdrChecked'    => $this->mdrChecked,
            'mdrOk'         => $this->mdrOk,
            'mdrTampered'   => $this->mdrTampered,
            'mdrMissing'    => $this->mdrMissing,
            'mdrNoLedger'   => $this->mdrNoLedger,
            'mdrRegenChain' => $this->mdrRegenChain,
            'ordersResults' => $ordersResults,
            'anomalies'     => $this->anomalies,
        ];

        if (!$dryRun) {
            try {
                $pdf  = Pdf::loadView('pdfs.integrity_report', $viewData)->output();
                $disk = Storage::disk('unraid_arch_l');
                file_put_contents($disk->path($reportFileName), $pdf);
                $this->info("Report PDF scritto su ARCH_L: {$reportFileName}");
            } catch (\Throwable $e) {
                $this->error("Errore generazione PDF: " . $e->getMessage());
                Log::error('MDR-ARCHIVE-LOG-FAIL: errore generazione integrity report PDF', ['error' => $e->getMessage()]);
            }
        } else {
            $totaleAnomalie = $this->tampered + $this->missing + $this->mdrTampered + $this->mdrMissing;
            $esito = $totaleAnomalie === 0 ? 'INTEGRO' : 'ANOMALIE RILEVATE';
            $this->line("File utente:   Verificati {$this->checked} | OK {$this->ok} | Mancanti {$this->missing} | Alterati {$this->tampered} | No hash {$this->noHash}");
            $this->line("PDF MDR:       Verificati {$this->mdrChecked} | OK {$this->mdrOk} | Mancanti {$this->mdrMissing} | Alterati {$this->mdrTampered} | No ledger {$this->mdrNoLedger} | Rigenerati {$this->mdrRegenChain}");
            $this->line("Esito: {$esito} | Durata: {$duration}s");
            $this->warn("(dry-run: PDF non scritto)");
        }

        // --- Notifica PRRC se ci sono anomalie ---
        if (!empty($this->anomalies)) {
            $prrcEmail = config('lab.prrc.email');
            if ($prrcEmail && !$dryRun) {
                $anomalyList = implode('<br>', array_map(fn($a) => "• {$a}", $this->anomalies));
                Mail::to($prrcEmail)->queue(new EnamelNotification([
                    'subject'    => "🚨 ANOMALIE ARCHIVIO MDR — Verifica integrità {$month}",
                    'title'      => 'Report Integrità Archivio MDR — Anomalie Rilevate',
                    'audience'   => 'internal',
                    'message'    =>
                        "La verifica mensile dell'integrità dell'archivio MDR ha rilevato <strong>" .
                        count($this->anomalies) . " anomalie</strong>:<br><br>" .
                        $anomalyList .
                        "<br><br>Aprire il report <strong>{$reportFileName}</strong> su ARCH_L per i dettagli.<br><br>" .
                        "Ai sensi dell'Art. 10(8) MDR, è necessario verificare l'integrità della documentazione tecnica e adottare misure correttive.",
                    'actionUrl'  => route('dashboard.archive'),
                    'actionText' => 'Vai all\'Archivio',
                ]));
            }

            $this->newLine();
            $this->error("⚠️  File utente: {$this->tampered} alterati, {$this->missing} mancanti. PDF MDR: {$this->mdrTampered} alterati, {$this->mdrMissing} mancanti — email inviata al PRRC.");
            Log::critical("MDR-INTEGRITY-REPORT: anomalie rilevate", [
                'month'        => $month,
                'tampered'     => $this->tampered,
                'missing'      => $this->missing,
                'mdr_tampered' => $this->mdrTampered,
                'mdr_missing'  => $this->mdrMissing,
                'anomalies'    => $this->anomalies,
            ]);
            return self::FAILURE;
        }

        $this->info("✓ Verifica completata. {$this->ok} file integri, nessuna anomalia.");
        Log::info("MDR-INTEGRITY-REPORT: verifica completata senza anomalie", [
            'month'   => $month,
            'checked' => $this->checked,
            'ok'      => $this->ok,
        ]);

        return self::SUCCESS;
    }
}
