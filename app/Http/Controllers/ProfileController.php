<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Studio;
use App\Support\LegalText;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Visualizza il profilo utente.
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) abort(403, 'Sessione scaduta');

        // Recupero studio (se esiste)
        $studio = Studio::where('user_id', $user->uid)->with('addresses')->first();

        // 🛡️ SICUREZZA STAFF: Se è staff, non ha bisogno dello studio.
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            return view('profile.show', [
                'user' => $user,
                'studio' => null, // L'admin non ha uno studio
                'isStaff' => true
            ]);
        }

        // Se è un medico e non ha ancora lo studio, lo mandiamo all'onboarding
        if (!$studio) {
            return redirect()->route('legal.onboarding')->with('error', 'Configurazione professionale necessaria.');
        }

        return view('profile.show', [
            'user' => $user,
            'studio' => $studio,
            'isStaff' => false
        ]);
    }

    /**
     * Download dei contratti firmati (Solo per Medici).
     */
    public function downloadContract($type)
    {
        $user = Auth::user();
        
        // 🛡️ PROTEZIONE: Lo staff non ha contratti clinici da scaricare
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            return back()->with('error', 'Gli account staff non hanno contratti clinici associati.');
        }

        $studio = Studio::where('user_id', $user->uid)->first();
        if (!$studio) abort(404, "Studio non trovato");

        $legalConfig = [
            'tos'     => ['file' => 'tos-mdr.txt',      'title' => 'Termini e Condizioni di Fornitura'],
            'privacy' => ['file' => 'privacy-mdr.txt', 'title' => 'Informativa Privacy e Trattamento Dati'],
            'dpa'     => ['file' => 'dpa-mdr.txt',      'title' => 'Nomina Responsabile del Trattamento (DPA)'],
        ];

        if (!isset($legalConfig[$type])) abort(404);

        $textFilePath = LegalText::path($legalConfig[$type]['file']);
        if (!file_exists($textFilePath)) abort(500, "File di testo legale mancante");

        $legalContent = LegalText::render($legalConfig[$type]['file']);

        // Gestione timestamp in sicurezza
        $timestamp = match($type) {
            'tos'     => $studio->tos_accepted_at,
            'privacy' => $studio->privacy_accepted_at,
            'dpa'     => $studio->dpa_accepted_at,
            default   => null,
        };

        if ($timestamp && !($timestamp instanceof Carbon)) {
            $timestamp = Carbon::parse($timestamp);
        }

        $pdf = Pdf::loadView('legal.pro.master-contract', [
            'studio'       => $studio,
            'user'         => $user,
            'timestamp'    => $timestamp ?? now(),
            'legalContent' => $legalContent,
            'docTitle'     => $legalConfig[$type]['title'],
            'isCopy'       => true
        ]);

        return $pdf->stream("Documento_" . strtoupper($type) . ".pdf");
    }

    /**
     * Aggiorna i dati Master (Solo per Medici/Laboratori).
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $isLab = $user->isLaboratorio();

        if (\App\Support\AccessContext::for($user)->isStaff()) return back()->with('error', 'Lo staff non può modificare anagrafiche cliniche.');

        // Validazione Condizionale
        $rules = [
            'name'            => 'required|string|max:255',
            'vat_number'      => 'required|string|max:13',
            'sdi_code'        => 'required|string|size:7',
            'pec_email'       => 'required|email|max:255',
            'mobile'          => 'required|string|max:255',
            'address'         => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'zip_code'        => 'required|string|max:10',
        ];

        if ($isLab) {
            $rules['dentist_name'] = 'required|string|max:255';
            $rules['itca_code']    = 'required|string|max:255';
        } else {
            $rules['dentist_name']    = 'required|string|max:255';
            $rules['dentist_license'] = 'required|string|max:255';
            $rules['albo_provincia']  = 'required|string|size:2';
        }

        $validated = $request->validate($rules);

        $validated['sdi_code'] = strtoupper($validated['sdi_code']);
        
        if (!$isLab) {
            $validated['albo_provincia'] = strtoupper($validated['albo_provincia']);
        } else {
            $validated['itca_code'] = strtoupper($validated['itca_code']);
        }

        try {
            DB::transaction(function () use ($user, $validated, $isLab) {
                $studio = Studio::where('user_id', $user->uid)->firstOrFail();
                
                // Aggiornamento selettivo
                if ($isLab) {
                    $validated['dentist_license'] = 'LAB-PARTNER';
                    $validated['albo_provincia'] = 'NA';
                }

                $studio->update($validated);

                // Sincronizza la Sede Legale (is_main = true)
                $studio->addresses()->where('is_main', true)->update([
                    'dentist_name'    => $validated['dentist_name'],
                    'dentist_license' => $isLab ? 'LAB-PARTNER' : $validated['dentist_license'],
                    'albo_provincia'  => $isLab ? 'NA' : ($validated['albo_provincia'] ?? 'NA'),
                    'phone'           => $validated['mobile'],
                    'address'         => $validated['address'],
                    'city'            => $validated['city'],
                    'zip_code'        => $validated['zip_code'],
                ]);
            });
            return redirect()->route('profile.show')->with('success', 'Profilo aggiornato.');
        } catch (\Exception $e) {
            Log::error("PROFILE-UPDATE-ERROR: " . $e->getMessage());
            return back()->with('error', 'Errore durante l\'aggiornamento.');
        }
    }

    public function requestDeletion()
    {
        $user = Auth::user();

        // Conta ordini già flaggati in precedenza
        $alreadyFlagged = \App\Models\Order::where('user_id', $user->uid)
            ->whereNotNull('deletion_requested_at')
            ->count();

        // Flagga ordini non locked e non ancora in attesa di cancellazione
        $flagged = \App\Models\Order::where('user_id', $user->uid)
            ->where('is_locked', false)
            ->whereNull('deletion_requested_at')
            ->update([
                'deletion_requested_at' => now(),
                'deletion_reason'       => 'user_request',
            ]);

        // Log originale mantenuto per compatibilità con eventuali monitoring
        Log::warning("GDPR DELETE REQUEST: User UID {$user->uid}");

        Log::info('GDPR deletion requested', [
            'user_id'          => $user->uid,
            'orders_flagged'   => $flagged,
            'orders_locked'    => \App\Models\Order::where('user_id', $user->uid)->where('is_locked', true)->count(),
            'already_pending'  => $alreadyFlagged,
            'deletion_date'    => now()->addDays(30)->toDateString(),
        ]);

        return back()->with('success', 'Richiesta presa in carico. I tuoi dati saranno rimossi entro 30 giorni. Riceverai una notifica via PEC.');
    }
}