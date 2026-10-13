<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\MdrDocumentHash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Log, Storage};

/**
 * mdr:backfill-hash-ledger
 *
 * Comando one-shot per "sigillare" nel ledger append-only MdrDocumentHash i PDF MDR
 * esistenti su ARCH_L generati prima del 2026-05-27 (quando il ledger non esisteva).
 *
 * Per ogni ordine archived+locked, per ogni PDF MDR atteso presente su ARCH_L senza
 * riga corrispondente nel ledger, calcola SHA-256 e crea una riga con:
 *   - generation_source = 'archive_seal'
 *   - generated_by      = 'system-backfill'
 *   - notes             = "Backfill ledger 2026-05-27 — sigillo postumo PDF MDR pre-esistenti"
 *
 * Default: --dry-run (solo conteggio). Per applicare: --apply
 *
 * Idempotente: se il ledger ha GIÀ una riga per il file (qualsiasi source), salta.
 */
class BackfillHashLedger extends Command
{
    protected $signature = 'mdr:backfill-hash-ledger {--apply : Esegue il backfill; default è dry-run}';
    protected $description = 'Sigilla nel ledger MdrDocumentHash i PDF MDR pre-esistenti su ARCH_L (one-shot, idempotente).';

    public function handle(): int
    {
        $apply = $this->option('apply');
        $disk = Storage::disk('unraid_arch_l');

        $this->info($apply ? '⚠️  MODALITÀ APPLY — verranno create righe nel ledger.' : '🔍 DRY-RUN — nessuna scrittura. Usa --apply per eseguire.');
        $this->newLine();

        $orders = Order::where('status', 'archived')
            ->where('is_locked', true)
            ->whereNotNull('archived_at')
            ->orderBy('archived_at')
            ->get();

        $this->info("Ordini archived da analizzare: {$orders->count()}");
        $this->newLine();

        $stats = ['analyzed' => 0, 'already_in_ledger' => 0, 'sealed' => 0, 'file_missing' => 0, 'errors' => 0];

        foreach ($orders as $order) {
            foreach ($order->requiredMdrDocuments() as $pdfName) {
                $stats['analyzed']++;
                $relPath = "{$order->directory_name}/{$pdfName}";

                if (!$disk->exists($relPath)) {
                    $stats['file_missing']++;
                    continue;
                }

                $exists = MdrDocumentHash::where('order_id', $order->id)
                    ->where('file_name', $pdfName)
                    ->exists();
                if ($exists) {
                    $stats['already_in_ledger']++;
                    continue;
                }

                if (!$apply) {
                    $stats['sealed']++;
                    continue;
                }

                try {
                    MdrDocumentHash::record(
                        $order,
                        'unraid_arch_l',
                        $relPath,
                        'archive_seal',
                        [
                            'by'    => 'system-backfill',
                            'notes' => 'Backfill ledger 2026-05-27 — sigillo postumo PDF MDR pre-esistenti su ARCH_L (file generato prima dell\'introduzione del registro append-only).',
                        ]
                    );
                    $stats['sealed']++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::error("MDR-BACKFILL-FAIL [{$order->code}] {$pdfName}: " . $e->getMessage());
                    $this->error("  [{$order->code}] {$pdfName}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info('=== Riepilogo ===');
        $this->table(
            ['Stato', 'Conteggio'],
            collect($stats)->map(fn($v, $k) => [$k, $v])->values()->toArray()
        );

        if (!$apply && $stats['sealed'] > 0) {
            $this->warn("DRY-RUN: {$stats['sealed']} righe sarebbero create. Esegui con --apply per procedere.");
        } elseif ($apply && $stats['sealed'] > 0) {
            $this->info("✓ Sigillate {$stats['sealed']} righe nel ledger append-only.");
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
