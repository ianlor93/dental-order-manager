<?php

namespace App\Http\Controllers;

use App\Http\Requests\FunnelRegistrationRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Funnel di registrazione — scrittura/promote del lead (Tranche 2).
 *
 * Endpoint pubblico distinto dalla landing BFD (LeadController::store, intatta):
 * crea o promuove una riga in `leads` con source='funnel-registrazione' e
 * status='da_vagliare', pronta per il vaglio staff.
 *
 * NON fa provisioning Nextcloud e NON invia email — quello è la Tranche 3, dopo
 * aver predisposto l'account tecnico OCS. Qui created_user_uid resta null.
 *
 * Validazione e anti-abuso: vedi FunnelRegistrationRequest (stesso scudo di store()).
 * Risposta: sempre generica (anti-enumerazione), a prescindere dal ramo.
 */
class RegistrationController extends Controller
{
    private const SOURCE = 'funnel-registrazione';

    public function register(FunnelRegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Lock pessimistico sull'email (come store()): niente righe doppie su submit concorrenti.
        DB::transaction(function () use ($data) {
            $existing = Lead::where('email', $data['email'])->lockForUpdate()->first();

            // Caso 1 — email nuova: crea il lead funnel.
            if (!$existing) {
                $lead = new Lead();
                $lead->nome          = $data['nome'];
                $lead->email         = $data['email'];
                $lead->studio        = $data['studio'];
                $lead->piva          = $data['piva'];
                $lead->tipo_attivita = $data['tipo_attivita'];
                $lead->telefono      = $data['telefono'];
                $lead->source        = self::SOURCE;              // fillable, ma esplicito
                $lead->status        = Lead::STATUS_DA_VAGLIARE;  // guarded → set esplicito (fail-closed)
                // created_user_uid resta null: l'utente NC arriva in T3.
                $lead->save();
                return;
            }

            // Caso 2 — già cliente attivo (approvato): non declassare, nessuna modifica.
            // Endpoint pubblico → non lasciamo sovrascrivere i dati di un account esistente.
            if ($existing->status === Lead::STATUS_APPROVATO) {
                return;
            }

            // Caso 3 — esiste come nuovo (lead marketing) | da_vagliare (ri-submit) | rifiutato:
            // è la registrazione intenzionale col dato corrente → aggiorna i contatti,
            // porta a funnel-registrazione / da_vagliare.
            $existing->nome          = $data['nome'];
            $existing->studio        = $data['studio'];
            $existing->piva          = $data['piva'];
            $existing->tipo_attivita = $data['tipo_attivita'];
            $existing->telefono      = $data['telefono'];
            $existing->source        = self::SOURCE;
            // (email invariata: è la chiave di match)

            // Riapertura di un rifiutato: rientra nella coda di vaglio "pulito",
            // azzero la decisione di vaglio ormai superata (default policy: riapri).
            if ($existing->status === Lead::STATUS_RIFIUTATO) {
                $existing->rejection_reason = null;
                $existing->vetted_by_uid    = null;
                $existing->vetted_at        = null;
            }

            $existing->status = Lead::STATUS_DA_VAGLIARE;
            $existing->save();
        });

        // Sempre la stessa risposta: non rivelare se l'email era nuova/nota/cliente/rifiutata.
        return $request->genericSuccessResponse();
    }

    /**
     * Preflight CORS per il funnel — stesso schema di LeadController::preflight,
     * con la whitelist del funnel (FunnelRegistrationRequest::allowedOrigins()).
     */
    public function preflight(Request $request): JsonResponse
    {
        $origin = $request->header('Origin', '');
        if (!in_array($origin, FunnelRegistrationRequest::allowedOrigins(), true)) {
            return response()->json(null, 403);
        }

        return response()->json(null, 204)->withHeaders([
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age'       => '86400',
        ]);
    }
}
