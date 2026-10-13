<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use App\Core\TierManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Per l'aggiornamento diretto della quota Nextcloud
use Carbon\Carbon;

class StudioController extends Controller
{
    /**
     * Visualizza l'elenco di tutti gli studi (Riservato allo STAFF).
     * Aggiornato con logica di ricerca e paginazione.
     */
    public function index(Request $request)
    {
        // Solo lo staff può vedere la lista completa degli studi
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) {
            abort(403, 'Accesso non autorizzato.');
        }

        $query = Studio::withCount('orders');

        // ✨ LOGICA DI RICERCA (Per far funzionare il filtro nella vista)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('vat_number', 'LIKE', "%{$search}%")
                  ->orWhere('dentist_name', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%");
            });
        }

        $studios = $query->latest()->paginate(15);
        $user = Auth::user();

        return view('studios.index', compact('studios', 'user'));
    }

    /**
     * 🚀 AGGIORNAMENTO TIER, CREDITI & CICLO 30 GIORNI
     */
    public function updateBilling(Request $request, $id)
    {
        if (!\App\Support\AccessContext::for(Auth::user())->isStaff()) {
            abort(403);
        }

        $request->validate([
            'tier_level' => 'required|string|in:L0,L1,L2,L3,L4',
            'subscription_plan' => 'required|string|in:monthly,annual',
        ]);

        $studio = Studio::findOrFail($id);

        /**
         * 1. Configurazione tier letta dal CSV (fonte unica, stesso TierManager
         *    del webhook BillingController). Niente più mappe hardcoded.
         */
        $conf = TierManager::getTierConfig($request->tier_level);
        if (!$conf) {
            return back()->with('error', "Configurazione tier {$request->tier_level} non trovata.");
        }

        // Storage GB -> bytes (stesso calcolo del webhook BillingController)
        $gb = (float) preg_replace('/[^0-9.]/', '', (string)($conf->storage_gb ?? 0));
        $newQuotaBytes = number_format($gb * 1073741824, 0, '.', '');
        $newCredits = (int) ($conf->gift_credits ?? 0);

        // 2. Scadenza in base al piano: 'annual' -> +12 mesi (come il webhook
        //    BillingController), 'monthly' -> +30 giorni. Il ciclo riparte da oggi.
        $newExpiry = ($request->subscription_plan === 'annual')
            ? Carbon::now()->addMonths(12)
            : Carbon::now()->addDays(30);

        // 3. Aggiornamento Studio Enamel
        $studio->update([
            'tier_level'              => $request->tier_level,
            'subscription_plan'       => $request->subscription_plan,
            'credits_gift'            => $newCredits, 
            'subscription_expires_at' => $newExpiry,
            'grace_period_until'      => null 
        ]);

        // balance coerente col wallet (gift + purchased). 'balance' NON è in $fillable
        // del modello Studio → assegnazione diretta + save (fix 2026-06-25).
        $studio->balance = $newCredits + (int) ($studio->credits_purchased ?? 0);
        $studio->save();

        // 4. 🔄 SINCRONIZZAZIONE REAL-TIME CON NEXTCLOUD
        if ($studio->user_id) {
            DB::table('oc_preferences')
                ->updateOrInsert(
                    [
                        'userid'    => $studio->user_id,
                        'appid'     => 'files',
                        'configkey' => 'quota'
                    ],
                    [
                        'configvalue' => $newQuotaBytes
                    ]
                );
        }

        $readableStorage = ($newQuotaBytes / 1073741824) . " GB";
        $cicloLabel = ($request->subscription_plan === 'annual') ? 'annuale (12 mesi)' : 'mensile (30 giorni)';
        return back()->with('success', "Piano aggiornato. Ciclo {$cicloLabel} avviato (Scadenza: " . $newExpiry->format('d/m/Y') . ").");
    }
}