<?php

namespace App\Policies;

use App\Enums\VfuStato;
use App\Models\SmontaggioRicambio;
use App\Models\SmontaggioSession;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Policies\Concerns\EnforcesDemoScope;

class SmontaggioPolicy
{
    use EnforcesDemoScope;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['operatore', 'admin', 'editor']);
    }

    public function avvia(User $user, VfuRegistration $vfu): bool
    {
        if (! $user->hasAnyRole(['operatore', 'admin', 'editor'])) {
            return false;
        }

        if (! $this->demoScopeAllows($vfu)) {
            return false;
        }

        return in_array($vfu->stato, [VfuStato::Bonificato, VfuStato::InSmontaggio], true);
    }

    public function gestisci(User $user, SmontaggioSession $session): bool
    {
        if (! $user->hasAnyRole(['operatore', 'admin', 'editor'])) {
            return false;
        }

        if (! $this->demoScopeAllows($session)) {
            return false;
        }

        return ! $session->isCompletata();
    }

    public function completa(User $user, SmontaggioSession $session): bool
    {
        return $this->gestisci($user, $session);
    }

    public function viewPhoto(User $user, SmontaggioRicambio $ricambio): bool
    {
        if (! $user->hasAnyRole(['operatore', 'segreteria', 'admin', 'editor'])) {
            return false;
        }

        if (! $this->demoScopeAllows($ricambio)) {
            return false;
        }

        $ricambio->loadMissing('session');

        return $this->demoScopeAllows($ricambio->session);
    }
}
