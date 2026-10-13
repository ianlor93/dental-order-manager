<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MdrCatalog;
use Illuminate\Support\Facades\File;

class SyncMdrCatalog extends Command
{
    protected $signature = 'mdr:sync';
    protected $description = 'Sincronizza il catalogo MDR dal file CSV';

    public function handle()
    {
        // Puntiamo al percorso fisico assoluto nel container
        $filePath = storage_path('app/mdr_catalog.csv');

        // Debug per console: verifichiamo se il file esiste davvero per PHP
        if (!File::exists($filePath)) {
            $this->error("ERRORE: File NON trovato nel percorso fisico: " . $filePath);
            $this->warn("Assicurati che il file si trovi esattamente in: storage/app/mdr_catalog.csv");
            return;
        }

        $this->info("File trovato. Inizio elaborazione...");

        $file = fopen($filePath, 'r');
        
        // Leggiamo l'intestazione
        $header = fgetcsv($file, 0, ';'); 

        if (!$header) {
            $this->error("Errore: Il file CSV sembra vuoto o malformato.");
            return;
        }

        $count = 0;
        $updated = 0;

        while (($row = fgetcsv($file, 0, ';')) !== FALSE) {
            // Verifichiamo che la riga abbia lo stesso numero di colonne dell'header
            if (count($header) !== count($row)) {
                $this->warn("Salto riga " . ($count + 1) . ": numero di colonne non corrispondente.");
                continue;
            }

            $data = array_combine($header, $row);
            
            MdrCatalog::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category'       => $data['category'],
                    'name'           => $data['name'],
                    'duration_use'   => $data['duration_use'] ?? null,
                    'is_implantable' => isset($data['is_implantable']) ? (bool)(int)$data['is_implantable'] : false,
                    'risk_class'     => $data['risk_class'],
                    'mdr_rule'       => $data['mdr_rule'],
                    'pms_years'      => (int)$data['pms_years'],
                    'is_active'      => true,
                ]
            );
            $updated++;
            $count++;
        }

        fclose($file);
        $this->info("SUCCESSO: Sincronizzazione completata.");
        $this->info("Righe processate: {$count}");
        $this->info("Dispositivi in catalogo: {$updated}");
    }
}