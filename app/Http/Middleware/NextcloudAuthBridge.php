<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use OCA\External\Vendor\Firebase\JWT\JWT as FbJwt;
use OCA\External\Vendor\Firebase\JWT\Key as FbKey;

class NextcloudAuthBridge
{
    /**
     * Chiave PUBBLICA ES256 dell'app Nextcloud "External Sites"
     * (oc_appconfig external/jwt_token_pubkey_es256). È PUBBLICA, NON è un segreto:
     * NC firma con la privata, qui validiamo con la pubblica.
     * Si configura con NEXTCLOUD_SSO_PUBKEY_B64 (PEM in base64 su una riga).
     * Vuota ⇒ nessun login via handshake.
     */
    private function jwtPubkey(): string
    {
        $b64 = trim((string) config('services.nextcloud.sso_pubkey_b64', ''));
        return $b64 !== '' ? (string) base64_decode($b64, true) : '';
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->ensureJwtLib();

            // Identità dall'handshake: JWT firmato da Nextcloud.
            $hsUid = $this->resolveHandshakeUid($request);

            // 1. Già autenticato: prosegui — MA se arriva un handshake valido per un uid
            //    DIVERSO, ri-aggancia l'identità (no riuso sessione altrui).
            if (Auth::check()) {
                if ($hsUid !== null && $hsUid !== Auth::user()->uid) {
                    Log::warning("MDR-BRIDGE: cambio identità rilevato, ri-autenticazione.");
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    // prosegue al blocco 2 (re-login come nuovo utente)
                } else {
                    return $next($request);
                }
            }

            // 2. Login da handshake
            if ($hsUid !== null) {
                $user = User::where('uid', $hsUid)->first();
                if ($user) {
                    /**
                     * 🚩 Auth::login($user) SENZA 'true': oc_users non ha 'remember_token',
                     * il remember-me causa crash della sessione + blocco IP.
                     */
                    Auth::login($user);

                    if ($request->hasSession()) {
                        $request->session()->regenerate();
                        $request->session()->put('nc_uid', $hsUid);
                    }

                    Log::info("MDR-BRIDGE: Login automatico riuscito.");

                    // Pulisce l'URL dai parametri sensibili e reindirizza
                    return redirect($request->fullUrlWithQuery(['key' => null, 'user' => null, 'jwt' => null]));
                }
            }

            /**
             * 3. PARACADUTE: non autenticato e senza handshake valido → torna su Nextcloud.
             */
            if (!Auth::check() && !$request->is('login')) {
                Log::warning("MDR-BRIDGE: Accesso negato, utente non autenticato. Redirect a Nextcloud.");
                $ncUrl = (string) config('services.nextcloud.url');
                if ($ncUrl === '') {
                    abort(500, 'NEXTCLOUD_URL non configurato');
                }
                return redirect($ncUrl);
            }

            return $next($request);

        } catch (\Throwable $e) {
            Log::emergency("MDR-BRIDGE-CRASH: " . $e->getMessage());
            return response("Errore critico di autenticazione bridge.", 500);
        }
    }

    /**
     * Ritorna l'uid se l'handshake è valido, altrimenti null.
     * JWT firmato ES256 di External Sites: uid DENTRO `userdata`, firma + scadenza
     * verificate dalla libreria.
     */
    private function resolveHandshakeUid(Request $request): ?string
    {
        // (a) JWT firmato
        $jwtRaw = $request->query('jwt');
        if (is_string($jwtRaw) && $jwtRaw !== '') {
            $pubkey = $this->jwtPubkey();
            if ($pubkey === '') {
                Log::error("MDR-BRIDGE: chiave pubblica SSO non configurata (NEXTCLOUD_SSO_PUBKEY_B64) — login via handshake disabilitato.");
                return null;
            }
            try {
                $decoded = FbJwt::decode($jwtRaw, new FbKey($pubkey, 'ES256'));
                $uid = $decoded->userdata->uid ?? null;
                if (is_string($uid) && $uid !== '') {
                    return $uid;
                }
                Log::warning("MDR-BRIDGE: JWT valido ma uid mancante nel claim.");
            } catch (\Throwable $e) {
                Log::warning("MDR-BRIDGE: JWT non valido (" . $e->getMessage() . ").");
            }
        }

        // Chiavi statiche ELIMINATE (Fase 3, 2026-06-14): handshake SOLO via JWT firmato.
        return null;
    }

    /** Autoloader on-demand per firebase/php-jwt bundlato (namespace OCA\External\Vendor\Firebase\JWT). */
    private function ensureJwtLib(): void
    {
        if (class_exists(FbJwt::class, false)) {
            return;
        }
        spl_autoload_register(function (string $class): void {
            $prefix = 'OCA\\External\\Vendor\\Firebase\\JWT\\';
            if (strpos($class, $prefix) === 0) {
                $file = __DIR__ . '/../../Support/Firebase/JWT/' . substr($class, strlen($prefix)) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });
    }
}
