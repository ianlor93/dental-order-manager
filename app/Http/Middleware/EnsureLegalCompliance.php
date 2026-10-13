<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\StudioUser;
use App\Support\StudioCompliance;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegalCompliance
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = Auth::user();

            // 1. Se non c'è utente, lasciamo che il Bridge gestisca o rimandiamo alla dashboard (che attiverà il bridge)
            if (!$user) {
                return $next($request);
            }

            // 🔓 ECCEZIONE STAFF: Forza il passaggio senza guardare altro
            // Usiamo un controllo aggiuntivo sull'UID admin per sicurezza estrema
            if (\App\Support\AccessContext::for($user)->isStaff() || $user->uid === 'admin' || \App\Support\AccessContext::for($user)->isAgente()) {
                return $next($request);
            }

            // 🔄 PREVENZIONE LOOP
            if ($request->is('onboarding*') || $request->is('legal/*') || $request->routeIs('legal.*')) {
                return $next($request);
            }

            // 🕵️ VERIFICA REQUISITI (Defensive version)
            $currentLegalVersion = config('legal.version', '1.0');

            // --- Risoluzione Studio ---
            // Caso 1: l'utente è titolare (studios.user_id = uid)
            $studio = \App\Models\Studio::where('user_id', $user->uid)->first();

            // Caso 2: l'utente non è titolare ma è membro attivo di un altro studio (multi-prescrittore)
            // I prescrittori operano sotto il DPA firmato dal titolare dello studio.
            // La compliance legale viene verificata sullo studio, non sul singolo prescrittore.
            if (!$studio) {
                $membership = StudioUser::where('user_uid', $user->uid)
                    ->where('is_active', true)
                    ->with('studio')
                    ->first();

                if ($membership && $membership->studio) {
                    $studio = $membership->studio;
                    // Memorizza in sessione per non riquotare ad ogni request
                    session(['active_studio_id' => $studio->id]);
                }
            }

            // Nessuno studio trovato: utente nuovo → onboarding
            if (!$studio) {
                return $this->redirectOnboarding($request, $user);
            }

            // Il prescrittore membro non deve completare l'onboarding in autonomia:
            // se lo studio esiste ed è compliant, lo lasciamo passare.
            // Se lo studio non è compliant, il titolare deve completare l'onboarding.
            // Mostriamo un messaggio dedicato invece del redirect al form di firma.
            $isMemberNotOwner = !\App\Support\AccessContext::for($user)->isStudioOwner($studio);

            // ⚖️ PREDICATO DI CONFORMITÀ — SORGENTE UNICA (App\Support\StudioCompliance::isCompliant),
            // condivisa con LegalComplianceService (gate NC). Qui restano INVARIATI i side-effect HTTP:
            // redirect onboarding (titolare) / 403 (prescrittore membro) / anti-loop + bypass-staff (sopra).
            if (!StudioCompliance::isCompliant($studio, $currentLegalVersion)) {
                // Il prescrittore non può completare l'onboarding per conto del titolare
                if ($isMemberNotOwner) {
                    Log::warning("MDR-COMPLIANCE: Prescrittore {$user->uid} bloccato — studio {$studio->id} non compliant per la versione {$currentLegalVersion}.");
                    return response("Il titolare dello studio deve completare la configurazione prima che i prescrittori possano accedere alla piattaforma.", 403);
                }

                return $this->redirectOnboarding($request, $user);
            }

            return $next($request);

        } catch (\Throwable $e) {
            Log::emergency("MDR-COMPLIANCE-CRASH: " . $e->getMessage());
            // In caso di errore critico nel middleware, se è admin, fammi passare comunque
            if (Auth::user()?->uid === 'admin') return $next($request);
            return response("Errore verifica conformità.", 500);
        }
    }

    private function redirectOnboarding($request, $user) {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false, 
                'error' => 'Onboarding necessario.',
                'redirect' => route('legal.onboarding')
            ], 403);
        }
        // Fase 3 (2026-06-14): chiave statica ELIMINATA dal redirect — il bridge è JWT-only,
        // non legge piu' ?key=/?user=. Lasciare la chiave qui = leak in URL/log/history (C5).
        return redirect()->route('legal.onboarding', ['user' => $user->uid]);
    }
}