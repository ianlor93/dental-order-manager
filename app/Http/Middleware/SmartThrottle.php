<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SmartThrottle — Rate limiting graduato per tier di operazione.
 *
 * Tier disponibili (req/minuto):
 *   read     → 300  (letture, bassa criticità)
 *   write    → 100  (scritture, media criticità)
 *   critical →  30  (operazioni irreversibili: delete, archive, gdpr)
 *
 * KILL SWITCH:
 *   Cambia $enabled = false per disabilitare istantaneamente.
 *   Oppure rimuovi ->middleware('throttle.smart:...') dalla route.
 *
 * AUMENTARE LIMITI SE NECESSARIO:
 *   Modifica l'array $limits. Effetto immediato (no cache clear).
 */
class SmartThrottle
{
    /**
     * Flag per disabilitare il throttling in emergenza.
     * Cambia in FALSE per bypass immediato — nessun cache clear necessario.
     */
    private bool $enabled = true;

    /**
     * Limiti per tier (richieste al minuto).
     * Partire ALTI e abbassare gradualmente monitorando i log.
     */
    private array $limits = [
        'read'     => 300,  // 5 req/sec — letture dashboard, download
        'write'    => 100,  // 1.6 req/sec — creazione ordini, produzione
        'critical' =>  30,  // 0.5 req/sec — delete, archive, gdpr deletion
    ];

    public function handle(Request $request, Closure $next, string $tier = 'read'): Response
    {
        if (! $this->enabled) {
            return $next($request);
        }

        $maxAttempts = $this->limits[$tier] ?? 60;
        $key = $this->buildKey($request, $tier);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            Log::warning('Rate limit superato', [
                'tier'        => $tier,
                'limit'       => $maxAttempts,
                'retry_after' => $retryAfter,
                'user_id'     => auth()->id() ?? 'guest',
                'ip'          => $request->ip(),
                'route'       => $request->path(),
                'method'      => $request->method(),
            ]);

            return response()->json([
                'error'       => 'Troppe richieste. Attendi prima di riprovare.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After'          => $retryAfter,
                'X-RateLimit-Limit'    => $maxAttempts,
                'X-RateLimit-Remaining'=> 0,
            ]);
        }

        RateLimiter::hit($key, 60); // decay 60 secondi

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
        ]);
    }

    /**
     * Chiave rate limit: user ID autenticato o indirizzo IP.
     * Preferisce user ID per non penalizzare utenti dietro lo stesso NAT.
     */
    private function buildKey(Request $request, string $tier): string
    {
        $identifier = auth()->check()
            ? 'user:' . auth()->id()
            : 'ip:' . $request->ip();

        return 'throttle:' . $tier . ':' . $identifier;
    }
}
