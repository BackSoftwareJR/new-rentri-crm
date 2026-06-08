<?php

namespace App\Policies;

use App\Enums\VfuStato;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Policies\Concerns\EnforcesDemoScope;

class BonificaPolicy
{
    use EnforcesDemoScope;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['operatore', 'admin', 'editor']);
    }

    public function perform(User $user, VfuRegistration $vfu): bool
    {
        if (! $user->hasAnyRole(['operatore', 'admin', 'editor'])) {
            return false;
        }

        if (! $this->demoScopeAllows($vfu)) {
            return false;
        }

        return in_array($vfu->stato, [
            VfuStato::Accettato,
            VfuStato::AttesaBonifica,
            VfuStato::InBonifica,
        ], true);
    }

    public function saveChecklist(User $user, VfuRegistration $vfu): bool
    {
        return $this->perform($user, $vfu);
    }

    public function advancePericolosi(User $user, VfuRegistration $vfu): bool
    {
        return $this->perform($user, $vfu);
    }
}
