<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ProcessGdprDeletions — Esegue le cancellazioni GDPR Art. 17 in coda.
 *
 * ⚠️  ATTUALMENTE DISABILITATO (ENABLED = false).
 *     Eseguire SOLO in dry-run fino al completamento della procedura
 *     di attivazione.
 *
 * Uso:
 *   php artisan gdpr:process-deletions --dry-run   ← sempre sicuro
 *   php artisan gdpr:process-deletions              ← richiede ENABLED=true
 */
class ProcessGdprDeletions extends Command
{
    /**
     * ⚠️ SAFETY FLAG — DEVE RIMANERE FALSE FINO A TEST COMPLETO
     * Vedi la procedura di attivazione.
     */
    private const ENABLED = false;

    /**
     * Giorni di grazia dalla richiesta prima dell'effettiva cancellazione.
     */
    private const GRACE_PERIOD_DAYS = 30;

    protected $signature   = 'gdpr:process-deletions
                                {--dry-run : Mostra cosa verrebbe cancellato senza modificare nulla}';
    protected $description = '[INATTIVO] Processa le richieste di cancellazione GDPR Art. 17 in coda';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if (! self::ENABLED && ! $dryRun) {
            $this->error('❌ GDPR deletion è DISABILITATO nel codice (ENABLED = false).');
            $this->error('   Questa è una misura di sicurezza intenzionale.');
            $this->error('   Completa la procedura di attivazione prima di procedere.');
            return 1;
        }

        // Guardia sui dischi di storage. Vale anche in --dry-run: con i root sbagliati
        // il dry-run mostrerebbe path falsi, dando per buona una cancellazione altrove.
        $diskRoots = [
            'unraid'       => config('filesystems.disks.unraid.root'),
            'unraid_bac_l' => config('filesystems.disks.unraid_bac_l.root'),
            'unraid_fin_l' => config('filesystems.disks.unraid_fin_l.root'),
        ];
        if (count(array_filter($diskRoots)) !== 3) {
            $this->error('❌ Dischi di storage non configurati: verificare filesystems.disks.{unraid,unraid_bac_l,unraid_fin_l}.root');
            return 1;
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — Nessuna modifica verrà eseguita.');
        } else {
            $this->warn('⚠️  LIVE MODE — Le modifiche sono PERMANENTI e IRREVERSIBILI.');
            if (! $this->confirm('Sei assolutamente sicuro di voler procedere?')) {
                $this->info('Operazione annullata.');
                return 0;
            }
        }

        $cutoff = now()->subDays(self::GRACE_PERIOD_DAYS);
        $this->info("Grace period: " . self::GRACE_PERIOD_DAYS . " giorni");
        $this->info("Cerco richieste anteriori al: {$cutoff->toDateTimeString()}");

        $orders = Order::where('deletion_requested_at', '<=', $cutoff)
            ->whereNull('deleted_at')                  // non già cancellati via SoftDelete
            ->whereNotNull('deletion_requested_at')
            ->get();

        $this->info("Trovati {$orders->count()} ordini da processare.");

        if ($orders->isEmpty()) {
            $this->info('✓ Nessuna cancellazione pendente.');
            return 0;
        }

        $this->table(
            ['Codice Ordine', 'Richiesta il', 'Giorni fa', 'Locked'],
            $orders->map(fn($o) => [
                $o->code,
                $o->deletion_requested_at->toDateTimeString(),
                $o->deletion_requested_at->diffInDays(now()),
                $o->is_locked ? 'SÌ (skip)' : 'No',
            ])
        );

        if ($dryRun) {
            $this->info('✓ Dry run completato. Nessuna modifica.');
            return 0;
        }

        $success = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($orders as $order) {
            // Salta ordini locked (MDR retention) — non devono mai essere cancellati
            if ($order->is_locked) {
                $this->warn("  SKIP (locked): {$order->code}");
                $skipped++;
                continue;
            }

            try {
                DB::beginTransaction();
                $this->anonymizeOrder($order);
                DB::commit();

                $success++;
                $this->info("  ✓ Processato: {$order->code}");

            } catch (\Throwable $e) {
                DB::rollBack();
                $errors++;
                $this->error("  ✗ Errore: {$order->code} — {$e->getMessage()}");

                Log::error('GDPR deletion failed', [
                    'order_code' => $order->code,
                    'error'      => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Riepilogo: {$success} processati, {$skipped} saltati (locked), {$errors} errori.");

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Anonimizza un singolo ordine: redacta patient_ref e note, rimuove file fisici.
     * Tutto dentro una transazione DB (chiamata dal caller).
     */
    private function anonymizeOrder(Order $order): void
    {
        Log::info('GDPR deletion started', [
            'order_code'              => $order->code,
            'patient_ref_hash'        => hash('sha256', $order->patient_ref . $order->id),
            'deletion_requested_at'   => $order->deletion_requested_at?->toDateTimeString(),
        ]);

        // 1. Rimuovi file fisici dalla directory dell'ordine
        $dirName = (string) $order->directory_name;

        if ($dirName !== '' && (str_contains($dirName, '/') || str_contains($dirName, '\\') || str_contains($dirName, '..'))) {
            throw new \RuntimeException("GDPR: directory_name non valido per l'ordine {$order->code}");
        }

        if ($dirName === '') {
            // L'ordine non ha una cartella fisica: niente da rimuovere.
            // L'anonimizzazione a database prosegue normalmente.
            Log::info('GDPR no directory', ['order_code' => $order->code]);
        } else {
            foreach (['unraid', 'unraid_bac_l', 'unraid_fin_l'] as $diskName) {
                $disk = Storage::disk($diskName);

                if (! $disk->directoryExists($dirName)) {
                    continue;
                }

                $disk->deleteDirectory($dirName);

                if ($disk->directoryExists($dirName)) {
                    throw new \RuntimeException("GDPR: cancellazione non riuscita sul disco {$diskName} per l'ordine {$order->code}");
                }

                Log::info('GDPR files deleted', ['order_code' => $order->code, 'disk' => $diskName]);
            }
        }

        // 2. Anonimizza campi sensibili
        $anonymizedRef = 'DELETED_' . substr(hash('sha256', $order->patient_ref . $order->id . now()), 0, 16);

        $order->update([
            'patient_ref'     => $anonymizedRef,
            'notes'           => null,
            'deletion_reason' => 'gdpr_right_to_deletion',
        ]);

        // 3. Soft-delete del record (mantiene audit trail minimo)
        $order->delete();

        Log::info('GDPR deletion completed', [
            'order_code'     => $order->code,
            'anonymized_ref' => $anonymizedRef,
            'deleted_at'     => now()->toDateTimeString(),
        ]);
    }
}

/*
 * ================================================================
 * TEST CASES — Eseguire PRIMA di attivare ENABLED=true
 * ================================================================
 *
 * STEP 1: Trova un ordine non-locked da usare come test
 *
 *   php artisan tinker
 *   >>> $o = App\Models\Order::where('is_locked', false)->first();
 *   >>> $o->code;       // annota il codice
 *   >>> $o->patient_ref; // annota il valore originale
 *
 * STEP 2: Simula richiesta di cancellazione vecchia (>30 giorni)
 *
 *   >>> $o->update(['deletion_requested_at' => now()->subDays(35), 'deletion_reason' => 'test']);
 *
 * STEP 3: Dry-run — SEMPRE sicuro
 *
 *   php artisan gdpr:process-deletions --dry-run
 *   // Deve mostrare l'ordine nella tabella senza modificarlo
 *
 * STEP 4: Verifica che l'ordine non sia stato toccato
 *
 *   >>> $o->fresh()->patient_ref;  // deve essere uguale a prima
 *
 * STEP 5: Live run (solo dopo aver cambiato ENABLED=true)
 *
 *   // ⚠️ Cambia const ENABLED = true; (temporaneamente per il test)
 *   php artisan gdpr:process-deletions
 *
 * STEP 6: Verifica post-cancellazione
 *
 *   >>> $o->fresh()->patient_ref;   // deve essere "DELETED_xxxxxxxxxxxx"
 *   >>> $o->fresh()->deleted_at;    // deve avere timestamp
 *   >>> $o->fresh()->deletion_reason; // "gdpr_right_to_deletion"
 *
 * STEP 7: Verifica log audit
 *
 *   tail -n 50 storage/logs/laravel.log | grep "GDPR deletion"
 *   // Deve mostrare: started, files deleted, completed
 *
 * STEP 8: RIMETTI ENABLED=false e non schedulare ancora
 *
 * ================================================================
 */
