<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class PurgeOrder extends Command
{
    // Il nome del comando che userai nel terminale
    protected $signature = 'order:purge {code}';

    protected $description = 'Elimina forzatamente un ordine bypassando ogni blocco MDR';

    public function handle()
    {
        $code = $this->argument('code');
        
        // Cerchiamo l'ordine
        $order = Order::where('code', $code)->first();

        if (!$order) {
            $this->error("Errore: L'ordine con codice {$code} non esiste.");
            return;
        }

        $this->warn("Sto eliminando l'ordine {$code} (ID: {$order->id})...");

        try {
            // Bypassiamo la logica del modello usando una query diretta al DB
            // Questo ignora i booted() methods, il SoftDelete e il flag is_locked
            DB::table('oc_enamel_orders')->where('code', $code)->delete();

            $this->info("✅ Ordine {$code} rimosso definitivamente dal database.");
        } catch (\Exception $e) {
            $this->error("Errore durante l'eliminazione: " . $e->getMessage());
        }
    }
}