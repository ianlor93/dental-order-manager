<?php

namespace App\Http\Controllers;

use App\Services\LegalComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gate NC — lato OM. Espone lo stato di onboarding all'app Nextcloud `enamel_gate`
 * (global middleware che fa containment della UI di NC per gli utenti funnel non
 * onboardati). Server-to-server, NESSUNA sessione.
 *
 * Autenticazione: SEGRETO CONDIVISO via header `Authorization: Bearer <secret>`
 * (config services.nextcloud_gate.shared_secret, da .env GATE_SHARED_SECRET).
 * Confronto a tempo costante. Segreto NON configurato ⇒ endpoint DISABILITATO
 * (rifiuta tutto) ⇒ l'app NC va in FAIL-OPEN (non blocca nessuno). Questo è
 * deliberato: finché Lorenzo non imposta il segreto, il gate è inerte.
 *
 * Ritorna SOLO booleani non-PII {applicable, onboarded}. Read-only, idempotente.
 * Distinto dal canale provisioning (quello usa firma ES256 + claim `act`).
 */
class GateController extends Controller
{
    public function __construct(private LegalComplianceService $compliance)
    {
    }

    /**
     * POST /api/gate/onboarding-status  body: {"uid": "<nc-uid>"}
     * → {"ok": true, "applicable": bool, "onboarded": bool}
     */
    public function onboardingStatus(Request $request): JsonResponse
    {
        if (!$this->authorized($request)) {
            return response()->json(['ok' => false], 401);
        }

        $uid = trim((string) $request->input('uid', ''));
        if ($uid === '') {
            return response()->json(['ok' => false, 'error' => 'bad_request'], 422);
        }

        $status = $this->compliance->status($uid);

        return response()->json([
            'ok'         => true,
            'applicable' => (bool) $status['applicable'],
            'onboarded'  => (bool) $status['onboarded'],
        ]);
    }

    /** Confronto a tempo costante col segreto condiviso. Segreto vuoto ⇒ disabilitato. */
    private function authorized(Request $request): bool
    {
        $expected = trim((string) config('services.nextcloud_gate.shared_secret', ''));
        if ($expected === '') {
            Log::warning('GATE-NC: shared secret non configurato — endpoint disabilitato.');
            return false;
        }

        $h = (string) $request->header('Authorization', '');
        if (stripos($h, 'Bearer ') !== 0) {
            return false;
        }
        $provided = trim(substr($h, 7));
        if ($provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}
