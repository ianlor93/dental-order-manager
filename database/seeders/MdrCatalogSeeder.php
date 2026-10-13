<?php

namespace Database\Seeders;

use App\Models\MdrCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MdrCatalogSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * ✨ FIX PERCORSO: Puntiamo alla cartella storage dove si trova il file su Unraid
         * Laravel risolverà storage_path('app/...') in /app/storage/app/...
         */
        $path = storage_path('app/mdr_catalog.csv');
        
        if (!File::exists($path)) {
            $this->command->error("ERRORE CRITICO: File CSV non trovato in: {$path}");
            $this->command->warn("Verifica che il file si chiami esattamente 'mdr_catalog.csv' e sia dentro storage/app/");
            return;
        }

        $lines = file($path);
        // Estraiamo l'intestazione e determiniamo il separatore (punto e virgola come nel tuo file)
        $header = str_getcsv(array_shift($lines), ';');

        foreach ($lines as $line) {
            $row = str_getcsv($line, ';');
            
            // Saltiamo righe vuote o malformate (il file deve avere almeno 7 colonne)
            if (count($row) < 7) continue;

            $data = array_combine($header, $row);

            /**
             * Sincronizzazione Intelligente:
             * Cerchiamo per 'slug' (che è l'ID unico del prodotto).
             * Se esiste, aggiorna i dati (inclusa la nuova duration_use).
             * Se non esiste, crea una nuova riga.
             */
            MdrCatalog::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category'     => $data['category'],
                    'name'         => $data['name'],
                    'duration_use' => $data['duration_use'], // Allineato all'Allegato VIII MDR
                    'risk_class'   => $data['risk_class'],
                    'mdr_rule'     => $data['mdr_rule'],
                    'pms_years'    => $data['pms_years'],
                    'is_active'    => $data['is_active'] ?? 1,
                ]
            );
        }
        
        $this->command->info("✅ Catalogo MDR sincronizzato con successo dal volume storage!");
    }
}