<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\LegalAcceptance;
use App\Models\Studio;
use App\Models\StudioUser;
use App\Models\User;

/**
 * Predicato di conformità legale di uno studio — SORGENTE UNICA.
 *
 * Estratto 1:1 (Passo 4c-①, 2026-06-24) dal predicato che era duplicato verbatim in
 * App\Http\Middleware\EnsureLegalCompliance::handle() e
 * App\Services\LegalComplianceService::isOnboarded(). Entrambi ora delegano qui: il "DATO"
 * (gate OM, middleware) e il "containment" della UI NC (gate NC, service read-only)
 * condividono lo STESSO predicato e non possono più divergere.
 *
 * PURO: nessun side-effect (niente sessione/redirect/403/log). Riceve uno studio GIÀ
 * risolto (titolare o membro) + la versione legale corrente; ritorna solo il booleano.
 * La risoluzione studio e i side-effect HTTP restano nei rispettivi chiamanti.
 *
 * (Passo 4c-③b, 2026-06-25) Aggiunti i predicati di SCOPE condivisi tra gate NC
 * (LegalComplianceService) e gate della lobby OM `/` (WelcomeController): risoluzione studio,
 * rilevazione lead funnel, e `isApplicable`. Un'unica nozione di "chi è in scope" + "chi è
 * conforme" → niente drift (stesso principio di `isCompliant` in 4c-①).
 */
class StudioCompliance
{
    /**
     * Lo studio è conforme PER LA VERSIONE indicata?
     *
     * @param  Studio|null  $studio   studio già risolto (null ⇒ non conforme: serve onboarding)
     * @param  string|null  $version  versione di riferimento; default config('legal.version')
     */
    public static function isCompliant(?Studio $studio, ?string $version = null): bool
    {
        if (!$studio) {
            return false;
        }

        $version = $version ?? config('legal.version', '1.0');

        // Controllo rapido sui campi denormalizzati dello studio.
        $incomplete = empty($studio->vat_number) ||
                      empty($studio->dentist_license) ||
                      $studio->tos_accepted_at === null ||
                      $studio->privacy_accepted_at === null ||
                      $studio->mdr_accepted_at === null ||
                      $studio->dpa_accepted_at === null ||   // GDPR Art. 28 — DPA obbligatorio
                      $studio->legal_version !== $version ||
                      $studio->addresses()->count() === 0;

        if ($incomplete) {
            return false;
        }

        // Record granulari per studi con versione >= 2026.3 (onboarding 4-step).
        if (version_compare($studio->legal_version ?? '0', '2026.3', '>=')) {
            $hasGranular = LegalAcceptance::hasAccepted($studio->id, 'tos', $version)
                && LegalAcceptance::hasAccepted($studio->id, 'vessatorie', $version)
                && LegalAcceptance::hasAccepted($studio->id, 'privacy', $version)
                && LegalAcceptance::hasAccepted($studio->id, 'dpa', $version);

            if (!$hasGranular) {
                return false;
            }
        }

        return true;
    }

    /**
     * Il gate (NC e lobby OM `/`) DEVE applicarsi a questo utente?
     *
     * true = NON-staff con uno studio risolvibile OPPURE con un lead del funnel (utente
     * funnel "fresco", ancora senza studio — lo crea il wizard allo step 4: opzione 2).
     * Safeguard: non-staff SENZA studio E SENZA lead → false (non gatato, niente dead-end).
     * Staff/admin → false (mai gatati).
     */
    public static function isApplicable(User $user): bool
    {
        if (\App\Support\AccessContext::for($user)->isStaff() || $user->uid === 'admin' || \App\Support\AccessContext::for($user)->isAgente()) {
            return false;
        }

        return self::resolveStudio($user) !== null || self::hasFunnelLead($user);
    }

    /**
     * Risoluzione studio dell'utente: titolare (studios.user_id), poi membro attivo via
     * pivot oc_enamel_studio_users. Read-only, nessun side-effect (a differenza del
     * middleware, che scrive active_studio_id in sessione). null se nessuno studio.
     *
     * Spostata qui da LegalComplianceService (4c-③a) per essere SORGENTE UNICA della
     * risoluzione condivisa da isApplicable + LegalComplianceService + gate lobby `/`.
     */
    public static function resolveStudio(User $user): ?Studio
    {
        $studio = Studio::where('user_id', $user->uid)->first();
        if (!$studio) {
            $membership = StudioUser::where('user_uid', $user->uid)
                ->where('is_active', true)
                ->with('studio')
                ->first();
            if ($membership && $membership->studio) {
                $studio = $membership->studio;
            }
        }

        return $studio;
    }

    /** L'utente è un iscritto del funnel? (lead source=funnel-registrazione legato all'uid) */
    public static function hasFunnelLead(User $user): bool
    {
        return Lead::where('created_user_uid', $user->uid)
            ->where('source', 'funnel-registrazione')
            ->exists();
    }
}
