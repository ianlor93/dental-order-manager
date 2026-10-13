<?php

namespace App\Http\Controllers;

use App\Mail\FunnelNewRegistrantNotification;
use App\Models\Lead;
use App\Services\FunnelVerifyTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Funnel — verifica email + set-password (Blocco 1), lato OM.
 *
 * Endpoint server-to-server chiamati dall'app NC `enamel_setpassword`. Decisione A:
 * il token (nel body) È la credenziale → nessuna sessione, nessun CSRF web. Risposte
 * SEMPRE generiche (anti-enumerazione). Il canale firmato del provisioning (ES256) NON
 * è coinvolto qui ed è intatto.
 */
class FunnelVerifyController extends Controller
{
    public function __construct(private FunnelVerifyTokenService $tokens)
    {
    }

    /**
     * POST /api/funnel/verify/validate {token}
     *
     * Token valido (non scaduto, non già verificato, utente NC creato) →
     * {ok:true, uid, displayName}; altrimenti {ok:false} generico.
     */
    public function validateToken(Request $request): JsonResponse
    {
        $plain = trim((string) $request->input('token', ''));
        if ($plain === '') {
            return response()->json(['ok' => false]);
        }

        $lead = Lead::where('verify_token', $this->tokens->hash($plain))
            ->where('verify_expires_at', '>', now())
            ->whereNull('email_verified_at')
            ->first();

        // Serve l'utente NC già creato: il set-password agisce su quell'uid.
        if (!$lead || !$lead->created_user_uid) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok'          => true,
            'uid'         => $lead->created_user_uid,
            'displayName' => $lead->nome ?: $lead->studio,
        ]);
    }

    /**
     * POST /api/funnel/verify/complete {token}
     *
     * Marca `email_verified_at`, azzera il token (single-use), notifica segreteria.
     * Idempotente: token già consumato/sconosciuto → {ok:true} senza effetti né re-invio
     * (non distinguibile da "già fatto" → anti-enumerazione).
     */
    public function complete(Request $request): JsonResponse
    {
        $plain = trim((string) $request->input('token', ''));
        if ($plain === '') {
            return response()->json(['ok' => true]); // generico, no-op
        }

        $hash = $this->tokens->hash($plain);

        // La notifica va inviata FUORI dalla transazione (niente lock durante l'SMTP) e
        // una sola volta: catturo il lead solo se ho davvero fatto la transizione.
        $verified = null;
        DB::transaction(function () use ($hash, &$verified) {
            $lead = Lead::where('verify_token', $hash)->lockForUpdate()->first();

            if ($lead
                && $lead->email_verified_at === null
                && $lead->verify_expires_at !== null
                && $lead->verify_expires_at->isFuture()
            ) {
                $lead->email_verified_at = now();   // guarded → set esplicito
                $lead->verify_token      = null;    // single-use
                $lead->verify_expires_at = null;
                $lead->save();
                $verified = $lead;
            }
        });

        // Avviso interno "nuovo iscritto verificato" — sincrono, best-effort, una volta.
        if ($verified !== null) {
            try {
                Mail::to(config('mail.internal_recipient'))
                    ->send(new FunnelNewRegistrantNotification($verified));
            } catch (\Throwable $e) {
                Log::error('EMAIL-ERROR: FunnelNewRegistrantNotification non inviata — ' . $e->getMessage());
            }
        }

        // Sempre generico/idempotente.
        return response()->json(['ok' => true]);
    }
}
