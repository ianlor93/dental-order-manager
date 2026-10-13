<?php

namespace App\Http\Controllers;

use App\Models\{Order, Studio, DeliveryNote};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Mail};
use App\Mail\EnamelNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class LogisticsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!\App\Support\AccessContext::for($user)->isBackOffice()) {
            abort(403, 'Accesso riservato all\'Ufficio Amministrativo.');
        }

        $studios = Studio::whereHas('orders', function ($q) {
            $q->readyForDelivery();
        })->with(['orders' => function ($q) {
            $q->readyForDelivery()
              ->with('production')
              ->orderBy('updated_at', 'desc');
        }])->orderBy('name')->get();

        return view('logistics.index', compact('studios', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!\App\Support\AccessContext::for($user)->isBackOffice()) {
            abort(403, 'Azione non autorizzata.');
        }

        $request->validate([
            'studio_id' => 'required|exists:oc_enamel_studios,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:oc_enamel_orders,id',
            'courier' => 'nullable|string|max:255',
            'tracking_code' => 'nullable|string|max:255',
        ]);

        try {
            $noteId = null;

            DB::transaction(function () use ($request, &$noteId) {
                $note = DeliveryNote::create([
                    'studio_id' => $request->studio_id,
                    'courier' => $request->courier ?? 'Consegna Diretta',
                    'tracking_code' => $request->tracking_code,
                    'delivery_date' => now(),
                ]);

                Order::whereIn('id', $request->order_ids)
                    ->update(['delivery_note_id' => $note->id]);

                $orders = Order::find($request->order_ids);
                foreach ($orders as $order) {
                    $order->logSystemEvent("LOGISTICA: Spedito con distinta #{$note->id} ({$note->courier})");
                }
                
                $noteId = $note->id;

                // 📧 INVIO MAIL
                $studio = Studio::find($request->studio_id);
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
                                'subject' => "Merce in Spedizione - DDT #{$note->id}",
                                'title'   => "Spedizione Partita",
                                'message' => "Gentile {$studio->name},\nabbiamo affidato al corriere la tua merce (Distinta #{$note->id}).\n\nCorriere: {$note->courier}\nTracking: " . ($note->tracking_code ?? 'N/D') . "\n\nCondivideremo a breve il DDT.",
                                'actionUrl' => route('welcome'),
                                'actionText' => "Area Clienti"
                            ]));
                        } catch (\Exception $e) {
                            Log::error("EMAIL-ERROR (Logistica): " . $e->getMessage());
                        }
                    }
                }
            });

            return redirect()->route('logistics.index')
                ->with('success', 'Spedizione registrata correttamente.')
                ->with('download_note_id', $noteId);

        } catch (\Exception $e) {
            Log::error("LOGISTICS-ERROR: " . $e->getMessage());
            return back()->with('error', 'Errore: ' . $e->getMessage());
        }
    }

    public function download(DeliveryNote $deliveryNote)
    {
        $user = Auth::user();
        if (!\App\Support\AccessContext::for($user)->isBackOffice()) {
            abort(403, 'Non autorizzato');
        }

        $studio = $deliveryNote->studio()->with('addresses')->first();
        $orders = $deliveryNote->orders;
        $filename = "DDT_" . $deliveryNote->id . "_" . Str::slug($studio->name) . ".pdf";
        
        $pdf = Pdf::loadView('pdfs.delivery_note', [
            'note' => $deliveryNote,
            'studio' => $studio,
            'orders' => $orders,
            'generated_at' => now()
        ]);

        return $pdf->download($filename);
    }
}