<?php

namespace App\Console\Commands;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Log, Storage};

/**
 * mdr:complete-archived-docs
 *
 * Sanatoria: rigenera i PDF MDR mancanti per ordini già archiviati (is_locked=true).
 *
 * Causa: prima del fix del bug "Genera Doc", alcuni ordini sono stati archiviati senza i 4
 * PDF MDR obbligatori (CONFORMITA_MDR, SCHEDA_TECNICA, ETICHETTA, INFORMATIVA_PAZIENTE)
 * perché il pulsante "Genera Doc" spariva una volta che il QC era stato approvato, e
 * l'archiviazione non aveva pre-check.
 *
 * Questo comando:
 *   1. Elenca ordini status=archived AND is_locked=true
 *   2. Per ciascuno, verifica quali dei 4 PDF MDR mancano su ARCH_L
 *   3. Rigenera i mancanti usando i dati attuali del DB (validation_hash, production, etc.)
 *   4. Logga ogni rigenerazione come OrderComment + Log MDR-CAPA per audit trail
 *
 * MDR Art. 10(8) + ISO 13485 §8.5.2: una CAPA (Corrective and Preventive Action) deve
 * essere documentata. Questo comando produce automaticamente la pista di audit.
 *
 * Default: --dry-run (solo report, nessun file scritto)
 * Per applicare: --apply
 */
class CompleteArchivedDocs extends Command
{
    protected $signature = 'mdr:complete-archived-docs {--apply : Esegue la rigenerazione, altrimenti solo dry-run} {--capa-ref= : Numero CAPA di riferimento (es. CAPA-2026-001) per audit trail}';
    protected $description = 'Rigenera i PDF MDR mancanti per ordini già archiviati (sanatoria bug "Genera Doc").';

    public function handle(): int
    {
        $apply = $this->option('apply');
        $capaRef = $this->option('capa-ref');
        $disk = Storage::disk('unraid_arch_l');

        if ($apply && empty($capaRef)) {
            $this->error('In modalità --apply è OBBLIGATORIO specificare --capa-ref=CAPA-YYYY-NNN per l\'audit trail nel ledger hash MDR.');
            $this->line('Esempio: php artisan mdr:complete-archived-docs --apply --capa-ref=CAPA-2026-001');
            return self::FAILURE;
        }

        $this->info($apply ? "⚠️  MODALITÀ APPLY — i file verranno scritti su ARCH_L. CAPA: {$capaRef}" : '🔍 DRY-RUN — nessuna modifica. Usa --apply per eseguire.');
        $this->newLine();

        $orders = Order::where('status', 'archived')
            ->where('is_locked', true)
            ->whereNotNull('archived_at')
            ->orderBy('archived_at')
            ->get();

        $this->info("Ordini archiviati da analizzare: {$orders->count()}");
        $this->newLine();

        $stats = ['ok' => 0, 'missing_dir' => 0, 'incomplete' => 0, 'regenerated' => 0, 'errors' => 0];
        $report = [];

        foreach ($orders as $order) {
            $dir = $order->directory_name;
            if (!$disk->exists($dir)) {
                $this->warn("  [{$order->code}] cartella ARCH_L mancante: {$dir}");
                $stats['missing_dir']++;
                $report[] = ['code' => $order->code, 'dir' => $dir, 'status' => 'missing_dir', 'missing' => []];
                continue;
            }

            $missing = $order->missingMdrDocuments('unraid_arch_l');
            if (empty($missing)) {
                $stats['ok']++;
                continue;
            }

            $stats['incomplete']++;
            $this->line("  [{$order->code}] mancanti: " . implode(', ', $missing));

            if (!$apply) {
                $report[] = ['code' => $order->code, 'dir' => $dir, 'status' => 'incomplete', 'missing' => $missing];
                continue;
            }

            try {
                $order->load(['studio', 'address', 'files', 'production']);
                $pdfData = $this->buildPdfData($order);

                $viewMap = [
                    "SCHEDA_TECNICA_{$order->code}.pdf"     => 'pdfs.production_report',
                    "CONFORMITA_MDR_{$order->code}.pdf"     => 'pdfs.conformity_declaration',
                    "ETICHETTA_{$order->code}.pdf"          => 'pdfs.shipping_label',
                    "INFORMATIVA_PAZIENTE_{$order->code}.pdf" => 'pdfs.patient_info',
                ];

                foreach ($missing as $pdfName) {
                    $view = $viewMap[$pdfName] ?? null;
                    if (!$view) continue;
                    $relPath = "{$dir}/{$pdfName}";
                    $destPath = $disk->path($relPath);
                    file_put_contents($destPath, Pdf::loadView($view, $pdfData)->output());

                    // Registra hash nel ledger append-only con riferimento CAPA per audit trail SGQ.
                    // Da questo momento l'hash è immutabile; se in futuro il file cambia, verify-integrity lo segnalerà.
                    \App\Models\MdrDocumentHash::record(
                        $order,
                        'unraid_arch_l',
                        $relPath,
                        'capa',
                        [
                            'by' => 'system',
                            'capa_reference' => $capaRef,
                            'notes' => "Rigenerazione postuma — il PDF originale non era mai stato generato (bug pulsante Genera Doc, fix 2026-05-27).",
                        ]
                    );

                    $stats['regenerated']++;
                    Log::warning("MDR-CAPA [{$order->code}]: rigenerato {$pdfName} postuma per archivio incompleto ({$capaRef}).");
                }

                $order->unlockPermissions('unraid_arch_l');
                $order->logSystemEvent("🛠️ CAPA {$capaRef}: rigenerati PDF MDR mancanti su ARCH_L con hash registrato nel ledger: " . implode(', ', $missing));
                $report[] = ['code' => $order->code, 'dir' => $dir, 'status' => 'regenerated', 'missing' => $missing];
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->error("  [{$order->code}] ERRORE rigenerazione: " . $e->getMessage());
                Log::error("MDR-CAPA-FAIL [{$order->code}]: " . $e->getMessage());
                $report[] = ['code' => $order->code, 'dir' => $dir, 'status' => 'error', 'missing' => $missing, 'error' => $e->getMessage()];
            }
        }

        $this->newLine();
        $this->info('=== Riepilogo ===');
        $this->table(
            ['Stato', 'Conteggio'],
            collect($stats)->map(fn($v, $k) => [$k, $v])->values()->toArray()
        );

        if ($apply && $stats['regenerated'] > 0) {
            $reportPath = $disk->path('CAPA_REPORT_' . now()->format('Y-m-d_His') . '.txt');
            $body = "CAPA Report — Rigenerazione PDF MDR mancanti\n";
            $body .= "Data: " . now()->toIso8601String() . "\n\n";
            foreach ($report as $r) {
                $body .= "[{$r['code']}] {$r['status']} — " . implode(', ', $r['missing']) . "\n";
                if (isset($r['error'])) $body .= "  Errore: {$r['error']}\n";
            }
            file_put_contents($reportPath, $body);
            $this->info("Report CAPA scritto in ARCH_L: " . basename($reportPath));
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildPdfData(Order $order): array
    {
        return [
            'order'          => $order,
            'production'     => $order->production,
            'clinician'      => $order->address->dentist_name ?? ($order->studio->dentist_name ?? 'N/D'),
            'studio_name'    => $order->studio->name ?? 'N/D',
            'studio_address' => $order->address->address ?? 'N/D',
            'license'        => $order->address->dentist_license ?? 'N/D',
            'prov'           => $order->address->albo_provincia ?? 'N/D',
        ];
    }
}
