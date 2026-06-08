<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
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

    public function view(User $user, Activity $activity): bool
    {
        return false;
    }

    public function viewExports(User $user): bool
    {
        return true;
    }

    public function downloadExport(User $user): bool
    {
        return true;
    }
}
