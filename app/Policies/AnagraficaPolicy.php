<?php

namespace App\Policies;

use App\Models\Anagrafica;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class AnagraficaPolicy
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

    public function view(User $user, Anagrafica $anagrafica): bool
    {
        return $this->demoScopeAllows($anagrafica);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Anagrafica $anagrafica): bool
    {
        return $this->demoScopeAllows($anagrafica);
    }

    public function delete(User $user, Anagrafica $anagrafica): bool
    {
        return $this->demoScopeAllows($anagrafica);
    }
}
