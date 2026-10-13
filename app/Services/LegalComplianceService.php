<?php

namespace App\Services;

use App\Models\User;
use App\Support\StudioCompliance;

/**
 * Predicato di conformità legale — SORGENTE espositiva (read-only) per il "Gate NC"
 * (app Nextcloud `enamel_gate`), interrogata via /api/gate/onboarding-status.
 * Nessun side-effect HTTP (niente redirect/sessione/403).
 *
 * Sia lo SCOPE (`applicable`) sia la conformità (`onboarded`) delegano alla SORGENTE
 * UNICA App\Support\StudioCompliance (isApplicable / resolveStudio / isCompliant),
 * condivisa col middleware EnsureLegalCompliance e col gate della lobby OM `/`
 * (WelcomeController). Niente predicato locale → niente drift.
 */
class LegalComplianceService
{
    /**
     * Stato onboarding per un uid Nextcloud.
     *
     * @return array{applicable: bool, onboarded: bool}
     *
     * applicable = StudioCompliance::isApplicable (non-staff con studio risolvibile O lead funnel).
     * onboarded  = StudioCompliance::isCompliant dello studio risolto (per la versione corrente).
     *   Quando applicable=false, onboarded è forzato a true (= "non bloccare").
     */
    public function status(string $uid): array
    {
        $pass = ['applicable' => false, 'onboarded' => true];

        $user = User::where('uid', $uid)->first();
        if (!$user || !StudioCompliance::isApplicable($user)) {
            return $pass;
        }

        return [
            'applicable' => true,
            'onboarded'  => StudioCompliance::isCompliant(StudioCompliance::resolveStudio($user)),
        ];
    }
}
