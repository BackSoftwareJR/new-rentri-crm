<?php

namespace App\Policies;

use App\Models\MudDichiarazione;
use App\Models\User;
use App\Enums\MudStato;
use App\Policies\Concerns\EnforcesDemoScope;

class MudDichiarazionePolicy
{
    use EnforcesDemoScope;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return null;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function view(User $user, MudDichiarazione $mudDichiarazione): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $this->demoScopeAllows($mudDichiarazione);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function update(User $user, MudDichiarazione $mudDichiarazione): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $this->demoScopeAllows($mudDichiarazione);
    }

    public function complete(User $user, MudDichiarazione $mudDichiarazione): bool
    {
        return $this->update($user, $mudDichiarazione);
    }

    public function export(User $user, MudDichiarazione $mudDichiarazione): bool
    {
        return $this->view($user, $mudDichiarazione);
    }

    public function invioTelematico(User $user, MudDichiarazione $mudDichiarazione): bool
    {
        return $this->update($user, $mudDichiarazione)
            && $mudDichiarazione->stato === MudStato::Completata;
    }
}
