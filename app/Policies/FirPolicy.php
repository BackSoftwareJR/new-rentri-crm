<?php

namespace App\Policies;

use App\Models\Fir;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class FirPolicy
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

    public function exportAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Fir $fir): bool
    {
        return $this->demoScopeAllows($fir);
    }

    public function vidima(User $user): bool
    {
        return true;
    }

    public function firma(User $user): bool
    {
        return true;
    }
}
