<?php

namespace App\Policies;

use App\Models\CodiceCer;
use App\Models\User;

class CodiceCerPolicy
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

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CodiceCer $codiceCer): bool
    {
        return false;
    }

    public function delete(User $user, CodiceCer $codiceCer): bool
    {
        return false;
    }

    public function syncRentri(User $user): bool
    {
        return false;
    }
}
