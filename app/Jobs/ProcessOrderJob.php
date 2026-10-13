<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\MdrCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, Storage, File};
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\VirusScanner;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    public function __construct(public Order $order, public string $action = 'default') {}

    public function handle(): void
    {
        try {
            $this->order->refresh()->load(['studio', 'address', 'files', 'production']);
            
            match ($this->action) {
                'move_to_fin'   => $this->handleMoveToFin(),
                'generate_pdfs' => $this->handleGeneratePdfs(),
                'move_to_arch'  => $this->handleMoveToArch(),
                default         => $this->handleInitialScanning(),
            };

        } catch (\Throwable $e) {
            Log::error("MDR-JOB-FAIL [{$this->order->code}] Action: {$this->action} -> " . $e->getMessage());
            $this->order->update(['status' => 'error_system']);
            $this->order->logSystemEvent("ERRORE SISTEMA durante {$this->action}: " . $e->getMessage());
        }
    }

    protected function handleInitialScanning() {
        // FIX ④ — directory_name si imposta UNA sola volta e non si ri-deriva mai più
        // (chiude alla radice il drift di naming, es. un caso reale di disallineamento).
        $dir = $this->order->directory_name
            ?: strtoupper($this->order->code . "_" . Str::slug($this->order->patient_ref));
        if (!$this->order->directory_name) {
            $this->order->update(['directory_name' => $dir]);
        }
        $disk = Storage::disk('unraid'); 
        
        if (!$disk->exists($dir)) $disk->makeDirectory($dir);
        if (!$disk->exists("{$dir}/assets_originali")) $disk->makeDirectory("{$dir}/assets_originali");

        $scanReport = "RAPPORTO INTEGRITÀ DATI MDR - PRATICA: {$this->order->code}\n";
        $scanReport .= "Eseguito il: " . now()->format('d/m/Y H:i:s') . "\n";
        $scanReport .= "Scanner: ClamAV (contenuto) + filtro estensioni\n";
        $scanReport .= "--------------------------------------------------\n\n";

        $scanner = new VirusScanner();

        foreach ($this->order->files as $file) {
            $path = storage_path("app/private/" . $file->file_path);
            if (!File::exists($path)) {
                $scanReport .= "[SALTATO] {$file->original_name} -> File non trovato su disco.\n";
                continue;
            }

            // 1. Blocco rapido per estensioni eseguibili pericolose
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, ['exe', 'bat', 'sh', 'js', 'ps1', 'vbs', 'cmd'])) {
                File::delete($path);
                $file->update(['scan_status' => 'infected', 'scanned_at' => now(), 'error_log' => 'Estensione eseguibile bloccata: ' . $extension]);
                $scanReport .= "[SCARTATO] {$file->original_name} -> Estensione eseguibile bloccata ({$extension}).\n";
                Log::warning("MDR-SCAN: File bloccato per estensione [{$this->order->code}]: {$file->original_name}");
                continue;
            }

            // 2. Scansione ClamAV sul contenuto del file
            $scanResult = $scanner->scan($file->file_path);

            if ($scanResult === 'infected') {
                File::delete($path);
                $file->update(['scan_status' => 'infected', 'scanned_at' => now(), 'error_log' => 'Malware rilevato da ClamAV']);
                $scanReport .= "[SCARTATO] {$file->original_name} -> Malware rilevato da ClamAV.\n";
                Log::warning("MDR-SCAN: Malware rilevato da ClamAV [{$this->order->code}]: {$file->original_name}");
                continue;
            }

            // 3. Accetta il file (clean, bypass, scan_error)
            $destPath = $disk->path("{$dir}/assets_originali/{$file->original_name}");
            copy($path, $destPath);
            File::delete($path);

            if ($scanResult === 'clean') {
                $file->update(['scan_status' => 'clean', 'scanned_at' => now()]);
                $scanReport .= "[ACCETTATO] {$file->original_name} -> ClamAV: pulito.\n";
            } elseif ($scanResult === 'bypass') {
                $file->update(['scan_status' => 'clean', 'scanned_at' => now(), 'error_log' => 'ClamAV non raggiungibile, scansione contenuto non eseguita']);
                $scanReport .= "[ACCETTATO] {$file->original_name} -> ClamAV non disponibile, filtro estensioni superato.\n";
                Log::warning("MDR-SCAN: ClamAV non raggiungibile per [{$this->order->code}], file accettato con riserva: {$file->original_name}");
            } else {
                // scan_error
                $file->update(['scan_status' => 'scan_error', 'scanned_at' => now(), 'error_log' => 'Errore durante la scansione ClamAV']);
                $scanReport .= "[ACCETTATO con ERRORE] {$file->original_name} -> Errore scanner ClamAV, file accettato con riserva.\n";
                Log::error("MDR-SCAN: Errore ClamAV [{$this->order->code}]: {$file->original_name}");
            }
        }

        $this->order->load('files');
        $disk->put("{$dir}/MDR_SCAN_REPORT.txt", $scanReport);
        
        $pdfData = $this->preparePdfData();
        $disk->put("{$dir}/Richiesta_{$this->order->code}.pdf", Pdf::loadView('pdfs.request_sheet', $pdfData)->output());

        $this->order->unlockPermissions('unraid');
        $this->order->update(['status' => 'processing']);
    }

    protected function handleMoveToFin() {
        $canonicalDir = $this->order->directory_name;
        $src = Storage::disk('unraid_bac_l');
        $dst = Storage::disk('unraid_fin_l');

        // FIX ④ — risolvi la sorgente per prefisso {CODE}_ (robusto al drift), fallback al nome canonico.
        $srcDir = $this->order->resolveDirectoryOnDisk('unraid_bac_l') ?? $canonicalDir;
        if (!$src->exists($srcDir)) return;

        // Sblocca i permessi della SORGENTE (BAC_L) PRIMA di copia/delete: file Samba con UID diversi
        // non sono leggibili/unlinkabili dal PHP senza chmod preventivo (era il difetto che lasciava
        // il residuo pieno in BAC_L).
        $srcPath = $src->path($srcDir);
        if (file_exists($srcPath)) exec("chmod -R 777 " . escapeshellarg($srcPath));

        if (!$dst->exists($canonicalDir)) $dst->makeDirectory($canonicalDir);

        // Bypass Flysystem (Anti-Crash per file di root/Blender). Dest = nome canonico (normalizza il drift).
        $sourceFiles = $src->allFiles($srcDir);
        $copyFailures = [];
        foreach ($sourceFiles as $file) {
            $destRel = $canonicalDir . substr($file, strlen($srcDir));
            $destPath = $dst->path($destRel);
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) mkdir($destDir, 0777, true);

            $readStream = $src->readStream($file);
            $writeStream = @fopen($destPath, 'w');
            if ($readStream === false || $writeStream === false) {
                $copyFailures[] = $file;
                if (is_resource($readStream)) fclose($readStream);
                if (is_resource($writeStream)) fclose($writeStream);
                continue;
            }
            $bytes = @stream_copy_to_stream($readStream, $writeStream);
            fclose($writeStream);
            if (is_resource($readStream)) fclose($readStream);
            if ($bytes === false) $copyFailures[] = $file;
        }

        // ABORT-and-retain: se anche un solo file non è stato copiato, NON cancellare BAC_L, NON avanzare.
        if (!empty($copyFailures)) {
            Log::error("MDR-MOVEFIN-FAIL [{$this->order->code}]: copia fallita per " . count($copyFailures) . " file: " . implode(', ', array_slice($copyFailures, 0, 5)));
            $this->order->logSystemEvent("⚠️ Spostamento in FINITURA INTERROTTO: " . count($copyFailures) . " file non copiati su FIN_L. Riprovare.");
            throw new \RuntimeException("Move BAC->FIN abortito: copia fallita per " . count($copyFailures) . " file.");
        }

        // Verifica completezza (conteggio junk-filtrato su entrambi i lati).
        $srcCount = count(array_filter($sourceFiles, fn($f) => !str_contains($f, '.DS_Store') && !str_starts_with(basename($f), '._')));
        $destCount = count(array_filter($dst->allFiles($canonicalDir), fn($f) => !str_contains($f, '.DS_Store') && !str_starts_with(basename($f), '._')));
        if ($destCount < $srcCount) {
            Log::error("MDR-MOVEFIN-FAIL [{$this->order->code}]: FIN_L contiene {$destCount} file, attesi {$srcCount}");
            $this->order->logSystemEvent("⚠️ Spostamento in FINITURA INTERROTTO: verifica completezza fallita.");
            throw new \RuntimeException("Move BAC->FIN abortito: verifica completezza fallita.");
        }

        $this->order->unlockPermissions('unraid_fin_l');

        // Pulizia sorgente VERIFICATA (non fidarsi del ritorno di deleteDirectory — vedi FIX ③).
        if (!$this->purgeSourceDirectory($src, $srcDir)) {
            Log::warning("MDR-MOVEFIN [{$this->order->code}]: pulizia BAC_L incompleta per {$srcDir} (residuo persistente)");
            $this->order->logSystemEvent("⚠️ Materiale in FINITURA, ma pulizia BAC_L incompleta: residuo {$srcDir} da rimuovere.");
        }

        $this->order->logSystemEvent("Materiale spostato in FINITURA (FIN_L).");
    }

    protected function handleGeneratePdfs() {
        $disk = Storage::disk('unraid_fin_l');
        // FIX ④ — scrivi nella cartella REALE (robusto al drift): mai creare un secondo {CODE}_ canonico
        // accanto a una cartella drift-ata (creerebbe ambiguità e bloccherebbe move_to_arch).
        $dir = $this->order->resolveDirectoryOnDisk('unraid_fin_l') ?? $this->order->directory_name;
        if (!$disk->exists($dir)) $disk->makeDirectory($dir);

        $pdfData = $this->preparePdfData();
        try {
            // Mappa filename → vista (mantiene un punto unico di verità)
            $code = $this->order->code;
            $map = [
                "SCHEDA_TECNICA_{$code}.pdf"     => 'pdfs.production_report',
                "CONFORMITA_MDR_{$code}.pdf"     => 'pdfs.conformity_declaration',
                "ETICHETTA_{$code}.pdf"          => 'pdfs.shipping_label',
                "INFORMATIVA_PAZIENTE_{$code}.pdf" => 'pdfs.patient_info',
            ];

            // Per ciascun file: rilevamento se esiste già un hash 'initial' → in tal caso è una rigenerazione
            // e richiede notes/motivazione. Senza, viene registrato 'initial' al primo giro.
            foreach ($map as $fileName => $view) {
                $relPath = "{$dir}/{$fileName}";
                $existingInitial = \App\Models\MdrDocumentHash::where('order_id', $this->order->id)
                    ->where('file_name', $fileName)
                    ->where('generation_source', 'initial')
                    ->exists();

                // Genera/sovrascrive il PDF
                file_put_contents($disk->path($relPath), Pdf::loadView($view, $pdfData)->output());

                // Registra hash nel ledger append-only
                if (!$existingInitial) {
                    \App\Models\MdrDocumentHash::record(
                        $this->order,
                        'unraid_fin_l',
                        $relPath,
                        'initial',
                        ['by' => auth()->user()?->uid ?? 'system']
                    );
                } else {
                    // Rigenerazione manuale (l'operatore ha cliccato "Rigenera Doc")
                    \App\Models\MdrDocumentHash::record(
                        $this->order,
                        'unraid_fin_l',
                        $relPath,
                        'manual',
                        [
                            'by' => auth()->user()?->uid ?? 'system',
                            'notes' => 'Rigenerazione manuale via pulsante "Rigenera Doc" in dashboard.',
                        ]
                    );
                }
            }

            $this->order->unlockPermissions('unraid_fin_l');
            $this->order->logSystemEvent("Fascicolo PDF MDR generato e hash registrati nel ledger append-only.");
        } catch (\Exception $pdfError) {
            Log::error("MDR-PDF-JOB-ERROR [{$this->order->code}]: " . $pdfError->getMessage());
            throw $pdfError;
        }
    }

    protected function handleMoveToArch() {
        $canonicalDir = $this->order->directory_name;
        $src = Storage::disk('unraid_fin_l');
        $dst = Storage::disk('unraid_arch_l');

        // FIX ④ — risolvi la sorgente per prefisso {CODE}_ (robusto al drift), fallback al nome canonico.
        $srcDir = $this->order->resolveDirectoryOnDisk('unraid_fin_l') ?? $canonicalDir;

        if ($src->exists($srcDir)) {
            // Normalizza i permessi di lettura su FIN_L prima della copia (file Samba con UID diversi
            // → readStream fallirebbe). chmod diretto sul path risolto (può essere drift-ato).
            $srcPath = $src->path($srcDir);
            if (file_exists($srcPath)) exec("chmod -R 777 " . escapeshellarg($srcPath));

            if (!$dst->exists($canonicalDir)) $dst->makeDirectory($canonicalDir);

            // Bypass Flysystem (Anti-Crash per file di root/Blender)
            $sourceFiles = $src->allFiles($srcDir);
            $copyFailures = [];
            foreach ($sourceFiles as $file) {
                $destRel = $canonicalDir . substr($file, strlen($srcDir)); // normalizza al nome canonico
                $destPath = $dst->path($destRel);
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) mkdir($destDir, 0777, true);

                $readStream = $src->readStream($file);
                $writeStream = @fopen($destPath, 'w');
                if ($readStream === false || $writeStream === false) {
                    $copyFailures[] = $file;
                    if (is_resource($readStream)) fclose($readStream);
                    if (is_resource($writeStream)) fclose($writeStream);
                    continue;
                }
                $bytes = @stream_copy_to_stream($readStream, $writeStream);
                fclose($writeStream);
                if (is_resource($readStream)) fclose($readStream);
                if ($bytes === false) $copyFailures[] = $file;
            }

            // ABORT se anche un solo file non è stato copiato — NON cancellare nulla da FIN_L,
            // NON lockare l'ordine. Operatore deve riprovare dopo aver risolto il problema.
            if (!empty($copyFailures)) {
                Log::error("MDR-ARCHIVE-FAIL [{$this->order->code}]: copia fallita per " . count($copyFailures) . " file: " . implode(', ', array_slice($copyFailures, 0, 5)));
                $this->order->logSystemEvent("⚠️ Archiviazione INTERROTTA: " . count($copyFailures) . " file non copiati su ARCH_L. Riprovare. File: " . implode(', ', array_slice($copyFailures, 0, 5)));
                throw new \RuntimeException("Archiviazione abortita: copia fallita per " . count($copyFailures) . " file.");
            }

            // Verifica completezza: count file su FIN_L === count file su ARCH_L per questa directory.
            $destFiles = $dst->allFiles($canonicalDir);
            if (count($destFiles) < count($sourceFiles)) {
                Log::error("MDR-ARCHIVE-FAIL [{$this->order->code}]: ARCH_L contiene " . count($destFiles) . " file, attesi " . count($sourceFiles));
                $this->order->logSystemEvent("⚠️ Archiviazione INTERROTTA: verifica completezza fallita.");
                throw new \RuntimeException("Archiviazione abortita: verifica completezza fallita.");
            }

            $this->order->unlockPermissions('unraid_arch_l');

            // SIGILLO ARCHIVIO — registra hash SHA-256 dei 4 PDF MDR su ARCH_L come 'archive_seal'.
            // Da questo momento gli hash su ARCH_L sono "congelati" nel ledger append-only.
            // Una qualsiasi modifica successiva ai file sarà rilevata da mdr:verify-integrity.
            $sealErrors = [];
            foreach ($this->order->requiredMdrDocuments() as $pdfName) {
                $relPath = "{$canonicalDir}/{$pdfName}";
                if (!$dst->exists($relPath)) {
                    $sealErrors[] = $pdfName;
                    continue;
                }
                try {
                    \App\Models\MdrDocumentHash::record(
                        $this->order,
                        'unraid_arch_l',
                        $relPath,
                        'archive_seal',
                        ['by' => auth()->user()?->uid ?? 'system']
                    );
                } catch (\Throwable $e) {
                    $sealErrors[] = "{$pdfName} ({$e->getMessage()})";
                    Log::error("MDR-HASH-SEAL-FAIL [{$this->order->code}]: {$pdfName} → {$e->getMessage()}");
                }
            }
            if (!empty($sealErrors)) {
                $this->order->logSystemEvent("⚠️ Archiviazione INTERROTTA: sigillo hash fallito per: " . implode(', ', $sealErrors));
                throw new \RuntimeException("Archiviazione abortita: impossibile sigillare hash MDR per " . count($sealErrors) . " file.");
            }

            // Cancellazione sorgente VERIFICATA — solo dopo sigillo riuscito.
            // FIX ③: File::deleteDirectory ritorna true anche se l'@rmdir finale fallisce → era codice
            // morto (il warning non scattava mai). purgeSourceDirectory ri-scansiona, tenta un rmdir
            // ricorsivo bottom-up e conferma con exists(): ora il warning scatta DAVVERO sui residui.
            if (!$this->purgeSourceDirectory($src, $srcDir)) {
                Log::warning("MDR-ARCHIVE [{$this->order->code}]: pulizia FIN_L incompleta per {$srcDir} (residuo persistente dopo rmdir ricorsivo)");
                $this->order->logSystemEvent("⚠️ Archiviazione completata su ARCH_L (hash sigillati), ma pulizia FIN_L incompleta: residuo {$srcDir} da rimuovere.");
            }
        }
        
        // Calcola retention MDR: 15 anni per dispositivi impiantabili, 10 per gli altri (Art. 10(8) MDR 2017/745)
        $retentionYears = $this->resolveRetentionYears();
        $this->order->update([
            'status'                => 'archived',
            'archived_at'           => now(),
            'is_locked'             => true,
            'legal_retention_until' => now()->addYears($retentionYears),
        ]);
        $this->order->logSystemEvent("Archiviazione definitiva completata. Conservazione MDR: {$retentionYears} anni.");
    }

    /**
     * Cancella la cartella sorgente e VERIFICA che sia davvero sparita.
     *
     * File::deleteDirectory ritorna `true` anche se l'@rmdir finale fallisce (ritorno soppresso):
     * era la causa dei gusci vuoti residui su FIN_L/BAC_L col warning che non scattava mai.
     * Qui: (1) deleteDirectory, (2) se la cartella persiste, rmdir ricorsivo bottom-up, (3) conferma con exists().
     * Ritorna true se la sorgente è pulita, false se persiste (residuo reale → il chiamante logga un warning VERO).
     */
    private function purgeSourceDirectory($srcDisk, string $srcDir): bool
    {
        $base = $srcDisk->path($srcDir);
        File::deleteDirectory($base);
        if (!is_dir($base)) return true; // is_dir() = verità fisica, affidabile anche per directory vuote

        // Restano directory (tipicamente vuote): rmdir/unlink ricorsivo bottom-up.
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($base);

        return !is_dir($base);
    }

    /**
     * Determina gli anni di conservazione MDR in base al tipo di dispositivo.
     *
     * Logica (MDR Art. 10(8)):
     *   1. Cerca il dispositivo specifico tramite final_device_slug nel catalogo
     *   2. Se is_implantable = true  → 15 anni (dispositivo impiantabile)
     *   3. Se is_implantable = false → 10 anni (dispositivo non impiantabile)
     *   4. Se il dispositivo non è in catalogo (ordine legacy) → 15 anni (cautelativo)
     *
     * Definizione impiantabile ex Art. 2(5) MDR: qualsiasi dispositivo che il paziente
     * non può rimuovere autonomamente (corone cementate, ponti fissi, protesi su impianti).
     */
    private function resolveRetentionYears(): int
    {
        $slug = $this->order->final_device_slug;

        if ($slug) {
            $catalogEntry = MdrCatalog::where('slug', $slug)->first();
            if ($catalogEntry) {
                return $catalogEntry->is_implantable
                    ? config('legal.retention.implant', 15)
                    : config('legal.retention.standard', 10);
            }
        }

        // Fallback cautelativo per ordini senza slug catalogo o legacy
        Log::warning("MDR-RETENTION: slug catalogo assente per ordine {$this->order->code}, applicata retention cautelativa 15 anni.");
        return config('legal.retention.implant', 15);
    }

    private function preparePdfData() {
        return [
            'order' => $this->order,
            'production' => $this->order->production,
            // ⚠️ Token "Titolare del Trattamento" dell'Informativa Paziente (Art. 13 GDPR):
            // devono SEMPRE riferirsi al clinico PRESCRITTORE che ha in cura il paziente
            // (titolare dei dati del paziente), MAI al laboratorio committente.
            // Oggi (config "a": intestatario account = clinico prescrittore) derivano
            // correttamente da order->address / order->studio. Se in futuro si implementa
            // la config "b" (intestatario = laboratorio / centro di fresaggio), la sorgente
            // del Titolare deve RESTARE il clinico prescrittore a monte, NON l'intestatario lab.
            'clinician' => $this->order->address->dentist_name ?? ($this->order->studio->dentist_name ?? 'N/D'),
            'studio_name' => $this->order->studio->name ?? 'N/D',
            'studio_address' => $this->order->address->address ?? 'N/D',
            'license' => $this->order->address->dentist_license ?? 'N/D',
            'prov' => $this->order->address->albo_provincia ?? 'N/D'
        ];
    }
}