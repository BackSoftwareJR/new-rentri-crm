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

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('admin');
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('admin') && $authUser->id !== $targetUser->id;
    }

    public function toggleActive(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('admin') && $authUser->id !== $targetUser->id;
    }

    public function resetPassword(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('admin');
    }

    public function forceDisable2fa(User $authUser, User $targetUser): bool
    {
        return $authUser->hasRole('admin');
    }
}
