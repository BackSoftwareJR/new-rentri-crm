<?php

namespace App\Domain\Operatore;

use App\Models\User;

class OperatoreProfiloService
{
    public function updateProfilo(User $user, string $name): User
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Il nome è obbligatorio.');
        }

        if (mb_strlen($name) > 255) {
            throw new \InvalidArgumentException('Il nome è troppo lungo.');
        }

        $user->update(['name' => $name]);

        return $user->fresh();
    }
}
