<?php

namespace App\Http\Controllers;

use App\Models\{Order, Studio, DeliveryNote};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, DB};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingController extends Controller
{
    private function checkPermissions()
    {
        $user = Auth::user();
        if (!\App\Support\AccessContext::for($user)->isBackOffice()) {
            Log::warning("BILLING-SECURITY: Accesso negato a {$user->uid}");
            abort(403, 'Accesso riservato all\'Ufficio Amministrativo.');
        }
    }

    public function index(Request $request)
    {
        $this->checkPermissions();
        $user = Auth::user();
        $month    = $request->input('month');
        $year     = $request->input('year');
        $studioId = $request->input('studio_id');
        if ($month !== null && (!ctype_digit((string)$month) || $month < 1 || $month > 12)) $month = null;
        if ($year  !== null && (!ctype_digit((string)$year)  || $year < 2000 || $year > 2100)) $year  = null;
        $allStudios = Studio::orderBy('name')->get(['id', 'name']);

        $filterClosure = function ($q) use ($month, $year) {
            $q->unbilled();
            if ($month) $q->whereMonth('delivery_date', $month);
            if ($year) $q->whereYear('delivery_date', $year);
            $q->orderBy('delivery_date', 'desc');
        };

        $query = Studio::whereHas('deliveryNotes', $filterClosure);
        if ($studioId) $query->where('id', $studioId);

        $studios = $query->with(['deliveryNotes' => function ($q) use ($filterClosure) {
                $filterClosure($q);
                $q->withCount('orders');
            }])
            ->orderBy('name')
            ->get();

        return view('billing.index', [
            'user' => $user,
            'studios' => $studios,
            'allStudios' => $allStudios, 
            'totalShipments' => $studios->sum(fn($s) => $s->deliveryNotes->count()),
            'totalOrders' => $studios->sum(fn($s) => $s->deliveryNotes->sum('orders_count')),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentStudio' => $studioId,
            'isArchive' => false
        ]);
    }

    public function archive(Request $request)
    {
        $this->checkPermissions();
        $user = Auth::user();
        $month    = $request->input('month', now()->month);
        $year     = $request->input('year', now()->year);
        $studioId = $request->input('studio_id');
        if ($month !== null && (!ctype_digit((string)$month) || $month < 1 || $month > 12)) $month = now()->month;
        if ($year  !== null && (!ctype_digit((string)$year)  || $year < 2000 || $year > 2100)) $year  = now()->year;
        $allStudios = Studio::orderBy('name')->get(['id', 'name']);

        $filterClosure = function ($q) use ($month, $year) {
            $q->whereNotNull('billed_at');
            if ($month) $q->whereMonth('billed_at', $month);
            if ($year) $q->whereYear('billed_at', $year);
            $q->orderBy('billed_at', 'desc');
        };

        $query = Studio::whereHas('deliveryNotes', $filterClosure);
        if ($studioId) $query->where('id', $studioId);

        $studios = $query->with(['deliveryNotes' => function ($q) use ($filterClosure) {
                $filterClosure($q);
                $q->withCount('orders');
            }])
            ->orderBy('name')
            ->get();

        return view('billing.index', [
            'user' => $user,
            'studios' => $studios,
            'allStudios' => $allStudios,
            'totalShipments' => $studios->sum(fn($s) => $s->deliveryNotes->count()),
            'totalOrders' => $studios->sum(fn($s) => $s->deliveryNotes->sum('orders_count')),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentStudio' => $studioId,
            'isArchive' => true
        ]);
    }

    public function downloadMonthlySummary(Request $request)
    {
        $this->checkPermissions();

        $validated = $request->validate([
            'studio_id'  => 'required|exists:oc_enamel_studios,id',
            'note_ids'   => 'required|array|min:1',
            'note_ids.*' => 'integer|exists:oc_enamel_delivery_notes,id',
        ]);

        $studio = Studio::with('addresses')->findOrFail($validated['studio_id']);
        $notes = DeliveryNote::where('studio_id', $validated['studio_id'])
            ->whereIn('id', $validated['note_ids'])
            ->unbilled()
            ->with(['orders'])
            ->orderBy('delivery_date')
            ->get();

        if ($notes->isEmpty()) {
            return back()->with('error', 'Nessuna spedizione trovata per i DDT selezionati.');
        }

        $pdf = Pdf::loadView('pdfs.monthly_summary', [
            'studio'      => $studio,
            'notes'       => $notes,
            'generatedAt' => now(),
        ]);

        return $pdf->download("Riepilogo_" . Str::slug($studio->name) . ".pdf");
    }

    public function markAsBilled(Request $request)
    {
        $this->checkPermissions();
        $validated = $request->validate([
            'studio_id'      => 'required|exists:oc_enamel_studios,id',
            'invoice_number' => ['nullable', 'string', 'max:50', 'regex:/^[\w\s\-\/\.]+$/'],
        ]);
        DB::transaction(function () use ($validated) {
            DeliveryNote::where('studio_id', $validated['studio_id'])->unbilled()->update([
                'billed_at'           => now(),
                'monthly_summary_ref' => $validated['invoice_number'] ?? null,
            ]);
        });
        return redirect()->route('billing.index')->with('success', "Spedizioni fatturate.");
    }

}