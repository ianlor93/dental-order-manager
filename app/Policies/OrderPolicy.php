<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Autorizzazione sugli ordini — fix IDOR (C2-C4).
 *
 * Proprietà a livello STUDIO (modello multi-prescrittore): lo studio attivo
 * dell'utente deve coincidere con lo studio dell'ordine. Fail-closed.
 */
class OrderPolicy
{
    /**
     * Bypass per lo Staff/Laboratorio: accede a TUTTI gli ordini.
     * isStaff() = uid 'admin' OPPURE gruppi Nextcloud admin / Amministrazione / Operativi.
     * Ritorna null per i non-staff → si valuta l'abilità specifica.
     */
    public function before(User $user, string $ability): ?bool
    {
        return \App\Support\AccessContext::for($user)->isStaff() ? true : null;
    }

    /**
     * Verifica di proprietà a livello studio.
     * Fail-closed: se l'utente non ha uno studio attivo → nega.
     */
    private function ownsOrder(User $user, Order $order): bool
    {
        return \App\Support\AccessContext::for($user)->ownsOrder($order);
    }

    /** Lettura / anteprima / download dei documenti dell'ordine (C2). */
    public function view(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    /** Validazione / annullamento prescrizione — mutazione di stato + lock MDR (C3). */
    public function validate(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }

    /** Upload di file nella cartella dell'ordine (C4). */
    public function upload(User $user, Order $order): bool
    {
        return $this->ownsOrder($user, $order);
    }
}
