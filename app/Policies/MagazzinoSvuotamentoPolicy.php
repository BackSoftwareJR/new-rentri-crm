<?php

namespace App\Policies;

use App\Models\MagazzinoSvuotamento;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class MagazzinoSvuotamentoPolicy
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

    public function view(User $user, MagazzinoSvuotamento $magazzinoSvuotamento): bool
    {
        return $this->demoScopeAllows($magazzinoSvuotamento);
    }

    public function create(User $user): bool
    {
        return true;
    }
}
