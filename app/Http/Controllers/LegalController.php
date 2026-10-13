<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Studio;
use App\Models\LegalAcceptance;
use App\Support\LegalText;
use App\Models\Lead;
use Carbon\Carbon;

class LegalController extends Controller
{
    private function loadText(string $filename): string
    {
        return file_exists(LegalText::path($filename))
            ? LegalText::render($filename)
            : "Errore: file {$filename} non trovato.";
    }

    /**
     * Mostra il passo corrente dell'onboarding 4-step.
     * Eccezione lecita per uso diretto di `$user->studio`.
     */
    public function onboarding(Request $request)
    {
        $user  = Auth::user();
        $step  = session('onboarding_step', 1);
        $studio = Studio::where('user_id', $user->uid)->first() ?? new Studio();

        $fileMap = [1 => 'tos-mdr.txt', 2 => 'vessatorie-mdr.txt', 3 => 'privacy-mdr.txt', 4 => 'dpa-mdr.txt'];
        $text = $this->loadText($fileMap[$step] ?? 'tos-mdr.txt');

        return view('legal.onboarding', compact('step', 'text', 'studio', 'user'));
    }

    /**
     * Vista READ-ONLY di un documento di un passo GIÀ presentato ("rivedi i documenti firmati").
     * Nessun side-effect: non scrive accettazioni, non tocca onboarding_step — il motore di firma
     * resta forward-only/append-only. Consentita solo per i passi PRECEDENTI a quello corrente.
     */
    public function review(Request $request, int $step)
    {
        $current = session('onboarding_step', 1);

        $fileMap = [1 => 'tos-mdr.txt', 2 => 'vessatorie-mdr.txt', 3 => 'privacy-mdr.txt', 4 => 'dpa-mdr.txt'];
        $titles  = [
            1 => 'Termini e Condizioni di Fornitura',
            2 => 'Clausole vessatorie (Art. 1341 c.c.)',
            3 => 'Informativa Privacy',
            4 => 'Accordo sul Trattamento dei Dati (DPA)',
        ];

        // Solo i documenti dei passi già firmati (precedenti al corrente); altrimenti torna al wizard.
        if (!isset($fileMap[$step]) || $step >= $current) {
            return redirect()->route('legal.onboarding');
        }

        return view('legal.review', [
            'step'    => $step,
            'title'   => $titles[$step],
            'text'    => $this->loadText($fileMap[$step]),
            'version' => config('legal.version'),
        ]);
    }

    /**
     * Riceve il POST di ciascun passo e avanza lo stato in sessione.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $step = session('onboarding_step', 1);

        return match ($step) {
            1       => $this->processStep1($request, $user),
            2       => $this->processStep2($request, $user),
            3       => $this->processStep3($request, $user),
            4       => $this->processStep4($request, $user),
            default => redirect()->route('legal.onboarding'),
        };
    }

    // -------------------------------------------------------------------------
    // STEP 1 — Termini e Condizioni
    // -------------------------------------------------------------------------

    private function processStep1(Request $request, $user)
    {
        $request->validate(['tos_accepted' => 'required|accepted']);

        $hash = LegalAcceptance::hashLegalFile('tos');

        session([
            'onboarding_step'        => 2,
            'legal_acceptance_tos'   => $this->makeAcceptanceData($request, $hash),
        ]);

        // Se lo studio esiste già (re-onboarding), registra subito
        $studio = Studio::where('user_id', $user->uid)->first();
        if ($studio) {
            $this->createRecord($studio->id, $user->uid, 'tos', $hash, $request);
        }

        return redirect()->route('legal.onboarding');
    }

    // -------------------------------------------------------------------------
    // STEP 2 — Clausole vessatorie Art. 1341 c.c.
    // -------------------------------------------------------------------------

    private function processStep2(Request $request, $user)
    {
        $request->validate(['vessatorie_accepted' => 'required|accepted']);

        // Stessa hash del ToS (stesso file — Art. 1341 estrae articoli dallo stesso documento)
        $hash = LegalAcceptance::hashLegalFile('vessatorie');

        session([
            'onboarding_step'              => 3,
            'legal_acceptance_vessatorie'  => $this->makeAcceptanceData($request, $hash),
        ]);

        $studio = Studio::where('user_id', $user->uid)->first();
        if ($studio) {
            $this->createRecord($studio->id, $user->uid, 'vessatorie', $hash, $request);
        }

        return redirect()->route('legal.onboarding');
    }

    // -------------------------------------------------------------------------
    // STEP 3 — Informativa Privacy
    // -------------------------------------------------------------------------

    private function processStep3(Request $request, $user)
    {
        $request->validate(['privacy_accepted' => 'required|accepted']);

        $hash = LegalAcceptance::hashLegalFile('privacy');

        session([
            'onboarding_step'           => 4,
            'legal_acceptance_privacy'  => $this->makeAcceptanceData($request, $hash),
        ]);

        $studio = Studio::where('user_id', $user->uid)->first();
        if ($studio) {
            $this->createRecord($studio->id, $user->uid, 'privacy', $hash, $request);
        }

        return redirect()->route('legal.onboarding');
    }

    // -------------------------------------------------------------------------
    // STEP 4 — DPA + dati fiscali → commit finale
    // -------------------------------------------------------------------------

    private function processStep4(Request $request, $user)
    {
        $isLab = $user->isLaboratorio();

        $rules = [
            'name'         => 'required|string|max:255',
            'vat_number'   => 'required|string|max:13',
            'sdi_code'     => 'required|string|size:7',
            'pec_email'    => 'required|email|max:255',
            'mobile'       => 'required|string|max:255',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:255',
            'zip_code'     => 'required|string|max:10',
            'dpa_accepted' => 'required|accepted',
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

        DB::transaction(function () use ($validated, $user, $isLab, $request) {
            $now     = Carbon::now();
            $version = config('legal.version');

            // 1. Crea o aggiorna lo studio
            $isNew  = false;
            $studio = Studio::where('user_id', $user->uid)->first();
            if (!$studio) {
                $studio  = new Studio();
                $isNew   = true;
            }

            $studio->user_id      = $user->uid;
            $studio->name         = $validated['name'];
            $studio->vat_number   = $validated['vat_number'];
            $studio->sdi_code     = strtoupper($validated['sdi_code']);
            $studio->pec_email    = $validated['pec_email'];
            $studio->mobile       = $validated['mobile'];
            $studio->address      = $validated['address'];
            $studio->city         = $validated['city'];
            $studio->zip_code     = $validated['zip_code'];
            $studio->dentist_name = $validated['dentist_name'];

            if ($isLab) {
                $studio->itca_code       = strtoupper($validated['itca_code']);
                $studio->dentist_license = 'LAB-PARTNER';
                $studio->albo_provincia  = 'NA';
            } else {
                $studio->dentist_license = $validated['dentist_license'];
                $studio->albo_provincia  = strtoupper($validated['albo_provincia']);
                $studio->itca_code       = null;
            }

            // Timestamp denormalizzati — richiesti da EnsureLegalCompliance (cache fast-path)
            $studio->tos_accepted_at     = $now;
            $studio->privacy_accepted_at = $now;
            $studio->dpa_accepted_at     = $now;
            $studio->mdr_accepted_at     = $now;
            $studio->legal_version       = $version;
            $studio->save();

            // 2. Registra DPA
            $dpaHash = LegalAcceptance::hashLegalFile('dpa');
            $this->createRecord($studio->id, $user->uid, 'dpa', $dpaHash, $request, $now);

            // 3. Per studi nuovi: crea i record dei passi 1-3 dalla sessione
            if ($isNew) {
                foreach (['tos', 'vessatorie', 'privacy'] as $type) {
                    $data = session("legal_acceptance_{$type}");
                    if ($data) {
                        LegalAcceptance::create([
                            'studio_id'     => $studio->id,
                            'user_uid'      => $user->uid,
                            'document_type' => $type,
                            'version'       => $version,
                            'accepted_at'   => Carbon::parse($data['at']),
                            'ip_address'    => $data['ip'],
                            'user_agent'    => $data['ua'],
                            'content_hash'  => $data['hash'],
                        ]);
                    }
                }
            }

            // 4. Crea/aggiorna sede principale
            $studio->addresses()->updateOrCreate(
                ['is_main' => true],
                [
                    'label'           => 'Sede Legale / Principale',
                    'dentist_name'    => $validated['dentist_name'],
                    'dentist_license' => $isLab ? 'LAB-PARTNER' : $validated['dentist_license'],
                    'albo_provincia'  => $isLab ? 'NA' : strtoupper($validated['albo_provincia']),
                    'phone'           => $validated['mobile'],
                    'address'         => $validated['address'],
                    'city'            => $validated['city'],
                    'zip_code'        => $validated['zip_code'],
                    'is_main'         => true,
                ]
            );

            // 5. Chiude il cerchio lead→studio: collega il lead originato dal funnel (T3) a questo studio.
            //    Chiave leads.created_user_uid === studios.user_id === $user->uid (valorizzato solo per i
            //    lead provisionati dal funnel; utenti non-funnel → 0 righe, innocuo).
            //    whereNull('converted_at') → idempotente (un lead si converte una volta sola).
            //    Update query-level → scrive a prescindere dal mass-assignment, atomico nella transaction.
            Lead::where('created_user_uid', $user->uid)
                ->whereNull('converted_at')
                ->update([
                    'studio_id'    => $studio->id,
                    'converted_at' => $now,
                ]);

            // 6. Welcome 25 cr una-tantum — ancorato al LEAD verificato (anti signup-farming v2).
            //    Grant SOLO se: lead del funnel (created_user_uid) + email verificata + mai concesso.
            //    Bucket credits_purchased: NON azzerato da ibex:reset-credits, non scade.
            $welcomeLead = Lead::where('created_user_uid', $user->uid)
                ->whereNotNull('email_verified_at')
                ->whereNull('welcome_granted_at')
                ->lockForUpdate()
                ->first();
            if ($welcomeLead) {
                $studio->credits_purchased = (int) ($studio->credits_purchased ?? 0) + 25;
                $studio->balance = (int) ($studio->credits_gift ?? 0) + (int) $studio->credits_purchased;
                $studio->save();

                $welcomeLead->welcome_granted_at = $now;
                $welcomeLead->save();

                Log::info("WELCOME-CREDITS: 25 cr -> studio {$studio->id} (lead {$welcomeLead->id}, uid {$user->uid})");
            }

            Log::info("MDR-COMPLIANCE: onboarding completato per studio {$studio->id} utente {$user->uid} versione {$version}");
        });

        session()->forget([
            'onboarding_step',
            'legal_acceptance_tos',
            'legal_acceptance_vessatorie',
            'legal_acceptance_privacy',
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Profilo Professionale attivato e protocolli firmati correttamente.');
    }

    // -------------------------------------------------------------------------
    // HELPER PRIVATI
    // -------------------------------------------------------------------------

    private function makeAcceptanceData(Request $request, string $hash): array
    {
        return [
            'hash' => $hash,
            'ip'   => $request->ip(),
            'ua'   => substr($request->userAgent() ?? '', 0, 500),
            'at'   => now()->toISOString(),
        ];
    }

    private function createRecord(
        int $studioId,
        string $userUid,
        string $type,
        string $hash,
        Request $request,
        ?Carbon $at = null
    ): void {
        LegalAcceptance::create([
            'studio_id'     => $studioId,
            'user_uid'      => $userUid,
            'document_type' => $type,
            'version'       => config('legal.version'),
            'accepted_at'   => $at ?? now(),
            'ip_address'    => $request->ip(),
            'user_agent'    => substr($request->userAgent() ?? '', 0, 500),
            'content_hash'  => $hash,
        ]);
    }
}
