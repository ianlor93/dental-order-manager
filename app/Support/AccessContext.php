<?php

namespace App\Support;

use App\Models\User;

/**
 * AccessContext — portiere di dominio per le decisioni di autorizzazione.
 *
 * FASE 0 (2026-08-05): isStaff() — delega PURA a User::isStaff().
 * FASE 1 (2026-08-05): isBackOffice() — tier stretto (admin ∪ Amministrazione, NO Operativi). NON è isStaff().
 * FASE studio (2026-08-05): isStudioOwner()/isStudioManager() — ruoli nello studio, ognuno con la SUA forma.
 * FASE ordine (2026-08-05): activeStudio() [involucro di User::activeStudio(), NON fonde le altre risoluzioni]
 *   + ownsOrder() [ricalco esatto di OrderPolicy::ownsOrder, studio-level, fail-closed].
 * Le altre risoluzioni di studio (StudioCompliance::resolveStudio, inline in EnsureLegalCompliance)
 * restano DISTINTE: la loro unificazione è un passo con decisione, non qui.
 */
class AccessContext
{
    private function __construct(private readonly User $user)
    {
    }

    public static function for(User $u): self
    {
        return new self($u);
    }

    public function isStaff(): bool
    {
        return $this->user->isStaff();
    }

    public function isBackOffice(): bool
    {
        return $this->user->uid === 'admin' || $this->user->isInGroups('Amministrazione');
    }

    public function isStudioOwner(\App\Models\Studio $s): bool
    {
        return $s->user_id === $this->user->uid;
    }

    public function isStudioManager(\App\Models\Studio $s): bool
    {
        return in_array($this->user->roleInStudio($s), ['owner', 'admin'], true);
    }

    public function activeStudio(): ?\App\Models\Studio
    {
        return $this->user->activeStudio();
    }

    public function ownsOrder(\App\Models\Order $order): bool
    {
        $studio = $this->activeStudio();
        return $studio !== null && (int) $order->studio_id === (int) $studio->id;
    }

    public function isAgente(): bool
    {
        return $this->user->isAgente();
    }
}
