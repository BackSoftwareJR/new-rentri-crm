<?php

namespace App\Policies;

use App\Models\RentriSetting;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class RentriSettingPolicy
{
    use EnforcesDemoScope;

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

    public function view(User $user, RentriSetting $rentriSetting): bool
    {
        return $this->demoScopeAllows($rentriSetting);
    }

    public function update(User $user, RentriSetting $rentriSetting): bool
    {
        return $this->demoScopeAllows($rentriSetting);
    }
}
