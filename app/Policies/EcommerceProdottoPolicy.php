<?php

namespace App\Policies;

use App\Models\EcommerceProdotto;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class EcommerceProdottoPolicy
{
    use EnforcesDemoScope;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return null;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria', 'operatore']);
    }

    public function view(User $user, EcommerceProdotto $ecommerceProdotto): bool
    {
        if (! $this->demoScopeAllows($ecommerceProdotto)) {
            return false;
        }

        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return true;
        }

        return $user->hasRole('operatore') && $ecommerceProdotto->attivo;
    }

    public function uploadPhotos(User $user): bool
    {
        return $user->hasRole('operatore');
    }

    public function linkPhoto(User $user, EcommerceProdotto $ecommerceProdotto): bool
    {
        if (! $user->hasRole('operatore')) {
            return false;
        }

        if (! $ecommerceProdotto->attivo) {
            return false;
        }

        return $this->demoScopeAllows($ecommerceProdotto);
    }

    public function uploadImage(User $user, EcommerceProdotto $ecommerceProdotto): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $this->demoScopeAllows($ecommerceProdotto);
    }
}
