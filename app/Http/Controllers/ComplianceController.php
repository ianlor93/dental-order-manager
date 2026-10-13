<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Endpoint pubblici di conformità — nessuna autenticazione richiesta.
 *
 * Scopo: permettere ai clienti (e ai loro legali) di verificare in qualsiasi momento
 * l'elenco aggiornato dei sub-responsabili del trattamento (Allegato A DPA).
 */
class ComplianceController extends Controller
{
    /**
     * Elenco sub-responsabili in formato JSON (machine-readable).
     * Esposto pubblicamente per adempimento Art. 28.4 GDPR.
     *
     * GET /compliance/subprocessors.json
     */
    public function subprocessorsJson(): JsonResponse
    {
        return response()->json([
            'allegato_a_version'    => config('compliance.allegato_a_version'),
            'allegato_a_updated_at' => config('compliance.allegato_a_updated_at'),
            'change_notice_days'    => config('compliance.change_notice_days'),
            'subprocessors'         => config('compliance.subprocessors'),
        ])->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Pagina HTML dei sub-responsabili (human-readable).
     *
     * GET /compliance/subprocessors
     */
    public function subprocessorsHtml()
    {
        return view('compliance.subprocessors', [
            'version'      => config('compliance.allegato_a_version'),
            'updatedAt'    => config('compliance.allegato_a_updated_at'),
            'subprocessors'=> config('compliance.subprocessors'),
            'noticeDays'   => config('compliance.change_notice_days'),
        ]);
    }
}
