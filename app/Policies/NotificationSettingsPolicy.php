<?php

namespace App\Policies;

use App\Models\User;
use App\Support\NotificationSettings;

class NotificationSettingsPolicy
{
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

    public function view(User $user, NotificationSettings $settings): bool
    {
        return true;
    }

    public function update(User $user, NotificationSettings $settings): bool
    {
        return true;
    }
}
