<?php
namespace App\Jobs;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Storage, Log};
class MoveToProductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 3600;
    public function __construct(public Order $order) {}
    public function handle(): void
    {
        $canonicalDir = $this->order->directory_name;
        if (!$canonicalDir) {
            Log::error("MDR-MOVER-ABORT: Directory name non impostata per {$this->order->code}");
            return;
        }
        $source = Storage::disk('unraid');
        $dest = Storage::disk('unraid_bac_l');
        try {
            // FIX ④ — risolvi la cartella sorgente per prefisso {CODE}_ (robusto al drift), fallback al nome canonico.
            $srcDir = $this->order->resolveDirectoryOnDisk('unraid') ?? $canonicalDir;
            if (!$source->exists($srcDir)) throw new \Exception("Cartella sorgente {$srcDir} non trovata.");

            $sourceFiles = $source->allFiles($srcDir);
            // Filtra spazzatura macOS (.DS_Store, ._* resource forks)
            $sourceFiles = array_values(array_filter($sourceFiles, fn($f) => !str_contains($f, '.DS_Store') && !str_starts_with(basename($f), '._')));
            if (empty($sourceFiles)) throw new \Exception("Nessun file (non-junk) trovato in {$srcDir}.");

            Log::info("MDR-PRODUCTION-MOVER: Spostamento di " . count($sourceFiles) . " file per {$this->order->code} ({$srcDir} -> {$canonicalDir})...");

            // Scrittura diretta via stream PHP nativo — bypassa Flysystem chmod/visibility (crasha su SMB/NFS Unraid).
            // Il path di destinazione usa SEMPRE il nome canonico → normalizza in avanti l'eventuale drift.
            foreach ($sourceFiles as $file) {
                $destRel = $canonicalDir . substr($file, strlen($srcDir)); // rimappa il componente radice al nome canonico
                $destPath = $dest->path($destRel);
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }
                $readStream = $source->readStream($file);
                $writeStream = fopen($destPath, 'w');
                stream_copy_to_stream($readStream, $writeStream);
                fclose($writeStream);
                if (is_resource($readStream)) fclose($readStream);
            }

            // (2) Gate di completezza ONESTO: filtra il dest con gli STESSI criteri della sorgente.
            $destFiles = array_values(array_filter($dest->allFiles($canonicalDir), fn($f) => !str_contains($f, '.DS_Store') && !str_starts_with(basename($f), '._')));
            if (count($destFiles) < count($sourceFiles)) {
                throw new \Exception("Errore integrità trasferimento: dest " . count($destFiles) . " < src " . count($sourceFiles) . ".");
            }

            // (1) Sblocca i permessi della SORGENTE (ING_L) PRIMA del delete: i file scritti via Samba
            //     con UID diversi non sono unlinkabili dal PHP senza chmod preventivo.
            //     chmod diretto sul path risolto (unlockPermissions userebbe directory_name, che può essere drift-ato).
            $srcPath = $source->path($srcDir);
            if (file_exists($srcPath)) exec("chmod -R 777 " . escapeshellarg($srcPath));

            // (3) Non fidarsi del ritorno di deleteDirectory (ritorna true anche se l'@rmdir fallisce):
            //     cancella, e se la cartella persiste tenta un rmdir ricorsivo bottom-up, poi conferma con is_dir().
            \Illuminate\Support\Facades\File::deleteDirectory($srcPath);
            if (is_dir($srcPath)) {
                $items = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($srcPath, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($items as $item) {
                    $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
                }
                @rmdir($srcPath);
            }
            if (is_dir($srcPath)) {
                // Residuo COSMETICO: i file sono GIÀ in BAC_L (copia + gate completezza superati);
                // la cartella ING_L è al più un guscio vuoto non-rimovibile (chmod best-effort su
                // multi-UID Samba/NFS Unraid). Come i mover fratelli (handleMoveToFin/handleMoveToArch)
                // si logga un warning VERO ma NON si blocca l'ordine: throw-are qui deadlockerebbe un
                // lavoro i cui file sono già al sicuro, e ogni retry ri-lancerebbe (allFiles vuoto).
                Log::warning("MDR-PRODUCTION-MOVER-RESIDUE [{$this->order->code}]: sorgente ING_L non rimossa dopo delete: {$srcDir} (guscio residuo, pulizia manuale)");
                $this->order->logSystemEvent("⚠️ Materiale trasferito in Laboratorio (BAC_L); pulizia ING_L incompleta: residuo {$srcDir} da rimuovere.");
            }

            $this->order->unlockPermissions('unraid_bac_l');
            $this->order->update(['status' => 'in_progettazione']);
            $this->order->logSystemEvent("Materiale trasferito in Laboratorio (BAC_L). Progettazione avviata.");
        } catch (\Throwable $e) {
            Log::error("MDR-PRODUCTION-MOVER-FAIL [{$this->order->code}]: " . $e->getMessage());
            throw $e;
        }
    }
}