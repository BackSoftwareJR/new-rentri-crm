<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor'])) {
            return true;
        }

        return null;
    }

    public function updateProfile(User $user, User $model): bool
    {
        return $user->hasRole('operatore') && $user->id === $model->id;
    }
}
