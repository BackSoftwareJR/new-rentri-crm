<?php

namespace App\Policies;

use App\Models\User;
use App\Support\TwoFactorSettings;

class TwoFactorSettingsPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'segreteria'])) {
            return null;
        }

        return false;
    }

    public function view(User $user, TwoFactorSettings $settings): bool
    {
        return (bool) config('two-factor.optional', true);
    }

    public function manage(User $user, TwoFactorSettings $settings): bool
    {
        return (bool) config('two-factor.optional', true);
    }
}
