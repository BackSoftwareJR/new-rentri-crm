<?php

namespace App\Policies;

use App\Models\CodiceCer;
use App\Models\User;

class MagazzinoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, CodiceCer $codiceCer): bool
    {
        return false;
    }

    public function caricoManuale(User $user, CodiceCer $codiceCer): bool
    {
        return false;
    }

    public function richiediSvuotamento(User $user, CodiceCer $codiceCer): bool
    {
        return false;
    }
}
