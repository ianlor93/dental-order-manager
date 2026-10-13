<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    /**
     * Registra una nuova sede operativa con il relativo referente clinico.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Protezione Staff: Lo staff non crea sedi, le usa solo per gli ordini
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            return back()->with('error', 'Gli account Staff non possono aggiungere sedi cliniche direttamente.');
        }

        // 2. Verifica esistenza Studio (titolare o membro attivo via pivot)
        $studio = \App\Support\AccessContext::for($user)->activeStudio();
        if (!$studio || !$studio->id) {
            Log::error("MDR-ADDRESS-ERROR: Utente {$user->uid} senza studio ha tentato di aggiungere una sede.");
            return back()->with('error', 'Configurazione studio non trovata. Completa l\'onboarding.');
        }

        // Solo owner e admin possono gestire le sedi dello studio
        $role = $user->roleInStudio($studio);
        if (!\App\Support\AccessContext::for($user)->isStudioManager($studio)) {
            return back()->with('error', 'Solo il titolare o un amministratore dello studio può aggiungere sedi.');
        }

        // 3. Validazione
        $validated = $request->validate([
            'label'           => 'required|string|max:255',
            'dentist_name'    => 'required|string|max:255',
            'dentist_license' => 'required|string|max:255',
            'albo_provincia'  => 'required|string|size:2',
            'phone'           => 'required|string|max:255',
            'address'         => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'zip_code'        => 'required|string|max:10',
        ]);

        // 4. Standardizzazione
        $validated['albo_provincia'] = strtoupper($validated['albo_provincia']);
        $validated['is_main'] = false;

        // 5. Creazione tramite relazione (Laravel userà automaticamente 'oc_enamel_addresses')
        $studio->addresses()->create($validated);

        return back()->with('success', 'Nuova sede operativa e referente clinico registrati con successo.');
    }

    /**
     * Rimuove una sede operativa (Soft Delete per rintracciabilità MDR).
     */
    public function destroy(Address $address)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Risoluzione studio attivo (titolare o membro via pivot)
        $studio = \App\Support\AccessContext::for($user)->activeStudio();

        if (!$studio || $address->studio_id !== $studio->id) {
            Log::warning("SECURITY ALERT: Tentativo di eliminazione sede non autorizzato da parte di {$user->uid}");
            abort(403, 'Azione non autorizzata su questa risorsa.');
        }

        // Solo owner e admin possono eliminare sedi
        $role = $user->roleInStudio($studio);
        if (!\App\Support\AccessContext::for($user)->isStudioManager($studio)) {
            abort(403, 'Solo il titolare o un amministratore dello studio può rimuovere sedi.');
        }

        // 🛑 Business Logic: Protezione Sede Legale
        if ($address->is_main) {
            return back()->with('error', 'La sede principale (fiscale) non può essere rimossa.');
        }

        // 🗑️ Soft Delete (MDR Compliance)
        $address->delete();

        return back()->with('success', 'Sede operativa rimossa correttamente.');
    }
}
