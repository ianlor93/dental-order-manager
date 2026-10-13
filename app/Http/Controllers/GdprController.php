<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Studio;
use App\Models\GdprRequest;
use App\Http\Requests\GdprAccessRequest;
use App\Http\Requests\GdprRectificationRequest;
use App\Mail\GdprRequestNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ZipArchive;

/**
 * GdprController — Diritti degli Interessati (GDPR 2016/679)
 *
 * Art. 20 — Diritto alla portabilità dei dati:
 *   L'interessato riceve i propri dati personali in formato strutturato,
 *   di uso comune e leggibile da dispositivo automatico.
 *
 * L'export include:
 *   - Dati profilo (studio, indirizzi, consensi)
 *   - Tutti gli ordini con produzione e commenti pubblici
 *   - Metadati file allegati (nome, dimensione, hash — non i file fisici)
 *   - PDF riepilogativo leggibile
 *
 * I file CAD/CAM fisici non sono inclusi: sono dati tecnici del laboratorio,
 * non dati personali dell'interessato ai sensi dell'Art. 20.
 */
class GdprController extends Controller
{
    public function export()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Lo staff non ha un profilo cliente da esportare
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            return back()->with('error', 'Gli account staff non hanno dati personali esportabili tramite questa funzione.');
        }

        $studio = Studio::where('user_id', $user->uid)
            ->with('addresses')
            ->first();

        $orders = Order::where('user_id', $user->uid)
            ->with([
                'production',
                'files',
                'address',
                'comments' => fn($q) => $q->where('is_internal', false)->orderBy('created_at'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // --- Costruzione struttura dati export ---

        $profilo = [
            'uid'          => $user->uid,
            'nome'         => $user->displayname,
            'email'        => $user->email,
            'studio'       => $studio ? [
                'ragione_sociale'  => $studio->name,
                'partita_iva'      => $studio->vat_number,
                'codice_fiscale'   => $studio->fiscal_code,
                'sdi'              => $studio->sdi_code,
                'pec'              => $studio->pec_email,
                'telefono'         => $studio->phone,
                'cellulare'        => $studio->mobile,
                'indirizzo'        => $studio->address,
                'citta'            => $studio->city,
                'cap'              => $studio->zip_code,
                'dentista'         => $studio->dentist_name,
                'albo'             => $studio->dentist_license,
                'provincia_albo'   => $studio->albo_provincia,
            ] : null,
            'indirizzi_operativi' => $studio?->addresses->map(fn($a) => [
                'etichetta'      => $a->label,
                'dentista'       => $a->dentist_name,
                'albo'           => $a->dentist_license,
                'indirizzo'      => $a->address,
                'citta'          => $a->city,
                'cap'            => $a->zip_code,
                'telefono'       => $a->phone,
                'sede_principale' => (bool) $a->is_main,
            ])->toArray() ?? [],
        ];

        $consensi = $studio ? [
            'privacy_accepted_at' => $studio->privacy_accepted_at?->toIso8601String(),
            'tos_accepted_at'     => $studio->tos_accepted_at?->toIso8601String(),
            'mdr_accepted_at'     => $studio->mdr_accepted_at?->toIso8601String(),
            'dpa_accepted_at'     => $studio->dpa_accepted_at?->toIso8601String(),
            'legal_version'       => $studio->legal_version,
        ] : [];

        $ordiniExport = $orders->map(fn($order) => [
            'codice'               => $order->code,
            'stato'                => $order->status,
            'riferimento_paziente' => $order->patient_ref,
            'tipo_dispositivo'     => $order->device_type,
            'dispositivi'          => $order->manufactured_devices,
            'materiale'            => $order->material,
            'note'                 => $order->notes,
            'priorita'             => $order->priority,
            'data_consegna'        => $order->delivery_date?->toDateString(),
            'creato_il'            => $order->created_at?->toIso8601String(),
            'validato_il'          => $order->validated_at?->toIso8601String(),
            'archiviato_il'        => $order->archived_at?->toIso8601String(),
            'conservare_fino_al'   => $order->legal_retention_until?->toDateString(),
            'sede_destinataria'    => $order->address ? [
                'dentista'  => $order->address->dentist_name,
                'indirizzo' => $order->address->address,
                'citta'     => $order->address->city,
            ] : null,
            'produzione'           => $order->production ? [
                'operatore_cad'  => $order->production->cad_operator,
                'operatore_lab'  => $order->production->lab_operator,
                'verificatore_qc'=> $order->production->qc_verifier,
                'stato_qc'       => $order->production->qc_status,
                'completato_il'  => $order->production->released_at?->toIso8601String(),
            ] : null,
            'file_allegati'        => $order->files->map(fn($f) => [
                'nome'          => $f->original_name,
                'dimensione_kb' => round($f->size_bytes / 1024, 1),
                'tipo'          => $f->mime_type,
                'hash_sha256'   => $f->hash_sha256,
                'stato_scansione' => $f->scan_status,
            ])->toArray(),
            'commenti_pubblici'    => $order->comments->map(fn($c) => [
                'testo'  => $c->body,
                'data'   => $c->created_at?->toIso8601String(),
            ])->toArray(),
        ])->toArray();

        // --- Generazione ZIP ---

        $exportDir = storage_path('app/private/gdpr_exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0700, true);
        }

        $timestamp = now()->format('Ymd_His');
        $zipName   = "{$exportDir}/EXPORT_{$user->uid}_{$timestamp}.zip";

        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('GDPR export ZIP creation failed', ['user_id' => $user->uid]);
            return back()->with('error', 'Impossibile creare il file di export. Contatta il supporto.');
        }

        $zip->addFromString('profilo.json',  json_encode($profilo,   JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('consensi.json', json_encode($consensi,  JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('ordini.json',   json_encode($ordiniExport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // PDF riepilogativo leggibile
        $summaryPdf = Pdf::loadView('pdfs.gdpr_export_summary', [
            'user'    => $user,
            'studio'  => $studio,
            'ordini'  => $orders,
            'consensi'=> $consensi,
            'generato_il' => now(),
        ])->output();
        $zip->addFromString('RIEPILOGO_EXPORT.pdf', $summaryPdf);

        $zip->close();

        Log::info('GDPR export generated', [
            'user_id'      => $user->uid,
            'orders_count' => $orders->count(),
            'zip_size_kb'  => round(filesize($zipName) / 1024, 1),
        ]);

        $downloadName = "MieiDati_{$timestamp}.zip";
        return response()->download($zipName, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * Riceve una richiesta di opposizione al trattamento (GDPR Art. 21).
     *
     * L'Art. 21 consente all'interessato di opporsi al trattamento basato su:
     *   - Art. 6(1)(e) — interesse pubblico
     *   - Art. 6(1)(f) — legittimo interesse del titolare
     * NON si applica ai trattamenti basati su obbligo legale (Art. 6(1)(c))
     * o su contratto (Art. 6(1)(b)) — es. dati MDR obbligatori.
     *
     * Termini Art. 12: risposta entro 1 mese (prorogabile a 3 mesi per complessità).
     */
    public function storeOpposition(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (\App\Support\AccessContext::for($user)->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'object_category' => 'required|in:ordini,studio,tutto,altro',
            'motivation'      => 'required|string|min:20|max:2000',
        ], [
            'object_category.required' => 'Seleziona la categoria di dati oggetto dell\'opposizione.',
            'object_category.in'       => 'Categoria non valida.',
            'motivation.required'      => 'La motivazione è obbligatoria.',
            'motivation.min'           => 'La motivazione deve contenere almeno 20 caratteri.',
            'motivation.max'           => 'La motivazione non può superare i 2000 caratteri.',
        ]);

        $gdprRequest = GdprRequest::create([
            'user_uid'        => $user->uid,
            'right_article'   => 'art21',
            'object_category' => $validated['object_category'],
            'motivation'      => $validated['motivation'],
            'received_at'     => now(),
            'deadline_at'     => now()->addDays(30),
            'decision'        => 'pending',
            'ip_address'      => $request->ip(),
        ]);

        Log::info('GDPR Art.21 opposition received', [
            'user_uid'        => $user->uid,
            'request_id'      => $gdprRequest->id,
            'object_category' => $validated['object_category'],
        ]);

        return back()->with('success',
            'Richiesta di opposizione registrata (Rif. #' . $gdprRequest->id . '). ' .
            'Riceverai risposta entro 30 giorni all\'indirizzo email del tuo account, ' .
            'come previsto dall\'Art. 12 GDPR 2016/679.'
        );
    }

    /**
     * GDPR Art. 15 — Diritto di accesso.
     *
     * L'interessato ha diritto di ottenere conferma che si stia trattando dati suoi,
     * accesso ai dati e informazioni su finalità/categorie/destinatari/retention.
     *
     * La richiesta crea un fascicolo in oc_enamel_gdpr_requests. Il DPO riceve
     * notifica email e ha 30gg (Art. 12, prorogabili a 90) per rispondere.
     *
     * Nota: /gdpr/export fornisce export automatico immediato (Art. 20).
     * Questo endpoint copre il diritto di accesso formale con audit trail completo.
     */
    public function storeAccessRequest(GdprAccessRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $gdpr = GdprRequest::create([
            'user_uid'        => $user->uid,
            'right_article'   => 'art15',
            'object_category' => $request->validated('object_category'),
            'motivation'      => $request->validated('motivation')
                ?? '(Modalità consegna: ' . $request->validated('delivery_mode') . ')',
            'received_at'     => now(),
            'deadline_at'     => now()->addDays(30),
            'decision'        => 'pending',
            'ip_address'      => $request->ip(),
        ]);

        $this->notifyDpo($gdpr, $user);

        Log::info('GDPR Art.15 access request received', [
            'user_uid'   => $user->uid,
            'request_id' => $gdpr->id,
        ]);

        return back()->with('success',
            'Richiesta di accesso registrata (Rif. #' . $gdpr->id . '). ' .
            'Riceverai risposta entro 30 giorni come previsto dall\'Art. 12 GDPR 2016/679. ' .
            'Per download immediato dei tuoi dati puoi usare anche l\'export automatico.'
        );
    }

    /**
     * GDPR Art. 16 — Diritto di rettifica.
     *
     * L'interessato ha diritto di ottenere la rettifica dei dati inesatti senza
     * ingiustificato ritardo. I dati vengono effettivamente rettificati solo dopo
     * validazione del DPO — non in automatico — per evitare manomissioni.
     *
     * Nota MDR: dati di ordini con is_locked=true sono immutabili ai sensi
     * dell'Art. 10(8) MDR. La rettifica su tali dati risulta in una nota allegata,
     * non in modifica del dato originale.
     */
    public function storeRectificationRequest(GdprRectificationRequest $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $motivation = sprintf(
            "Campo: %s\nValore attuale: %s\nValore richiesto: %s\n\nMotivazione:\n%s",
            $request->validated('field'),
            $request->validated('current_value') ?? '(non specificato)',
            $request->validated('requested_value'),
            $request->validated('motivation'),
        );

        $gdpr = GdprRequest::create([
            'user_uid'        => $user->uid,
            'right_article'   => 'art16',
            'object_category' => 'altro',
            'motivation'      => $motivation,
            'received_at'     => now(),
            'deadline_at'     => now()->addDays(30),
            'decision'        => 'pending',
            'ip_address'      => $request->ip(),
        ]);

        $this->notifyDpo($gdpr, $user);

        Log::info('GDPR Art.16 rectification request received', [
            'user_uid'   => $user->uid,
            'request_id' => $gdpr->id,
            'field'      => $request->validated('field'),
        ]);

        return back()->with('success',
            'Richiesta di rettifica registrata (Rif. #' . $gdpr->id . '). ' .
            'Il DPO valuterà la richiesta entro 30 giorni.'
        );
    }

    /**
     * GDPR Art. 18 — Diritto di restrizione del trattamento.
     *
     * L'interessato può richiedere la restrizione quando:
     *   a) contesta l'esattezza dei dati
     *   b) il trattamento è illecito ma preferisce la restrizione alla cancellazione
     *   c) i dati servono per difesa di un diritto in sede giudiziaria
     *   d) ha presentato opposizione ex Art. 21 in attesa di verifica
     *
     * La restrizione è una marcatura logica: i dati restano nel DB ma non vengono
     * inclusi nelle esportazioni né modificati dallo staff fino alla data di revisione.
     * NON interferisce con is_locked (vincolo MDR).
     */
    public function storeRestrictionRequest(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (\App\Support\AccessContext::for($user)->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'restriction_reason' => 'required|in:accuracy_dispute,unlawful_processing,legal_claim,opposition_pending',
            'motivation'         => 'required|string|min:20|max:2000',
            'object_category'    => 'required|in:ordini,studio,tutto,altro',
        ], [
            'restriction_reason.required' => 'Seleziona il motivo della restrizione.',
            'restriction_reason.in'       => 'Motivo non valido.',
            'motivation.required'         => 'La motivazione è obbligatoria.',
            'motivation.min'              => 'La motivazione deve contenere almeno 20 caratteri.',
            'object_category.required'    => 'Seleziona la categoria di dati.',
        ]);

        $motivation = sprintf(
            "Motivo: %s\nCategoria: %s\n\n%s",
            $validated['restriction_reason'],
            $validated['object_category'],
            $validated['motivation']
        );

        $gdprRequest = GdprRequest::create([
            'user_uid'        => $user->uid,
            'right_article'   => 'art18',
            'object_category' => $validated['object_category'],
            'motivation'      => $motivation,
            'received_at'     => now(),
            'deadline_at'     => now()->addDays(30),
            'decision'        => 'pending',
            'ip_address'      => $request->ip(),
        ]);

        $this->notifyDpo($gdprRequest, $user);

        Log::info('GDPR Art.18 restriction request received', [
            'user_uid'           => $user->uid,
            'request_id'         => $gdprRequest->id,
            'restriction_reason' => $validated['restriction_reason'],
        ]);

        return back()->with('success',
            'Richiesta di restrizione registrata (Rif. #' . $gdprRequest->id . '). ' .
            'Riceverai risposta entro 30 giorni come previsto dall\'Art. 12 GDPR 2016/679.'
        );
    }

    /**
     * Invia notifica email al DPO con il fascicolo della richiesta.
     * Fallback silenzioso in caso di errore — la richiesta resta nel DB.
     */
    private function notifyDpo(GdprRequest $gdpr, \App\Models\User $user): void
    {
        try {
            $dpoEmail = config('lab.dpo.email') ?: config('lab.manufacturer.email');
            if (!$dpoEmail) {
                Log::warning('GDPR: DPO email non configurata, notifica saltata', [
                    'request_id' => $gdpr->id,
                ]);
                return;
            }

            Mail::to($dpoEmail)->queue(new GdprRequestNotification($gdpr, $user));
        } catch (\Throwable $e) {
            Log::error('GDPR: invio notifica DPO fallito: ' . $e->getMessage(), [
                'request_id' => $gdpr->id,
            ]);
        }
    }
}
