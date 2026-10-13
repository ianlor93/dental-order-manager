<?php

namespace App\Http\Controllers;

use App\Models\{Order, Studio, Address};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, Schema, Mail};
use App\Mail\EnamelNotification;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) return redirect('/')->with('error', 'Sessione scaduta.');

            // Risoluzione studio: titolare (owner) o prescrittore membro via pivot
            $studio = \App\Support\AccessContext::for($user)->activeStudio()?->load('addresses');
            $today = Carbon::now()->startOfDay();
            $tomorrow = Carbon::now()->addDay()->startOfDay();

            if (\App\Support\AccessContext::for($user)->isStaff()) {
                $allAddresses = Address::has('studio')->with('studio')->get();
            } else {
                if (!$studio || !$studio->id) return redirect()->route('legal.onboarding');
                $allAddresses = $studio->addresses;
            }

            // Scope ordini coerente con la OrderPolicy (proprietà a livello studio):
            // il titolare/amministratore vede TUTTI gli ordini dello studio, inclusi
            // quelli inseriti dallo staff per conto del cliente (user_id = operatore interno).
            $baseQuery = $this->scopedOrders($user, $studio)->where('status', '!=', 'archived');

            $filter = $request->query('filter');
            $baseQuery->when($filter == 'overdue', fn($q) => $q->whereNotNull('delivery_date')->whereDate('delivery_date', '<', $today)->whereNotIn('status', ['completed', 'pronto_consegna']))
                      ->when($filter == 'today', fn($q) => $q->whereDate('delivery_date', $today)->whereNotIn('status', ['completed', 'pronto_consegna']))
                      ->when($filter == 'tomorrow', fn($q) => $q->whereDate('delivery_date', $tomorrow)->whereNotIn('status', ['completed', 'pronto_consegna']))
                      ->when($filter == 'urgent', fn($q) => $q->where('priority', 'high')->where('status', '!=', 'completed'))
                      ->when($filter == 'trial', fn($q) => $q->where('is_trial', true)->where('status', '!=', 'completed'));

            $baseQuery->when($request->search, fn($q, $s) => $q->where(fn($sq) => $sq->where('code', 'like', "%{$s}%")->orWhere('patient_ref', 'like', "%{$s}%")))
                      ->when($request->status, fn($q, $st) => $q->where('status', $st));

            $sq = $this->scopedOrders($user, $studio)->where('status', '!=', 'archived');
            $stats = [
                'overdue' => (clone $sq)->whereNotNull('delivery_date')->whereDate('delivery_date', '<', $today)->whereNotIn('status', ['completed', 'pronto_consegna'])->count(),
                'due_today' => (clone $sq)->whereDate('delivery_date', $today)->whereNotIn('status', ['completed', 'pronto_consegna'])->count(),
                'due_tomorrow' => (clone $sq)->whereDate('delivery_date', $tomorrow)->whereNotIn('status', ['completed', 'pronto_consegna'])->count(),
                'urgent' => (clone $sq)->where('priority', 'high')->where('status', '!=', 'completed')->count(),
                'in_trial' => (clone $sq)->where('is_trial', true)->where('status', '!=', 'completed')->count(),
            ];

            $orders = $baseQuery->with(['studio', 'address', 'files', 'production'])->latest()->paginate(20)->appends($request->query());
            return view('dashboard', compact('user', 'studio', 'orders', 'stats', 'allAddresses'));

        } catch (\Throwable $e) {
            Log::error("DASHBOARD-FATAL: " . $e->getMessage());
            abort(500);
        }
    }

    /**
     * Query base degli ordini visibili all'utente corrente, coerente con la
     * OrderPolicy (proprietà a livello studio).
     *
     * - Staff/Lab → tutti gli ordini del sistema.
     * - Titolare ('owner') / Amministratore ('admin') → tutti gli ordini dello
     *   studio attivo, inclusi quelli inseriti dallo staff per conto del cliente
     *   (dove user_id è l'operatore interno, non il cliente).
     * - Prescrittore / Lettore → solo gli ordini da loro creati.
     *
     * Fail-closed: per i non-staff, nessuno studio attivo ⇒ nessun ordine visibile.
     * Non amplia in alcun modo la visibilità cross-studio garantita dalla OrderPolicy.
     */
    private function scopedOrders(\App\Models\User $user, ?Studio $studio): \Illuminate\Database\Eloquent\Builder
    {
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            return Order::query();
        }

        // Fail-closed: senza studio attivo non si vede nulla.
        if (!$studio || !$studio->id) {
            return Order::whereRaw('1 = 0');
        }

        $query = Order::where('studio_id', $studio->id);

        // Solo i gestori dello studio vedono l'intero studio; gli altri solo i propri.
        if (!\App\Support\AccessContext::for($user)->isStudioManager($studio)) {
            $query->where('user_id', $user->uid);
        }

        return $query;
    }

    /**
     * ✨ NUOVO: Anagrafica Amministrativa (Riservata admin e Amministrazione)
     */
    public function customers(Request $request)
    {
        $user = Auth::user();
        
        // Verifica restrizione imperativa: admin o gruppo Amministrazione
        if (!\App\Support\AccessContext::for($user)->isBackOffice()) {
            Log::warning("MDR-SECURITY: Accesso negato anagrafica per {$user->uid}");
            abort(403, 'Area riservata all\'Ufficio Amministrativo.');
        }

        $query = Studio::with(['addresses', 'user']);
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                  ->orWhere('vat_number', 'like', "%{$s}%")
                  ->orWhere('dentist_name', 'like', "%{$s}%"));
        }

        $studios = $query->latest()->paginate(25)->appends($request->query());
        return view('studios.index', compact('user', 'studios'));
    }

    public function archive(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) return redirect('/')->with('error', 'Sessione scaduta.');

            $studio = \App\Support\AccessContext::for($user)->activeStudio();
            if (!\App\Support\AccessContext::for($user)->isStaff() && (!$studio || !$studio->id)) return redirect()->route('legal.onboarding');

            $baseQuery = $this->scopedOrders($user, $studio)->where('status', 'archived');

            $baseQuery->when($request->search, fn($q, $s) => $q->where(fn($sq) => $sq->where('code', 'like', "%{$s}%")->orWhere('patient_ref', 'like', "%{$s}%")));
            $orders = $baseQuery->with(['studio', 'address', 'production'])->latest('archived_at')->paginate(25)->appends($request->query());

            foreach($orders as $order) {
                if (!$order->pms_next_review_at && $order->archived_at) {
                    $years = in_array($order->risk_class, ['IIb', 'III']) ? 1 : 2;
                    $order->pms_next_review_at = $order->archived_at->addYears($years);
                    if ($order->pms_next_review_at->isPast() && $order->pms_status !== 'incident_reported') {
                        $order->pms_status = 'review_due';
                    }
                    $order->save();
                }
            }

            return view('dashboard-archive', compact('user', 'orders'));

        } catch (\Throwable $e) {
            Log::error("ARCHIVE-DASHBOARD-FATAL: " . $e->getMessage());
            abort(500);
        }
    }

    public function updatePmsStatus(Request $request, Order $order)
    {
        $user = Auth::user();
        if (!$user->isStaff() && $user->uid !== $order->user_id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $action   = $request->input('action');
        $feedback = strip_tags(trim($request->input('feedback') ?? ''));

        // --- APERTURA INCIDENTE ---
        if ($action === 'trigger_incident') {

            $severity = $request->input('severity');
            if (!in_array($severity, ['imminente', 'grave', 'non_grave'])) {
                return response()->json(['error' => 'Specificare la gravità dell\'incidente: imminente, grave o non_grave.'], 422);
            }
            if (empty(trim($feedback ?? ''))) {
                return response()->json(['error' => 'La descrizione dell\'incidente è obbligatoria.'], 422);
            }

            // Calcola scadenza notifica Ministero (MDR Art. 87)
            $deadlineDays = match($severity) {
                'imminente' => 2,
                'grave'     => 15,
                'non_grave' => 30,
            };
            $now      = now();
            $deadline = $now->copy()->addDays($deadlineDays);

            $order->update([
                'pms_status'                    => 'incident_reported',
                'incident_reported_at'          => $now,
                'incident_severity'             => $severity,
                'incident_notification_deadline'=> $deadline,
                'incident_resolved_at'          => null,
                'incident_resolution_notes'     => null,
            ]);

            $severityLabel = match($severity) {
                'imminente' => 'RISCHIO IMMINENTE (scadenza notifica: 2 giorni)',
                'grave'     => 'INCIDENTE GRAVE (scadenza notifica: 15 giorni)',
                'non_grave' => 'INCIDENTE NON GRAVE (scadenza notifica: 30 giorni)',
            };

            $order->logSystemEvent(
                "🚨 VIGILANZA ATTIVATA [{$severityLabel}]: Segnalazione inviata da " .
                (\App\Support\AccessContext::for($user)->isStaff() ? 'STAFF' : 'CLINICO') .
                ". Scadenza notifica Ministero: " . $deadline->format('d/m/Y') .
                ". Descrizione: " . $feedback
            );

            // Notifica immediata al PRRC via email
            $prrcEmail = config('lab.prrc.email');
            if ($prrcEmail) {
                Mail::to($prrcEmail)->queue(new EnamelNotification([
                    'subject'    => "🚨 INCIDENTE MDR [{$severityLabel}] — Ordine {$order->code}",
                    'title'      => 'Segnalazione Incidente MDR — Azione Richiesta',
                    'audience'   => 'internal',
                    'paragraphs' => [
                        "È stato segnalato un incidente sul dispositivo {$order->code}.",
                    ],
                    'details'    => [
                        ['label' => 'Gravità',      'value' => $severityLabel],
                        ['label' => 'Segnalato da', 'value' => \App\Support\AccessContext::for($user)->isStaff() ? 'Staff interno' : ($user->displayname ?? $user->uid)],
                        ['label' => 'Descrizione',  'value' => $feedback],
                        ['label' => '⚠️ Scadenza notifica Ministero', 'value' => $deadline->format('d/m/Y')],
                    ],
                    'notes'      => [
                        "Accedi alla piattaforma per gestire l'indagine e documentare le misure correttive.",
                    ],
                    'actionUrl'  => route('dashboard.archive'),
                    'actionText' => 'Vai all\'Archivio',
                ]));
            }

            Log::warning('MDR-INCIDENT-OPENED: Incidente aperto', [
                'order_code' => $order->code,
                'severity'   => $severity,
                'deadline'   => $deadline->toDateString(),
                'reported_by'=> $user->uid,
            ]);
        }

        // --- FEEDBACK CLINICO PERIODICO (nessun incidente) ---
        elseif ($action === 'record_feedback') {
            if (\App\Support\AccessContext::for($user)->isStaff()) {
                return response()->json(['error' => 'Solo il Clinico può registrare feedback clinico.'], 403);
            }
            if ($order->pms_status === 'incident_reported') {
                return response()->json(['error' => 'Incidente aperto: impossibile registrare feedback. Risolvere prima l\'incidente.'], 422);
            }

            $years = in_array($order->risk_class, ['IIb', 'III']) ? 1 : 2;
            $order->update([
                'pms_status'        => 'active',
                'pms_last_review_at'=> now(),
                'pms_next_review_at'=> now()->addYears($years),
            ]);
            $order->logSystemEvent("✅ SORVEGLIANZA AGGIORNATA: Il Clinico ha registrato feedback positivo. Prossima revisione: " . now()->addYears($years)->format('d/m/Y') . ".");
        }

        // --- CHIUSURA INCIDENTE (solo staff) ---
        elseif ($action === 'resolve_incident') {
            if (!\App\Support\AccessContext::for($user)->isStaff()) {
                return response()->json(['error' => 'Solo lo Staff può chiudere un incidente.'], 403);
            }
            if ($order->pms_status !== 'incident_reported') {
                return response()->json(['error' => 'Nessun incidente aperto su questo ordine.'], 422);
            }

            $resolutionNotes = trim($request->input('resolution_notes') ?? '');
            if (strlen($resolutionNotes) < 20) {
                return response()->json(['error' => 'Le note di risoluzione sono obbligatorie (min. 20 caratteri): indicare causa, misure adottate ed esito.'], 422);
            }

            $years = in_array($order->risk_class, ['IIb', 'III']) ? 1 : 2;
            $order->update([
                'pms_status'                => 'active',
                'pms_last_review_at'        => now(),
                'pms_next_review_at'        => now()->addYears($years),
                'incident_resolved_at'      => now(),
                'incident_resolution_notes' => $resolutionNotes,
            ]);

            $order->logSystemEvent(
                "🏁 VIGILANZA RISOLTA: Indagine chiusa da STAFF ({$user->uid}). " .
                "Note: {$resolutionNotes}"
            );

            Log::info('MDR-INCIDENT-CLOSED: Incidente chiuso', [
                'order_code'  => $order->code,
                'resolved_by' => $user->uid,
            ]);
        }

        else {
            return response()->json(['error' => 'Azione non riconosciuta.'], 422);
        }

        return response()->json(['success' => true, 'new_status' => $order->pms_status]);
    }

    public function downloadPmsReport(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['comments' => fn($q) => $q->orderBy('created_at', 'desc'), 'studio', 'address', 'production']);

        $pdf = Pdf::loadView('pdfs.pms_report', compact('order'));
        return $pdf->download("PMS_SUMMARY_{$order->code}.pdf");
    }
}