<?php

namespace App\Policies;

use App\Models\RentriTransmissione;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class RentriTransmissionePolicy
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

    public function view(User $user, RentriTransmissione $rentriTransmissione): bool
    {
        return $this->demoScopeAllows($rentriTransmissione);
    }

    public function create(User $user): bool
    {
        return true;
    }
}
