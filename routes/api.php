<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ProvisioningController;
use App\Http\Controllers\FunnelVerifyController;
use App\Http\Controllers\GateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Lead — pre-registrazione dalla landing page pubblica
Route::options('/leads', [LeadController::class, 'preflight']);
Route::post('/leads', [LeadController::class, 'store'])->middleware('throttle:5,60');

// Funnel di registrazione — scrittura/promote del lead (Tranche 2, senza OCS/mail).
// Stesso scudo anti-abuso di /leads (FunnelRegistrationRequest) + throttle per-route.
Route::options('/register', [RegistrationController::class, 'preflight']);
Route::post('/register', [RegistrationController::class, 'register'])
    ->middleware('throttle:5,60')
    ->name('api.funnel.register');

// Provisioning utenti NC (Tranche 3) — canale pull firmato: NC firma, OM verifica.
// Autenticazione per firma ES256 + claim act (NON i token SSO). Vedi ProvisioningController.
Route::get('/provisioning/pending', [ProvisioningController::class, 'pending'])
    ->middleware('throttle:60,1')
    ->name('api.provisioning.pending');
Route::post('/provisioning/ack', [ProvisioningController::class, 'ack'])
    ->middleware('throttle:60,1')
    ->name('api.provisioning.ack');

// T4b — abilitazione (pull degli approvati + ack attivazione). Stessa verifica firma,
// claim distinti (provision_enable_pull / provision_enable_ack).
Route::get('/provisioning/approved', [ProvisioningController::class, 'approved'])
    ->middleware('throttle:60,1')
    ->name('api.provisioning.approved');
Route::post('/provisioning/activated', [ProvisioningController::class, 'activated'])
    ->middleware('throttle:60,1')
    ->name('api.provisioning.activated');

// Blocco 1 — Verifica email + imposta password (funnel). Chiamati server-to-server
// dall'app NC `enamel_setpassword`: il token nel body È la credenziale (decisione A)
// → niente sessione/CSRF web. Risposte generiche. Distinti dal canale provisioning (JWT).
Route::post('/funnel/verify/validate', [FunnelVerifyController::class, 'validateToken'])
    ->middleware('throttle:30,1')
    ->name('api.funnel.verify.validate');
Route::post('/funnel/verify/complete', [FunnelVerifyController::class, 'complete'])
    ->middleware('throttle:30,1')
    ->name('api.funnel.verify.complete');

// Gate NC — stato onboarding per l'app Nextcloud `enamel_gate` (global middleware:
// containment della UI di NC per gli utenti funnel non onboardati). Server-to-server,
// autenticato per SEGRETO CONDIVISO (header Authorization: Bearer <secret>). Read-only,
// ritorna solo {applicable, onboarded}. Segreto non configurato ⇒ 401 ⇒ il gate NC va
// in FAIL-OPEN. Throttle ampio: il middleware NC cacha per-utente (TTL breve). Vedi GateController.
Route::post('/gate/onboarding-status', [GateController::class, 'onboardingStatus'])
    ->middleware('throttle:120,1')
    ->name('api.gate.onboarding_status');

// Rotta di default (opzionale, per ora la lasciamo commentata)
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
