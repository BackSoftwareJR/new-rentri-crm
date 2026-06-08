<?php

namespace App\Support\Horizon;

use Illuminate\Support\Facades\Gate;

final class HorizonMonitorService
{
    public function isInstalled(): bool
    {
        return class_exists(\Laravel\Horizon\Horizon::class);
    }

    public function canAccess(?\App\Models\User $user = null): bool
    {
        if (! $this->isInstalled()) {
            return false;
        }

        $user ??= auth()->user();

        return $user !== null && Gate::forUser($user)->allows('viewHorizon');
    }

    public function dashboardUrl(): string
    {
        return url('/'.ltrim((string) config('horizon.path', 'horizon'), '/'));
    }
}
