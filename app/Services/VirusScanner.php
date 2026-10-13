<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class VirusScanner
{
    protected $host;
    protected $port;
    protected $timeout;

    public function __construct()
    {
        $this->host = env('CLAMAV_HOST', 'clamav'); 
        $this->port = env('CLAMAV_PORT', 3310);
        $this->timeout = 600; // 10 minuti per file pesanti
    }

    public function scan(string $relativePath): string
    {
        $fullPath = storage_path('app/private/' . $relativePath);

        if (!file_exists($fullPath)) {
            Log::error("MDR-SCAN: File non trovato in {$fullPath}");
            return 'error_missing';
        }

        $socket = null;
        try {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
            
            if (!$socket) {
                Log::warning("MDR-SCAN: ClamAV non raggiungibile su {$this->host}.");
                return 'bypass'; 
            }

            stream_set_timeout($socket, $this->timeout);
            fwrite($socket, "zINSTREAM\0");
            
            $handle = fopen($fullPath, "rb");
            while (!feof($handle)) {
                $chunk = fread($handle, 32768); // Buffer 32KB
                if ($chunk !== false && strlen($chunk) > 0) {
                    $sent = @fwrite($socket, pack('N', strlen($chunk)) . $chunk);
                    if ($sent === false) throw new Exception("Broken Pipe durante l'invio a ClamAV.");
                }
            }
            fwrite($socket, pack('N', 0)); 
            fclose($handle);
            
            $response = fgets($socket);
            fclose($socket);

            if (str_contains($response, 'OK')) return 'clean';
            if (str_contains($response, 'FOUND')) return 'infected';
            
            return 'bypass';

        } catch (Exception $e) {
            if (isset($socket) && is_resource($socket)) fclose($socket);
            Log::error("MDR-SCAN-FATAL: " . $e->getMessage());
            return 'scan_error'; // Sblocca il PDF in caso di errore scanner
        }
    }
}