<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestClamAV extends Command
{
    // Il nome del comando da lanciare
    protected $signature = 'app:test-clamav';
    protected $description = 'Verifica la connessione TCP con il container ClamAV';

    public function handle()
    {
        $host = env('CLAMAV_HOST', 'clamav');
        $port = env('CLAMAV_PORT', 3310);

        $this->info("Tentativo di connessione a ClamAV su {$host}:{$port}...");

        try {
            // Tentativo di apertura socket TCP [cite: 238]
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);

            if (!$socket) {
                $this->error("❌ ERRORE: Impossibile connettersi a ClamAV.");
                $this->line("Dettaglio: {$errstr} ({$errno})");
                $this->comment("\nSuggerimento: Controlla che il Docker di ClamAV sia avviato e che la porta {$port} sia aperta su Unraid.");
                return 1;
            }

            // Inviamo il comando PING (standard ClamAV)
            fwrite($socket, "nPING\n");
            $response = trim(fgets($socket));
            fclose($socket);

            if ($response === 'PONG') {
                $this->info("✅ SUCCESS: ClamAV ha risposto PONG! La connessione è attiva.");
                return 0;
            } else {
                $this->warn("⚠️ Risposta inattesa da ClamAV: {$response}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ ECCEZIONE: " . $e->getMessage());
            return 1;
        }
    }
}