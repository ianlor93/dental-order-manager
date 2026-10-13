<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    OrderController,
    DashboardController,
    OrderProductionController,
    LegalController,
    ProfileController,
    AddressController,
    OrderCommentController,
    BillingController,
    WelcomeController,
    LogisticsController,
    StudioController,
    GdprController,
    ComplianceController,
    AmministrazioneController,
};
use App\Http\Middleware\NextcloudAuthBridge;
use Illuminate\Support\Facades\Auth;

// --- ROTTE LEGALI PUBBLICHE ---
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/note-legali', 'legal.note-legali')->name('legal.note-legali');
Route::redirect('/terms', '/note-legali', 301);
Route::view('/cookie', 'legal.cookie')->name('legal.cookie');
// /dpa rimossa: il DPA è documento dei servizi (wizard onboarding), non più pagina pubblica.

// --- ROTTE COMPLIANCE PUBBLICHE (nessuna autenticazione) ---
Route::get('/compliance/subprocessors', [ComplianceController::class, 'subprocessorsHtml'])->name('compliance.subprocessors');
Route::get('/compliance/subprocessors.json', [ComplianceController::class, 'subprocessorsJson'])->name('compliance.subprocessors.json');

Route::get('/debug-auth', function () {
    if (!Auth::user()?->isStaff()) abort(403);
    return [
        'check' => Auth::check(),
        'user'  => Auth::user()?->uid,
        'studio' => Auth::user()?->studio?->id,
        'tier' => Auth::user()?->studio?->tier_level,
        'is_staff' => Auth::user()?->isStaff(),
        'session_id' => session()->getId(),
    ];
})->middleware([NextcloudAuthBridge::class]);

Route::middleware([NextcloudAuthBridge::class])->group(function () {
    Route::get('/onboarding', [LegalController::class, 'onboarding'])->name('legal.onboarding');
    Route::post('/onboarding', [LegalController::class, 'store'])->name('legal.store');
    // Revisione read-only di un documento di un passo già firmato (nessun side-effect)
    Route::get('/onboarding/review/{step}', [LegalController::class, 'review'])->whereNumber('step')->name('legal.review');

    // ✨ WELCOME PAGE (LOBBY)
    Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

    // 🔒 GRUPPO PROTETTO DA LEGAL GATE (Richiede firma documenti)
    Route::middleware(['legal.gate'])->group(function () {
        
        // DASHBOARD
        Route::get('/lab', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/archive', [DashboardController::class, 'archive'])->name('dashboard.archive');
        
        // ✨ ANAGRAFICA CLIENTI
        Route::get('/customers', [StudioController::class, 'index'])->name('customers.index');

        // Amministrazione (lead, ticket assistenza)
        Route::get('/amministrazione', [AmministrazioneController::class, 'index'])->name('amministrazione.index');
        Route::post('/amministrazione/lead/{lead}/approve', [AmministrazioneController::class, 'approveLead'])->name('amministrazione.lead.approve');
        Route::post('/amministrazione/lead/{lead}/reject', [AmministrazioneController::class, 'rejectLead'])->name('amministrazione.lead.reject');
        Route::delete('/amministrazione/lead/{lead}', [AmministrazioneController::class, 'destroyLead'])->name('amministrazione.lead.destroy');
        Route::patch('/amministrazione/supporto/{supportRequest}/status', [AmministrazioneController::class, 'updateSupportStatus'])->name('amministrazione.supporto.status');
        Route::delete('/amministrazione/supporto/{supportRequest}', [AmministrazioneController::class, 'destroySupportRequest'])->name('amministrazione.supporto.destroy');

        // Nuovo ticket assistenza (solo staff)
        Route::post('/amministrazione/supporto', [AmministrazioneController::class, 'storeSupportRequest'])->name('amministrazione.supporto.store');
        Route::post('/studios/{studio}/update-billing', [StudioController::class, 'updateBilling'])->name('studios.updateBilling');

        // ✨ AREA LOGISTICA (SPEDIZIONI)
        Route::get('/logistics', [LogisticsController::class, 'index'])->name('logistics.index');
        Route::post('/logistics', [LogisticsController::class, 'store'])->name('logistics.store');
        Route::get('/logistics/download/{deliveryNote}', [LogisticsController::class, 'download'])->name('logistics.download');

        // AREA CONTABILE (AMMINISTRAZIONE)
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('index');
            Route::get('/archive', [BillingController::class, 'archive'])->name('archive');
            Route::post('/summary', [BillingController::class, 'downloadMonthlySummary'])->name('summary');
            Route::post('/mark-billed', [BillingController::class, 'markAsBilled'])->name('markBilled');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::get('/contract/{type}', [ProfileController::class, 'downloadContract'])->name('contract');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::post('/deletion-request', [ProfileController::class, 'requestDeletion'])->middleware('throttle.smart:critical')->name('deletion-request');
        });

        // GDPR — Diritti degli Interessati
        Route::get('/gdpr/export', [GdprController::class, 'export'])
            ->middleware('throttle.smart:critical')
            ->name('gdpr.export');

        Route::post('/gdpr/opposition', [GdprController::class, 'storeOpposition'])
            ->middleware('throttle.smart:critical')
            ->name('gdpr.opposition');

        // GDPR Art. 15 (Accesso) e Art. 16 (Rettifica) — richieste formali con audit trail
        Route::post('/gdpr/access-request', [GdprController::class, 'storeAccessRequest'])
            ->middleware('throttle.smart:critical')
            ->name('gdpr.access');

        Route::post('/gdpr/rectification-request', [GdprController::class, 'storeRectificationRequest'])
            ->middleware('throttle.smart:critical')
            ->name('gdpr.rectification');

        Route::post('/gdpr/restriction-request', [GdprController::class, 'storeRestrictionRequest'])
            ->middleware('throttle.smart:critical')
            ->name('gdpr.restriction');

        Route::prefix('addresses')->name('addresses.')->group(function () {
            Route::post('/', [AddressController::class, 'store'])->name('store');
            Route::delete('/{address}', [AddressController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/create', [OrderController::class, 'create'])->name('create');
            Route::post('/', [OrderController::class, 'store'])->middleware('throttle.smart:write')->name('store');
            Route::match(['get', 'post'], '/{order}/upload-chunk', [OrderController::class, 'uploadChunk'])->name('chunk');
            Route::match(['get', 'post'], '/{order}/upload-supplemental', [OrderController::class, 'uploadSupplementalChunk'])->name('supplemental');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->middleware('throttle.smart:critical')->name('destroy');

            Route::get('/{order}/comments', [OrderCommentController::class, 'index'])->name('comments.index');
            Route::post('/{order}/comments', [OrderCommentController::class, 'store'])->name('comments.store');

            Route::get('/{order}/download', [OrderController::class, 'downloadZip'])->name('downloadZip');
            Route::get('/{order}/download-request-pdf', [OrderController::class, 'downloadRequestPdf'])->name('downloadRequestPdf');
            Route::get('/{order}/download-prescription', [OrderProductionController::class, 'downloadPrescription'])->name('downloadPrescription');
            Route::get('/{order}/download-label', [OrderProductionController::class, 'downloadLabel'])->name('downloadLabel');
            Route::get('/{order}/download-conformity', [OrderProductionController::class, 'downloadConformity'])->name('downloadConformity');
            Route::get('/{order}/download-production-report', [OrderProductionController::class, 'downloadProductionReport'])->name('downloadProductionReport');
            Route::get('/{order}/download-patient-info', [OrderProductionController::class, 'downloadPatientInfo'])->name('downloadPatientInfo');
            Route::get('/{order}/download-pms-report', [DashboardController::class, 'downloadPmsReport'])->name('downloadPmsReport');

            Route::get('/{order}/prescription-preview', [OrderProductionController::class, 'previewPrescription'])->name('prescription.preview');
            Route::post('/{order}/validate-prescription', [OrderProductionController::class, 'validatePrescription'])->name('prescription.validate');
            Route::post('/{order}/cancel-prescription', [OrderProductionController::class, 'cancelPrescriptionDraft'])->name('prescription.cancel');
            Route::patch('/{order}/pms-update', [DashboardController::class, 'updatePmsStatus'])->name('pms.update');

            // GDPR Art. 18 — Restrizione trattamento (solo staff)
            Route::post('/{order}/restrict', [OrderController::class, 'applyRestriction'])->name('restrict.apply');
            Route::delete('/{order}/restrict', [OrderController::class, 'removeRestriction'])->name('restrict.remove');

            // MDR Art. 87 — Segnalazione e chiusura incidenti (solo staff/PRRC)
            Route::post('/{order}/report-incident', [OrderController::class, 'reportIncident'])->name('incident.report');
            Route::patch('/{order}/resolve-incident', [OrderController::class, 'resolveIncident'])->name('incident.resolve');

            Route::middleware(['can:access-production'])->group(function () {
                Route::patch('/{order}/update-delivery-date', [OrderController::class, 'updateDeliveryDate'])->name('updateDeliveryDate');
                Route::patch('/{order}/toggle-trial', [OrderController::class, 'toggleTrial'])->name('toggleTrial');
                Route::patch('/{order}/toggle-priority', [OrderController::class, 'togglePriority'])->name('togglePriority');
                Route::get('/{order}/prescription-draft', [OrderProductionController::class, 'createPrescription'])->name('prescription.create');
                Route::post('/{order}/prescription-draft', [OrderProductionController::class, 'storePrescription'])->name('prescription.store');
                Route::post('/{order}/start-production', [OrderProductionController::class, 'startProduction'])->name('production.start');
                Route::get('/{order}/production', [OrderProductionController::class, 'create'])->name('production.create');
                Route::post('/{order}/production', [OrderProductionController::class, 'store'])->middleware('throttle.smart:write')->name('production.store');
                Route::post('/{order}/generate-mdr-pdfs', [OrderProductionController::class, 'generateMdrPdfs'])->middleware('throttle.smart:write')->name('mdr.generate');
                Route::post('/{order}/archive-final', [OrderProductionController::class, 'archiveFinal'])->middleware('throttle.smart:critical')->name('mdr.archive');
            });
        });
    });
});

Route::get('/login', function () {
    return response("Autenticazione Nextcloud necessaria.", 403);
})->name('login');