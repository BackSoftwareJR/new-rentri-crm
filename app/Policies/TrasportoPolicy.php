<?php

namespace App\Policies;

use App\Models\Trasporto;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class TrasportoPolicy
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

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Trasporto $trasporto): bool
    {
        return $this->demoScopeAllows($trasporto);
    }

    public function update(User $user, Trasporto $trasporto): bool
    {
        return $this->demoScopeAllows($trasporto);
    }

    public function complete(User $user, Trasporto $trasporto): bool
    {
        return $this->demoScopeAllows($trasporto);
    }
}
