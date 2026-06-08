<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VfuRegistration;
use App\Policies\Concerns\EnforcesDemoScope;

class VfuRegistrationPolicy
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

    public function view(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function delete(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function downloadCertificato(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function exportStorico(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }
}
