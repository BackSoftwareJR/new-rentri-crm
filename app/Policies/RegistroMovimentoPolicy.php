<?php

namespace App\Policies;

use App\Models\RegistroMovimento;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class RegistroMovimentoPolicy
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

    public function view(User $user, RegistroMovimento $registroMovimento): bool
    {
        return $this->demoScopeAllows($registroMovimento);
    }

    public function update(User $user, RegistroMovimento $registroMovimento): bool
    {
        return $this->demoScopeAllows($registroMovimento) && ! $registroMovimento->isLocked();
    }

    public function delete(User $user, RegistroMovimento $registroMovimento): bool
    {
        return $this->demoScopeAllows($registroMovimento) && ! $registroMovimento->isLocked();
    }
}
