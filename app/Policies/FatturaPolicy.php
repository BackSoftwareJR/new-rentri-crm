<?php

namespace App\Policies;

use App\Models\Fattura;
use App\Models\User;

class FatturaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function view(User $user, Fattura $fattura): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function update(User $user, Fattura $fattura): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && $fattura->stato === 'bozza';
    }

    /** Post-emission actions: XML, email, SDI, pagamento. */
    public function manage(User $user, Fattura $fattura): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && in_array($fattura->stato, ['emessa', 'pagata', 'scaduta'], true);
    }

    public function delete(User $user, Fattura $fattura): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria'])
            && ! in_array($fattura->stato, ['pagata', 'annullata'], true);
    }

    public function restore(User $user, Fattura $fattura): bool
    {
        return $user->hasRole('admin');
    }
}
