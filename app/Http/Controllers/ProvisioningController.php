<?php

namespace App\Http\Controllers;

use App\Mail\FunnelVerifyEmail;
use App\Mail\FunnelWelcomeEmail;
use App\Models\Lead;
use App\Services\FunnelVerifyTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OCA\External\Vendor\Firebase\JWT\JWT as FbJwt;
use OCA\External\Vendor\Firebase\JWT\Key as FbKey;

/**
 * Provisioning utenti NC — lato OM del canale "pull" firmato (funnel T3).
 *
 * Modello: NC firma (ha la privata), OM **verifica** con la chiave PUBBLICA della
 * coppia dedicata di `enamelprovisioning` (config services.nextcloud_provisioning).
 * OM non detiene segreti. RegistrationController/landing BFD: non toccati.
 *
 * Due endpoint server-to-server (nessuna sessione), entrambi autenticati per firma
 * ES256 + claim `act` atteso + `exp` breve. I token SSO del bridge non passano qui
 * (claim `act` diverso). Su qualsiasi anomalia: 401, nessun dato esposto, log redatto.
 */
class ProvisioningController extends Controller
{
    private const ACT_PULL = 'provision_pull';
    private const ACT_ACK  = 'provision_ack';
    private const ACT_ENABLE_PULL = 'provision_enable_pull';
    private const ACT_ENABLE_ACK  = 'provision_enable_ack';
    private const MAX_BATCH = 100;

    /**
     * GET /api/provisioning/pending — lead da provisionare.
     * Ritorna SOLO {email, displayName} dei lead `da_vagliare` senza utente NC.
     */
    public function pending(Request $request): JsonResponse
    {
        if (!$this->verify($request, self::ACT_PULL)) {
            return response()->json(['success' => false], 401);
        }

        // Include anche gli `approvato` ancora senza utente NC (fix race: se approvi
        // prima che il runner abbia creato l'utente, glielo crea comunque al giro dopo).
        $items = Lead::query()
            ->whereNull('created_user_uid')
            ->whereIn('status', [Lead::STATUS_DA_VAGLIARE, Lead::STATUS_APPROVATO])
            ->where('source', 'funnel-registrazione')
            ->orderBy('id')
            ->limit(self::MAX_BATCH)
            ->get(['email', 'nome', 'studio'])
            ->map(fn (Lead $l) => [
                'email'       => $l->email,
                'displayName' => $l->nome ?: $l->studio,
            ])
            ->all();

        return response()->json(['success' => true, 'items' => $items]);
    }

    /**
     * POST /api/provisioning/ack — NC comunica l'uid creato.
     * Scrive `created_user_uid` SOLO se ancora null (idempotente).
     */
    public function ack(Request $request): JsonResponse
    {
        if (!$this->verify($request, self::ACT_ACK)) {
            return response()->json(['success' => false], 401);
        }

        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $uid   = trim((string) $request->input('uid', ''));
        if ($email === '' || $uid === '') {
            return response()->json(['success' => false, 'error' => 'bad_request'], 422);
        }

        $updated = false;
        $lead = Lead::where('email', $email)->first();
        if ($lead && $lead->created_user_uid === null) {
            $lead->created_user_uid = $uid; // guarded → set esplicito (fail-closed)

            // Blocco 1 — al PRIMO ack (utente NC appena creato, transizione null→uid)
            // minta il token di verifica (hash+scadenza sul lead) e accoda la mail
            // "verifica email + imposta password". Solo qui: gli ack idempotenti
            // successivi hanno created_user_uid già valorizzato → non rientrano.
            $plainToken = (new FunnelVerifyTokenService())->mint($lead);
            $lead->save();
            $updated = true;

            // Mail SINCRONA best-effort (in prod nessun worker drena la coda): un errore
            // SMTP non deve far fallire l'ack, altrimenti l'uid resterebbe salvato senza
            // re-mint al giro dopo. Il runner è un cron → la latenza SMTP è accettabile.
            try {
                Mail::to($lead->email)->send(new FunnelVerifyEmail($lead, $plainToken));
            } catch (\Throwable $e) {
                Log::error('EMAIL-ERROR: FunnelVerifyEmail non inviata — ' . $e->getMessage());
            }
        }

        // Idempotente: già valorizzato o lead assente → ok comunque (il canale è firmato).
        return response()->json(['success' => true, 'updated' => $updated]);
    }

    /**
     * GET /api/provisioning/approved — utenti NC creati e APPROVATI, da abilitare (T4b).
     * Ritorna {uid, email, assigned_group} dei lead `approvato` con utente NC già
     * creato e non ancora abilitati (`activated_at` null).
     */
    public function approved(Request $request): JsonResponse
    {
        if (!$this->verify($request, self::ACT_ENABLE_PULL)) {
            return response()->json(['success' => false], 401);
        }

        $items = Lead::query()
            ->where('status', Lead::STATUS_APPROVATO)
            ->whereNotNull('created_user_uid')
            ->whereNull('activated_at')
            ->where('source', 'funnel-registrazione')
            ->orderBy('id')
            ->limit(self::MAX_BATCH)
            ->get(['email', 'created_user_uid', 'assigned_group'])
            ->map(fn (Lead $l) => [
                'uid'            => $l->created_user_uid,
                'email'          => $l->email,
                'assigned_group' => $l->assigned_group,
            ])
            ->all();

        return response()->json(['success' => true, 'items' => $items]);
    }

    /**
     * POST /api/provisioning/activated — NC conferma abilitazione completata (T4b).
     * Setta `activated_at` SOLO se ancora null (idempotente).
     */
    public function activated(Request $request): JsonResponse
    {
        if (!$this->verify($request, self::ACT_ENABLE_ACK)) {
            return response()->json(['success' => false], 401);
        }

        $uid = trim((string) $request->input('uid', ''));
        if ($uid === '') {
            return response()->json(['success' => false, 'error' => 'bad_request'], 422);
        }

        $updated = false;
        $lead = Lead::where('created_user_uid', $uid)->first();
        if ($lead && $lead->activated_at === null) {
            $lead->activated_at = now(); // guarded → set esplicito (fail-closed)
            $lead->save();
            $updated = true;

            // Mail di benvenuto "account attivo" — sincrona, best-effort, una sola volta
            // (transizione null→now). Sostituisce il reset stock NC del runner T4b: a questo
            // punto l'utente ha già impostato la password (Blocco 1), serve solo il benvenuto.
            try {
                Mail::to($lead->email)->send(new FunnelWelcomeEmail($lead));
            } catch (\Throwable $e) {
                Log::error('EMAIL-ERROR: FunnelWelcomeEmail non inviata — ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    /**
     * Verifica la firma ES256 del token (header Authorization: Bearer …),
     * il claim `act` atteso e che la finestra exp-iat sia breve. True se valido.
     */
    private function verify(Request $request, string $expectedAct): bool
    {
        $jwt = $this->bearerToken($request);
        if ($jwt === null) {
            return false;
        }

        $pubkeyB64 = trim((string) config('services.nextcloud_provisioning.jwt_pubkey_b64', ''));
        $pubkey = $pubkeyB64 !== '' ? (string) base64_decode($pubkeyB64, true) : '';
        if ($pubkey === '') {
            Log::error('PROVISIONING: chiave pubblica non configurata — endpoint disabilitato.');
            return false;
        }

        try {
            $this->ensureJwtLib();
            $decoded = FbJwt::decode($jwt, new FbKey($pubkey, 'ES256')); // verifica firma + exp/iat

            if (($decoded->act ?? null) !== $expectedAct) {
                return false; // token di altro scopo (es. SSO) → rifiutato
            }

            // exp deve esistere e la finestra exp-iat non superare max_ttl (anti-replay).
            $maxTtl = (int) config('services.nextcloud_provisioning.max_ttl', 120);
            $exp = $decoded->exp ?? null;
            $iat = $decoded->iat ?? null;
            if (!is_numeric($exp) || !is_numeric($iat) || ((int) $exp - (int) $iat) > $maxTtl) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // firma invalida / scaduta / malformata — redatto: niente token né payload nei log.
            Log::warning('PROVISIONING: token rifiutato (' . $e->getMessage() . ').');
            return false;
        }
    }

    private function bearerToken(Request $request): ?string
    {
        $h = (string) $request->header('Authorization', '');
        if (stripos($h, 'Bearer ') === 0) {
            $t = trim(substr($h, 7));
            return $t !== '' ? $t : null;
        }
        return null;
    }

    /** Autoloader on-demand per firebase/php-jwt bundlato (riusa il bundle del bridge). */
    private function ensureJwtLib(): void
    {
        if (class_exists(FbJwt::class, false)) {
            return;
        }
        spl_autoload_register(function (string $class): void {
            $prefix = 'OCA\\External\\Vendor\\Firebase\\JWT\\';
            if (strpos($class, $prefix) === 0) {
                $file = base_path('app/Support/Firebase/JWT/' . substr($class, strlen($prefix)) . '.php');
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });
    }
}
