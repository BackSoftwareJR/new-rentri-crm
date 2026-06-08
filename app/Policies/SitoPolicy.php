<?php

namespace App\Policies;

use App\Models\Sito;
use App\Models\User;

class SitoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Sito $sito): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Sito $sito): bool
    {
        return $user->hasRole('admin') && ! $sito->is_default;
    }
}
