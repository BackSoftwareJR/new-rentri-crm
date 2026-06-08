<?php

namespace App\Policies;

use App\Models\ApplicationLog;
use App\Models\User;

class ApplicationLogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, ApplicationLog $log): bool
    {
        return false;
    }

    public function export(User $user): bool
    {
        return true;
    }
}
