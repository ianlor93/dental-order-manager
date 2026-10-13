<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studio;
use App\Core\TierManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ResetStudioCredits extends Command
{
    // Manteniamo la firma ibex:reset-credits come da tua richiesta
    protected $signature = 'ibex:reset-credits';
    protected $description = 'Resetta i crediti mensili in base al Tier dal CSV e aggiorna la quota Nextcloud';

    public function handle()
    {
        Log::info("SCHEDULER: Inizio reset crediti mensili...");

        // Cerchiamo gli studi con abbonamento attivo che scade oggi o è già scaduto
        // Nota: Nel webhook la scadenza viene impostata a +1 o +12 mesi. 
        // Questo comando serve per il rinnovo della quota crediti omaggio ogni 30gg.
        $studios = Studio::where('subscription_expires_at', '<=', Carbon::now())
            ->whereNotNull('subscription_expires_at')
            ->get();

        foreach ($studios as $studio) {
            $conf = TierManager::getTierConfig($studio->tier_level);
            
            if (!$conf) {
                Log::warning("SCHEDULER: Configurazione non trovata per Tier {$studio->tier_level} (Studio: {$studio->name})");
                continue;
            }

            // 1. Ricarichiamo i crediti regalo dal CSV
            $studio->credits_gift = (int)($conf->gift_credits ?? 0);
            
            // 2. Spostiamo la scadenza di altri 30 giorni (Ciclo mensile)
            $studio->subscription_expires_at = Carbon::parse($studio->subscription_expires_at)->addDays(30);
            $studio->save();

            // 3. Sincronizzazione Quota Nextcloud (Conversione GB -> Bytes)
            if ($studio->user_id && isset($conf->storage_gb)) {
                $bytes = (int)$conf->storage_gb * 1024 * 1024 * 1024;
                DB::table('oc_preferences')->updateOrInsert(
                    ['userid' => $studio->user_id, 'appid' => 'files', 'configkey' => 'quota'],
                    ['configvalue' => $bytes]
                );
            }

            Log::info("SCHEDULER: Reset mensile eseguito per {$studio->name} (Tier {$studio->tier_level})");
        }

        $this->info('Reset completato con successo.');
    }
}