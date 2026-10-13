<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * ThrottleStats — Analizza gli eventi di rate limit dai log.
 *
 * Uso: php artisan throttle:stats
 */
class ThrottleStats extends Command
{
    protected $signature   = 'throttle:stats {--lines=500 : Quante righe di log analizzare}';
    protected $description = 'Mostra statistiche rate limit dagli ultimi N log';

    public function handle(): int
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            $this->error("File log non trovato: $logPath");
            return 1;
        }

        $maxLines = (int) $this->option('lines');
        $this->info("Analisi ultimi {$maxLines} eventi rate limit...");

        $lines = array_reverse(file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $lines = array_slice($lines, 0, $maxLines);

        $hitsByUser = [];
        $hitsByTier = [];
        $hitsByRoute = [];
        $totalHits   = 0;

        foreach ($lines as $line) {
            if (! str_contains($line, 'Rate limit superato')) {
                continue;
            }

            $totalHits++;

            if (preg_match('/"user_id":"([^"]+)"/', $line, $m)) {
                $hitsByUser[$m[1]] = ($hitsByUser[$m[1]] ?? 0) + 1;
            }
            if (preg_match('/"tier":"([^"]+)"/', $line, $m)) {
                $hitsByTier[$m[1]] = ($hitsByTier[$m[1]] ?? 0) + 1;
            }
            if (preg_match('/"route":"([^"]+)"/', $line, $m)) {
                $hitsByRoute[$m[1]] = ($hitsByRoute[$m[1]] ?? 0) + 1;
            }
        }

        if ($totalHits === 0) {
            $this->info('✓ Nessun evento rate limit trovato.');
            return 0;
        }

        $this->warn("Trovati {$totalHits} eventi di rate limit superato.");

        arsort($hitsByUser);
        arsort($hitsByTier);
        arsort($hitsByRoute);

        $this->newLine();
        $this->info('Per utente:');
        $this->table(['User ID', 'Hits'], array_map(fn($u, $c) => [$u, $c], array_keys($hitsByUser), $hitsByUser));

        $this->info('Per tier:');
        $this->table(['Tier', 'Hits'], array_map(fn($t, $c) => [$t, $c], array_keys($hitsByTier), $hitsByTier));

        $this->info('Per route:');
        $this->table(['Route', 'Hits'], array_map(fn($r, $c) => [$r, $c], array_keys($hitsByRoute), $hitsByRoute));

        return 0;
    }
}
