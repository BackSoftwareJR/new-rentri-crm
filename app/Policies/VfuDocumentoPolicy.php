<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VfuDocumento;
use App\Models\VfuRegistration;
use App\Policies\Concerns\EnforcesDemoScope;

class VfuDocumentoPolicy
{
    use EnforcesDemoScope;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return null;
        }

        return false;
    }

    public function viewAny(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function view(User $user, VfuDocumento $documento): bool
    {
        $documento->loadMissing('vfuRegistration');

        return $this->demoScopeAllows($documento->vfuRegistration);
    }

    public function create(User $user, VfuRegistration $vfuRegistration): bool
    {
        return $this->demoScopeAllows($vfuRegistration);
    }

    public function delete(User $user, VfuDocumento $documento): bool
    {
        $documento->loadMissing('vfuRegistration');

        return $this->demoScopeAllows($documento->vfuRegistration);
    }
}
