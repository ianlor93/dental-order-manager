<?php

namespace App\Http\Controllers;

use App\Models\{Order, OrderProduction, MdrCatalog, ArchiveAccess};
use App\Jobs\{ProcessOrderJob, MoveToProductionJob};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Auth, Storage, Mail};
use App\Mail\EnamelNotification;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderProductionController extends Controller
{
    public function createPrescription(Order $order) {
        $user = Auth::user();
        return view('orders.prescription_draft', compact('order', 'user'));
    }
    
    public function storePrescription(Request $request, Order $order) {
        $validated = $request->validate([
            'prescriber_qualification' => 'required|string|max:500',
            'design_features'          => 'required|string|max:2000',
        ]);
        $order->update(array_merge($validated, ['status' => 'to_be_validated']));
        
        // 📧 EVENTO B: Prescrizione Compilata (NEXTCLOUD LOOKUP)
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
                        'subject' => "Prescrizione Compilata - Ordine #{$order->code}",
                        'title'   => "Prescrizione in Validazione",
                        'message' => "Gentile {$studio->name},\nla prescrizione per l'ordine {$order->code} (Paziente: {$order->patient_ref}) è stata compilata ed è ora in fase di validazione finale.\n\nAccedi subito all'area personale per la validazione.",
                        'actionUrl' => route('welcome'),
                        'actionText' => "Stato Ordine"
                    ]));
                } catch (\Exception $e) {
                    Log::error("EMAIL-ERROR (Prescrizione): " . $e->getMessage());
                }
            }
        }

        return redirect()->route('dashboard')->with('success', 'Bozza inviata per revisione.');
    }

    public function cancelPrescriptionDraft(Order $order) {
        $this->authorize('validate', $order);
        if ($order->status !== 'to_be_validated') {
            return back()->with('error', 'La prescrizione non è in stato di revisione.');
        }

        $order->update([
            'prescriber_qualification' => null,
            'design_features' => null,
            // 'processing' e NON 'scanning': in 'scanning' non compare alcun pulsante per
            // ricompilare la prescrizione (il bottone "Prescrizione" esiste solo in 'processing'),
            // quindi dopo l'annullamento l'ordine resterebbe bloccato in uno stato cieco.
            'status' => 'processing',
        ]);

        return redirect()->route('dashboard')->with('success', 'Prescrizione annullata, ordine riportato in lavorazione.');
    }

    public function previewPrescription(Order $order) {
        $this->authorize('view', $order);
        $user = Auth::user();
        $order->load(['address', 'studio']);
        return view('orders.prescription_preview', compact('order', 'user'));
    }

    public function validatePrescription(Order $order) {
        $this->authorize('validate', $order);
        $timestamp = now();
        $dataToHash = implode('|', [
            $order->code,
            strtoupper($order->patient_ref),
            $order->prescriber_qualification,
            $order->design_features,
            $timestamp->toDateTimeString()
        ]);
        
        $order->update([
            'validated_at' => $timestamp, 
            'validation_hash' => hash('sha256', $dataToHash),
            'status' => 'validated', 
            'is_locked' => true
        ]);

        $pdf = Pdf::loadView('pdfs.prescription', ['order' => $order]);
        $fileName = "Prescrizione_{$order->code}.pdf";
        $fullPath = "{$order->directory_name}/{$fileName}";
        
        Storage::disk('unraid')->put($fullPath, $pdf->output());
        
        $physicalPath = Storage::disk('unraid')->path($fullPath);
        if (file_exists($physicalPath)) {
            chmod($physicalPath, 0777);
        }

        return redirect()->route('dashboard')->with('success', 'Prescrizione firmata digitalmente.');
    }

    public function startProduction(Order $order) {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);
        try {
            MoveToProductionJob::dispatchSync($order);
        } catch (\Throwable $e) {
            Log::error("MDR-STARTPROD-FAIL [{$order->code}]: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Spostamento in Laboratorio non riuscito per ' . $order->code . ': ' . $e->getMessage());
        }
        // FIX ⑥ — verifica l'esito reale invece di flashare successo a prescindere.
        $order->refresh();
        if ($order->status !== 'in_progettazione') {
            return redirect()->route('dashboard')->with('error', "Spostamento in Laboratorio NON completato per {$order->code} (stato: {$order->status}). Controlla i commenti di sistema.");
        }
        return redirect()->route('dashboard')->with('success', 'Lavoro spostato in Laboratorio (CAD).');
    }

    public function create(Order $order) { 
        $normalizedCategory = ucwords(str_replace('_', ' ', $order->device_type));
        $availableDevices = MdrCatalog::where('category', $normalizedCategory)->where('is_active', true)->orderBy('name')->get();
        if ($availableDevices->isEmpty()) {
            $availableDevices = MdrCatalog::where('category', $order->device_type)->where('is_active', true)->orderBy('name')->get();
        }
        return view('orders.production', [
            'order' => $order, 
            'production' => $order->production()->firstOrCreate(['order_id' => $order->id]),
            'availableDevices' => $availableDevices
        ]); 
    }

    public function store(Request $request, Order $order) {
        $action = $request->input('action');
        try {
            DB::transaction(function () use ($order, $request, $action) {
                $production = $order->production()->firstOrCreate(['order_id' => $order->id]);
                
                $allowed = [
                    'cad_operator', 'cad_teeth_library', 'cad_implant_library', 'cad_notes',
                    'cam_data', 'lab_color', 'lab_operator', 'lab_materials', 'lab_notes',
                    'qc_verifier', 'qc_checklist', 'qc_status',
                ];
                $dataToUpdate = array_filter($request->only($allowed), fn($v) => $v !== null && $v !== '');

                $production->update($dataToUpdate);

                if ($request->has('manufactured_devices_slugs')) {
                    $slugs = $request->input('manufactured_devices_slugs');
                    $devicesData = [];
                    $highestRisk = 'I';
                    $riskWeights = ['I' => 1, 'IIa' => 2, 'IIb' => 3, 'III' => 4];
                    $appliedRule = 'Regola 8';

                    foreach ($slugs as $slug) {
                        $dev = MdrCatalog::where('slug', $slug)->first();
                        if ($dev) {
                            $devicesData[] = [
                                'slug' => $dev->slug,
                                'name' => $dev->name,
                                'duration_use' => $dev->duration_use, 
                                'risk_class' => $dev->risk_class,
                                'mdr_rule' => $dev->mdr_rule
                            ];
                            if ($riskWeights[$dev->risk_class] > $riskWeights[$highestRisk]) {
                                $highestRisk = $dev->risk_class;
                                $appliedRule = $dev->mdr_rule;
                            }
                        }
                    }

                    $order->update([
                        'manufactured_devices' => $devicesData,
                        'risk_class' => $highestRisk,
                        'mdr_rule_applied' => $appliedRule
                    ]);
                }

                switch ($action) {
                    case 'step_cad': $order->update(['status' => 'in_produzione']); break;
                    case 'step_cam': $order->update(['status' => 'finalizzazione']); break;
                    case 'step_lab': $order->update(['status' => 'controllo_qualita']); break;
                    
                    case 'finalize': 
                        $timestamp = now();
                        $camLots = $production->cam_data['lots'] ?? [];
                        $firstLot = $camLots[0]['material_id'] ?? 'N/D';
                        $allMaterials = collect($camLots)->map(fn($l) => $l['material_name'] ?? 'Sconosciuto')->implode(', ');

                        $prodData = implode('|', [
                            $order->code,
                            $firstLot,
                            $allMaterials,
                            $timestamp->toDateTimeString()
                        ]);
                        $pHash = hash('sha256', $prodData);

                        $confData = implode('|', [
                            $order->code,
                            $order->risk_class,
                            $request->input('qc_verifier') ?? 'STAFF',
                            $timestamp->toDateTimeString()
                        ]);
                        $cHash = hash('sha256', $confData);

                        $order->update([
                            'status' => 'pronto_consegna',
                            'lot_number' => $firstLot,
                            'materials_used' => $allMaterials,
                            'production_hash' => $pHash,
                            'conformity_hash' => $cHash
                        ]); 

                        $production->update([
                            'released_at' => $timestamp, 
                            'qc_status' => 'approved'
                        ]);
                        break;
                }
            });

            if ($action === 'finalize') {
                ProcessOrderJob::dispatchSync($order, 'move_to_fin');
                // FIX ⑥ — move_to_fin cattura le eccezioni e imposta 'error_system': rileggo l'esito reale.
                $order->refresh();
                if ($order->status === 'error_system') {
                    return redirect()->route('dashboard')->with('error', "Spostamento in Finitura FALLITO per {$order->code}: controlla i commenti di sistema dell'ordine.");
                }
                return redirect()->route('dashboard')->with('success', 'Lavoro completato, firmato digitalmente e spostato in Finitura.');
            }
            return redirect()->back()->with('success', 'Dati salvati correttamente.');
        } catch (\Exception $e) {
            Log::error("MDR-PRODUCTION-STORE-ERROR: " . $e->getMessage());
            return redirect()->back()->with('error', 'Errore critico durante il salvataggio: ' . $e->getMessage());
        }
    }

    public function generateMdrPdfs(Order $order) {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);
        ProcessOrderJob::dispatchSync($order, 'generate_pdfs');
        // FIX ⑥ — surface: se la generazione ha lanciato, il job imposta 'error_system'.
        $order->refresh();
        if ($order->status === 'error_system') {
            return back()->with('error', "Generazione PDF MDR FALLITA per {$order->code}: controlla i commenti di sistema.");
        }
        return back()->with('success', 'Fascicolo PDF MDR generato correttamente.');
    }

    public function archiveFinal(Order $order) {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) abort(403);
        $this->authorize('validate', $order); // FIX ⑤ — allineo all'OrderPolicy come gli altri endpoint

        if (is_null($order->delivery_note_id)) {
            return back()->with('error', 'Impossibile archiviare: La spedizione (DDT) non è stata ancora generata in Logistica.');
        }

        // MDR Art. 10(8): l'archivio legale deve contenere il fascicolo completo.
        $missing = $order->missingMdrDocuments('unraid_fin_l');
        if (!empty($missing)) {
            return back()->with('error', 'Impossibile archiviare: mancano i seguenti documenti MDR su FIN_L. Clicca "Genera Doc" prima di archiviare. File mancanti: ' . implode(', ', $missing));
        }

        ProcessOrderJob::dispatchSync($order, 'move_to_arch');
        // FIX ⑥ — verifica l'esito REALE: move_to_arch abortisce (seal/copia/pulizia) impostando 'error_system'.
        // Prima si flashava "archiviato" anche su abort.
        $order->refresh();
        if ($order->status !== 'archived') {
            return back()->with('error', "Archiviazione NON completata per {$order->code} (stato: {$order->status}). Controlla i commenti di sistema dell'ordine e riprova.");
        }
        return back()->with('success', 'Lavoro archiviato definitivamente.');
    }

    public function downloadPrescription(Order $order) {
        $this->authorize('view', $order);
        $order->load(['address', 'studio']);
        ArchiveAccess::log($order, 'download_prescription', "Prescrizione_{$order->code}.pdf");
        return Pdf::loadView('pdfs.prescription', compact('order'))->download("Prescrizione_{$order->code}.pdf");
    }

    public function downloadProductionReport(Order $order) {
        $this->authorize('view', $order);
        $production = $order->production;
        if (!$production) return back()->with('error', 'Dati non disponibili.');
        ArchiveAccess::log($order, 'download_pdf', "Scheda_Tecnica_{$order->code}.pdf");
        return Pdf::loadView('pdfs.production_report', compact('order', 'production'))->download("Scheda_Tecnica_{$order->code}.pdf");
    }

    public function downloadLabel(Order $order) {
        $this->authorize('view', $order);
        $production = $order->production ?: new OrderProduction();
        ArchiveAccess::log($order, 'download_pdf', "Etichetta_{$order->code}.pdf");
        return Pdf::loadView('pdfs.shipping_label', compact('order', 'production'))->download("Etichetta_{$order->code}.pdf");
    }

    public function downloadConformity(Order $order) {
        $this->authorize('view', $order);
        $production = $order->production;
        if (!$production || empty($production->qc_verifier)) return back()->with('error', 'Eseguire prima il QC.');
        ArchiveAccess::log($order, 'download_pdf', "Conformita_{$order->code}.pdf");
        return Pdf::loadView('pdfs.conformity_declaration', compact('order', 'production'))->download("Conformita_{$order->code}.pdf");
    }

    public function downloadPatientInfo(Order $order) {
        $this->authorize('view', $order);
        // L'informativa GDPR Art. 13 richiede anche il Titolare del Trattamento (medico/studio),
        // non solo $order. Stessa costruzione dati di ProcessOrderJob::preparePdfData()
        // (punto unico di verità). Null-safe: address_id è nullable su ordini non finalizzati.
        $order->load(['studio', 'address']);
        $data = [
            'order'          => $order,
            'clinician'      => $order->address?->dentist_name ?? $order->studio?->dentist_name ?? 'N/D',
            'studio_name'    => $order->studio?->name ?? 'N/D',
            'studio_address' => $order->address?->address ?? 'N/D',
        ];
        ArchiveAccess::log($order, 'download_pdf', "Informativa_{$order->code}.pdf");
        return Pdf::loadView('pdfs.patient_info', $data)->download("Informativa_{$order->code}.pdf");
    }
}