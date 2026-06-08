<?php

namespace App\Policies;

use App\Models\RentriTransazione;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class RentriTransazionePolicy
{
    use EnforcesDemoScope;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return null;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RentriTransazione $rentriTransazione): bool
    {
        return $this->demoScopeAllows($rentriTransazione);
    }
}
