<?php

namespace App\Http\Controllers;

use App\Models\{Order, OrderFile, Address, ArchiveAccess};
use App\Jobs\{ProcessOrderJob, SupplementOrderJob};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Auth, Log, File, Mail, DB};
use App\Mail\EnamelNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use Exception;

class OrderController extends Controller
{
    public function create() {
        $user = Auth::user();
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            $allAddresses = Address::has('studio')->with('studio')->get();
            return view('orders.create', compact('user', 'allAddresses'));
        }

        // Risoluzione studio: titolare o prescrittore membro via pivot
        $studio = \App\Support\AccessContext::for($user)->activeStudio()?->load('addresses');
        if (!$studio || !$studio->id || $studio->addresses->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Configura la sede dello studio prima di continuare.');
        }

        return view('orders.create', compact('user', 'studio'));
    }

    public function store(Request $request) {
        $request->validate([
            'address_id' => 'required|exists:oc_enamel_addresses,id',
            'patient_ref' => 'required|string',
            'device_type' => 'required|string',
            'expected_files_count' => 'required|integer',
            'notes' => 'nullable|string'
        ]);

        try {
            $address = Address::with('studio')->findOrFail($request->address_id);
            $order = Order::create([
                'code' => $this->generateUniqueOrderCode(),
                // user_id = uid del prescrittore che crea l'ordine (non necessariamente il titolare).
                // Il titolare dello studio è ricavabile tramite $order->studio->user_id.
                'user_id' => Auth::id(),
                'studio_id' => $address->studio->id,
                'address_id' => $address->id,
                'patient_ref' => strtoupper($request->patient_ref),
                'device_type' => $request->device_type,
                'notes' => $request->notes,
                'teeth_notation' => 'N/D',
                'material' => 'N/D',
                'status' => 'draft',
                'priority' => 'normal',
                'expected_files_count' => $request->expected_files_count,
                'upload_session_id' => (string) Str::uuid(),
                'source' => 'web'
            ]);
            
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_code' => $order->code,
                'session_id' => $order->upload_session_id
            ]);
        } catch (Exception $e) {
            Log::error("MDR-STORE-ERROR: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function uploadChunk(Request $request, Order $order) { return $this->handleResumableUpload($request, $order, false); }
    public function uploadSupplementalChunk(Request $request, Order $order) { return $this->handleResumableUpload($request, $order, true); }

    private function handleResumableUpload(Request $request, Order $order, bool $isSupplemental) {
        $this->authorize('upload', $order);
        $chunkDir = storage_path("app/chunks/" . ($isSupplemental ? "supp_" : "") . $order->upload_session_id . "/" . Str::slug($request->resumableIdentifier));
        $chunkFile = $chunkDir . '/' . $request->resumableChunkNumber . '.part';
        if ($request->isMethod('get')) return (File::exists($chunkFile)) ? response()->json([], 200) : response()->json([], 204);

        if (!$request->hasFile('file')) return response()->json(['error' => 'No file'], 400);

        // ✨ FIX PERMESSI UNRAID: Cartella chunks a 0777
        if (!File::isDirectory($chunkDir)) File::makeDirectory($chunkDir, 0777, true, true);
        $request->file('file')->move($chunkDir, $request->resumableChunkNumber . '.part');

        if (count(File::glob("{$chunkDir}/*.part")) === (int)$request->resumableTotalChunks) {
            $fileName = basename($request->resumableFilename);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['stl', 'ply', 'obj', 'dcm', 'nrrd', 'zip', 'pdf', 'png', 'jpg', 'jpeg', 'tiff', 'tif'];
            if (!in_array($ext, $allowedExts, true)) {
                File::deleteDirectory($chunkDir);
                return response()->json(['error' => 'Formato file non consentito.'], 422);
            }

            $relativeFolder = "orders/{$order->code}";
            $fullFolderPath = storage_path("app/private/" . $relativeFolder);
            
            // ✨ FIX PERMESSI UNRAID: Cartella finale a 0777
            if (!File::isDirectory($fullFolderPath)) File::makeDirectory($fullFolderPath, 0777, true, true);

            $finalPath = "{$fullFolderPath}/{$fileName}";
            $out = fopen($finalPath, "wb");
            $chunks = File::glob("{$chunkDir}/*.part");
            sort($chunks, SORT_NATURAL);
            foreach ($chunks as $c) { $in = fopen($c, "rb"); stream_copy_to_stream($in, $out); fclose($in); }
            fclose($out);
            
            // ✨ FIX PERMESSI UNRAID: File sbloccato per Mac/Samba
            if (File::exists($finalPath)) chmod($finalPath, 0777);

            File::deleteDirectory($chunkDir);

            if ($isSupplemental) {
                SupplementOrderJob::dispatch($order, "{$relativeFolder}/{$fileName}", $fileName);
            } else {
                $this->registerInitialFile($order, $fileName, "{$relativeFolder}/{$fileName}");
                $this->checkAndFinalizeOrder($order);
            }
        }
        return response()->json(['status' => 'accepted']);
    }

    public function downloadRequestPdf(Order $order) {
        $this->authorize('view', $order);
        $order = $order->fresh(['studio', 'address', 'files']);
        ArchiveAccess::log($order, 'download_pdf', "Richiesta_{$order->code}.pdf");
        $pdfData = [
            'order' => $order,
            'clinician' => $order->address->dentist_name ?? ($order->studio->dentist_name ?? 'N/D'),
            'studio_name' => $order->studio->name ?? 'N/D',
            'studio_address' => $order->address->address ?? 'N/D',
            'license' => $order->address->dentist_license ?? 'N/D',
            'prov' => $order->address->albo_provincia ?? 'N/D'
        ];
        return Pdf::loadView('pdfs.request_sheet', $pdfData)->download("Richiesta_{$order->code}.pdf");
    }

    public function downloadZip(Order $order) {
        $this->authorize('view', $order);
        ArchiveAccess::log($order, 'download_zip');
        $zipName = storage_path("app/private/orders/{$order->code}.zip");
        $zip = new ZipArchive;
        if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($order->files as $file) {
                $filePath = storage_path("app/private/" . $file->file_path);
                if (File::exists($filePath)) $zip->addFile($filePath, $file->original_name);
            }
            $zip->close();
        }
        return response()->download($zipName)->deleteFileAfterSend(true);
    }

    public function destroy(Order $order) {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) return back()->with('error', 'Solo lo Staff può eliminare i lavori.');
        if ($order->is_locked) return back()->with('error', 'Impossibile eliminare: lavoro protetto MDR.');
        try { $order->delete(); return back()->with('success', 'Lavoro eliminato.'); }
        catch (Exception $e) { return back()->with('error', 'Errore eliminazione.'); }
    }

    private function registerInitialFile($order, $fileName, $relativePath) {
        $fullPath = storage_path("app/private/" . $relativePath);
        OrderFile::create([
            'order_id' => $order->id,
            'file_path' => $relativePath,
            'original_name' => $fileName,
            'size_bytes' => File::size($fullPath),
            'mime_type' => File::mimeType($fullPath) ?? 'application/octet-stream',
            'hash_sha256' => hash_file('sha256', $fullPath),
            'scan_status' => 'pending'
        ]);
        
        // ✨ FIX PERMESSI UNRAID: Sblocco in fase di registrazione
        if (File::exists($fullPath)) chmod($fullPath, 0777);
    }

    private function checkAndFinalizeOrder($order) {
        $order->refresh();
        if ($order->files()->count() >= (int)$order->expected_files_count && $order->status === 'draft') {
            $order->update(['status' => 'scanning']);
            ProcessOrderJob::dispatchSync($order);
            
            // 📧 EVENTO A: Conferma Ricezione Ordine (NEXTCLOUD LOOKUP)
            $studio = $order->studio;
            if ($studio) {
                $cleanUid = trim($studio->user_id);
                $ncAccount = DB::table('oc_accounts')->where('uid', $cleanUid)->first();
                $recipientEmail = null;
                
                if ($ncAccount && isset($ncAccount->data)) {
                    $jsonData = json_decode($ncAccount->data, true);
                    $recipientEmail = $jsonData['email']['value'] ?? null;
                }

                if ($recipientEmail) {
                    try {
                        Mail::to($recipientEmail)->queue(new EnamelNotification([
                            'subject' => "Ricezione Ordine #{$order->code}",
                            'title'   => "Ordine Ricevuto",
                            'message' => "Gentile {$studio->name},\nabbiamo ricevuto correttamente il tuo ordine per il paziente {$order->patient_ref}. I file sono in fase di controllo.\n\nRiceverai un aggiornamento appena la prescrizione sarà pronta.",
                            'actionUrl' => route('welcome'),
                            'actionText' => "Vedi Dettagli"
                        ]));
                    } catch (\Exception $e) {
                        Log::error("EMAIL-ERROR (Ricezione): " . $e->getMessage());
                    }
                }
            }
        }
    }

    private function generateUniqueOrderCode() { do { $code = strtoupper(Str::random(6)); } while (Order::where('code', $code)->exists()); return $code; }
    public function toggleTrial(Order $order) { $order->update(['is_trial' => !$order->is_trial]); return response()->json(['success' => true]); }
    public function togglePriority(Order $order) { $new = ($order->priority === 'high' ? 'normal' : 'high'); $order->update(['priority' => $new]); return response()->json(['success' => true]); }
    
    public function updateDeliveryDate(Request $request, Order $order) {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) return response()->json(['error' => 'Non autorizzato'], 403);
        $validated = $request->validate(['delivery_date' => 'required|date']);
        $order->update(['delivery_date' => $validated['delivery_date']]);
        
        // 📧 EVENTO E: Modifica Previsione Consegna (NEXTCLOUD LOOKUP)
        $studio = $order->studio;
        if ($studio) {
            $cleanUid = trim($studio->user_id);
            $ncAccount = DB::table('oc_accounts')->where('uid', $cleanUid)->first();
            $recipientEmail = null;
            
            if ($ncAccount && isset($ncAccount->data)) {
                $jsonData = json_decode($ncAccount->data, true);
                $recipientEmail = $jsonData['email']['value'] ?? null;
            }

            if ($recipientEmail) {
                try {
                    Mail::to($recipientEmail)->queue(new EnamelNotification([
                        'subject' => "Aggiornamento Consegna - Ordine #{$order->code}",
                        'title'   => "Aggiornamento Consegna",
                        'message' => "Gentile {$studio->name},\nabbiamo aggiornato la data di consegna prevista per il lavoro {$order->patient_ref} ({$order->device_type}).\n\nTi contatteremo a breve per concordare i dettagli e fornirti ulteriori informazioni.",
                        'actionUrl' => route('welcome'),
                        'actionText' => "Accedi al Portale"
                    ]));
                } catch (\Exception $e) {
                    Log::error("EMAIL-ERROR (UpdateDate): " . $e->getMessage());
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * GDPR Art. 18 — Applica restrizione del trattamento su un ordine (solo staff).
     *
     * La restrizione è una marcatura logica: l'ordine resta nel DB ma non compare
     * nelle esportazioni GDPR né è modificabile dallo staff fino a restriction_review_date.
     * Non interferisce con is_locked (vincolo MDR separato).
     */
    public function applyRestriction(Request $request, Order $order)
    {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);

        $validated = $request->validate([
            'restricted_reason'       => 'required|in:accuracy_dispute,unlawful_processing,legal_claim,opposition_pending',
            'restriction_review_date' => 'required|date|after:today',
            'restriction_case_id'     => 'nullable|integer|exists:oc_enamel_gdpr_requests,id',
        ]);

        $order->update([
            'restricted_at'           => now(),
            'restricted_reason'       => $validated['restricted_reason'],
            'restriction_review_date' => $validated['restriction_review_date'],
            'restriction_case_id'     => $validated['restriction_case_id'] ?? null,
        ]);

        $order->logSystemEvent(
            'Restrizione GDPR Art. 18 applicata — motivo: ' . $validated['restricted_reason'] .
            ' — revisione: ' . $validated['restriction_review_date'] .
            ($validated['restriction_case_id'] ? ' — Rif. GDPR #' . $validated['restriction_case_id'] : '')
        );

        Log::info('GDPR Art.18 restriction applied', [
            'order_code' => $order->code,
            'reason'     => $validated['restricted_reason'],
            'review'     => $validated['restriction_review_date'],
            'staff_uid'  => Auth::user()->uid,
        ]);

        return back()->with('success', 'Restrizione GDPR Art. 18 applicata all\'ordine ' . $order->code . '.');
    }

    /**
     * GDPR Art. 18 — Rimuove la restrizione del trattamento (solo staff).
     */
    public function removeRestriction(Order $order)
    {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);

        if (!$order->restricted_at) {
            return back()->with('error', 'L\'ordine non ha una restrizione attiva.');
        }

        $order->update([
            'restricted_at'           => null,
            'restricted_reason'       => null,
            'restriction_review_date' => null,
            'restriction_case_id'     => null,
        ]);

        $order->logSystemEvent('Restrizione GDPR Art. 18 rimossa.');

        Log::info('GDPR Art.18 restriction removed', [
            'order_code' => $order->code,
            'staff_uid'  => Auth::user()->uid,
        ]);

        return back()->with('success', 'Restrizione rimossa dall\'ordine ' . $order->code . '.');
    }

    /**
     * MDR Art. 87 — Segnalazione incidente (solo staff/PRRC).
     *
     * Calcola la scadenza di notifica in base alla gravità:
     *   imminente → 2 gg  (pericolo immediato per la salute pubblica — Art. 87(2))
     *   grave     → 15 gg (incidente grave — Art. 87(3))
     *   non_grave → 30 gg (incidente non grave — Art. 87(5))
     */
    public function reportIncident(Request $request, Order $order)
    {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);

        if ($order->incident_reported_at && !$order->incident_resolved_at) {
            return back()->with('error', 'L\'ordine ha già un incidente aperto. Chiuderlo prima di aprirne uno nuovo.');
        }

        $validated = $request->validate([
            'severity'         => 'required|in:imminente,grave,non_grave',
            'description'      => 'required|string|min:20|max:3000',
            'immediate_action' => 'nullable|string|max:1000',
        ]);

        $deadlineDays = match ($validated['severity']) {
            'imminente' => 2,
            'grave'     => 15,
            default     => 30,
        };

        $order->update([
            'incident_reported_at'          => now(),
            'incident_severity'             => $validated['severity'],
            'incident_notification_deadline' => now()->addDays($deadlineDays),
            'incident_resolved_at'          => null,
            'incident_resolution_notes'     => null,
        ]);

        $severityLabel = [
            'imminente' => 'IMMINENTE (2 gg)',
            'grave'     => 'GRAVE (15 gg)',
            'non_grave' => 'NON GRAVE (30 gg)',
        ][$validated['severity']];

        $order->logSystemEvent("MDR Art. 87: Incidente segnalato — Gravità: {$severityLabel}. " .
            "Scadenza notifica: {$order->incident_notification_deadline->format('d/m/Y')}.");

        Log::warning('MDR-INCIDENT: Incidente segnalato', [
            'order_code'  => $order->code,
            'severity'    => $validated['severity'],
            'deadline'    => $order->incident_notification_deadline->toDateString(),
            'staff_uid'   => Auth::user()->uid,
        ]);

        $this->notifyPrrcIncident($order, $validated['description'], $validated['immediate_action'] ?? null);

        return back()->with('success', "Incidente segnalato. Scadenza notifica MDR: {$order->incident_notification_deadline->format('d/m/Y')}.");
    }

    /**
     * MDR Art. 87 — Chiusura indagine incidente (solo staff/PRRC).
     */
    public function resolveIncident(Request $request, Order $order)
    {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);

        if (!$order->incident_reported_at) {
            return back()->with('error', 'L\'ordine non ha incidenti aperti.');
        }
        if ($order->incident_resolved_at) {
            return back()->with('error', 'L\'incidente è già chiuso.');
        }

        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:20|max:3000',
        ]);

        $order->update([
            'incident_resolved_at'      => now(),
            'incident_resolution_notes' => $validated['resolution_notes'],
        ]);

        $order->logSystemEvent('MDR Art. 87: Indagine incidente chiusa.');

        Log::info('MDR-INCIDENT: Incidente risolto', [
            'order_code' => $order->code,
            'staff_uid'  => Auth::user()->uid,
        ]);

        return back()->with('success', 'Indagine incidente chiusa per l\'ordine ' . $order->code . '.');
    }

    private function notifyPrrcIncident(Order $order, string $description, ?string $immediateAction): void
    {
        $prrcEmail = config('lab.prrc.email') ?: config('lab.manufacturer.email');
        if (!$prrcEmail) {
            Log::warning('MDR-INCIDENT: Email PRRC non configurata — notifica incidente saltata', [
                'order_code' => $order->code,
            ]);
            return;
        }

        $severity = strtoupper($order->incident_severity);
        $deadline = $order->incident_notification_deadline?->format('d/m/Y') ?? '—';

        $paragraphs = ["Descrizione:\n" . $description];
        if ($immediateAction) {
            $paragraphs[] = "Azioni immediate adottate:\n" . $immediateAction;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($prrcEmail)->queue(new \App\Mail\EnamelNotification([
                'subject'    => "🚨 Incidente MDR Art. 87 — Ordine {$order->code} — Gravità {$severity}",
                'title'      => 'Segnalazione Incidente MDR',
                'audience'   => 'internal',
                'paragraphs' => $paragraphs,
                'details'    => [
                    ['label' => 'Ordine',  'value' => $order->code],
                    ['label' => 'Gravità', 'value' => $severity],
                    ['label' => 'Scadenza notifica MDR Art. 87', 'value' => $deadline],
                ],
                'notes'      => ['Verificare se la segnalazione a EUDAMED/MHRA è necessaria prima della scadenza.'],
            ]));
        } catch (\Throwable $e) {
            Log::error('MDR-INCIDENT: Notifica PRRC fallita: ' . $e->getMessage(), [
                'order_code' => $order->code,
            ]);
        }
    }
}