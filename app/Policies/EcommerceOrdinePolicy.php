<?php

namespace App\Policies;

use App\Models\EcommerceOrdine;
use App\Models\User;
use App\Policies\Concerns\EnforcesDemoScope;

class EcommerceOrdinePolicy
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
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function view(User $user, EcommerceOrdine $ecommerceOrdine): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $this->demoScopeAllows($ecommerceOrdine);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function update(User $user, EcommerceOrdine $ecommerceOrdine): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $this->demoScopeAllows($ecommerceOrdine);
    }

    public function checkout(User $user, EcommerceOrdine $ecommerceOrdine): bool
    {
        return $this->update($user, $ecommerceOrdine)
            && in_array($ecommerceOrdine->stato, [
                \App\Enums\OrdineEcommerceStato::Bozza,
                \App\Enums\OrdineEcommerceStato::PagamentoInAttesa,
            ], true);
    }

    public function annulla(User $user, EcommerceOrdine $ecommerceOrdine): bool
    {
        return $this->update($user, $ecommerceOrdine)
            && $ecommerceOrdine->stato !== \App\Enums\OrdineEcommerceStato::Confermato
            && $ecommerceOrdine->stato !== \App\Enums\OrdineEcommerceStato::Annullato;
    }
}
