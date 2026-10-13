<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmministrazioneController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $stats = [
            'leads_totali'    => Lead::count(),
            'lead_convertiti' => Lead::whereNotNull('converted_at')->count(),
            'supporto_aperte' => SupportRequest::where('status', 'aperta')->count(),
        ];

        $leads = Lead::latest()->paginate(25);

        $supportRequests = SupportRequest::with('studio')
            ->latest()
            ->paginate(20, ['*'], 'sr_page');

        return view('amministrazione.index', [
            'user'            => $request->user(),
            'stats'           => $stats,
            'leads'           => $leads,
            'supportRequests' => $supportRequests,
        ]);
    }

    public function storeSupportRequest(Request $request)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $validated = $request->validate([
            'studio_id' => ['nullable', 'integer', 'exists:oc_enamel_studios,id'],
            'user_uid'  => ['required', 'string', 'max:100'],
            'subject'   => ['required', 'string', 'min:5', 'max:200'],
            'body'      => ['required', 'string', 'min:10', 'max:3000'],
            'priority'  => ['required', 'in:normale,urgente'],
        ]);

        SupportRequest::create($validated);

        return back()->with('success', 'Richiesta di assistenza creata.');
    }

    public function updateSupportStatus(Request $request, SupportRequest $supportRequest)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $validated = $request->validate([
            'status'      => ['required', 'in:aperta,in_lavorazione,chiusa'],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $supportRequest->update([
            'status'      => $validated['status'],
            'staff_notes' => $validated['staff_notes'],
            'closed_at'   => $validated['status'] === 'chiusa' ? now() : null,
        ]);

        return back()->with('success', "Richiesta #{$supportRequest->id} aggiornata.");
    }

    public function destroySupportRequest(Request $request, SupportRequest $supportRequest)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $supportRequest->delete();

        return back()->with('success', "Richiesta #{$supportRequest->id} eliminata.");
    }

    /**
     * Vaglio — APPROVA un lead `da_vagliare`: registra solo la decisione.
     * Nessuna interazione con NC qui (abilita+gruppo+email = T4b, ramo del runner).
     */
    public function approveLead(Request $request, Lead $lead)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $validated = $request->validate([
            // Gruppo scelto manualmente dall'admin, ristretto ai gruppi cliente di config.
            'assigned_group' => ['required', 'string', Rule::in(array_keys(config('funnel.assignable_groups', [])))],
        ]);

        if ($lead->status !== Lead::STATUS_DA_VAGLIARE) {
            return back()->with('error', "Solo i lead «da vagliare» possono essere approvati.");
        }

        // Campi guarded (fuori da $fillable, T1) → set espliciti.
        $lead->status         = Lead::STATUS_APPROVATO;
        $lead->assigned_group = $validated['assigned_group'];
        $lead->vetted_by_uid  = $request->user()->uid;
        $lead->vetted_at      = now();
        $lead->save();

        return back()->with('success', "Lead {$lead->email} approvato (gruppo «{$lead->assigned_group}»).");
    }

    /**
     * Vaglio — RIFIUTA un lead `da_vagliare` con motivo.
     */
    public function rejectLead(Request $request, Lead $lead)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if ($lead->status !== Lead::STATUS_DA_VAGLIARE) {
            return back()->with('error', "Solo i lead «da vagliare» possono essere rifiutati.");
        }

        $lead->status           = Lead::STATUS_RIFIUTATO;
        $lead->rejection_reason = $validated['rejection_reason'];
        $lead->vetted_by_uid    = $request->user()->uid;
        $lead->vetted_at        = now();
        $lead->save();

        return back()->with('success', "Lead {$lead->email} rifiutato.");
    }

    /**
     * "Scarta" un lead — la tabella `leads` è append-only: NIENTE hard-delete.
     * Diventa un soft-reject tracciato (status=rifiutato). Una cancellazione vera
     * (richiesta GDPR) è un'azione deliberata separata, non questo bottone di lista.
     */
    public function destroyLead(Request $request, Lead $lead)
    {
        abort_unless(\App\Support\AccessContext::for($request->user())->isStaff(), 403);

        $lead->status           = Lead::STATUS_RIFIUTATO;
        $lead->rejection_reason = $lead->rejection_reason ?: 'Scartato dalla lista amministrazione';
        $lead->vetted_by_uid    = $request->user()->uid;
        $lead->vetted_at        = now();
        $lead->save();

        return back()->with('success', "Lead {$lead->email} scartato (segnato come rifiutato, non eliminato).");
    }
}
