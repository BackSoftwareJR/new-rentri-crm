<?php

namespace App\Support\Rentri;

use Illuminate\Support\Facades\RateLimiter;

final class FirActionRateLimiter
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function tooMany(string $action, int $userId): bool
    {
        return RateLimiter::tooManyAttempts($this->key($action, $userId), self::MAX_ATTEMPTS);
    }

    public function record(string $action, int $userId): void
    {
        RateLimiter::hit($this->key($action, $userId), self::DECAY_SECONDS);
    }

    public function message(string $action): string
    {
        return match ($action) {
            'vidima' => 'Troppe vidimazioni FIR in poco tempo. Attendi un minuto e riprova.',
            'firma'  => 'Troppe firme xFIR in poco tempo. Attendi un minuto e riprova.',
            default  => 'Limite azioni raggiunto. Attendi un minuto e riprova.',
        };
    }

    private function key(string $action, int $userId): string
    {
        return 'fir-'.$action.':'.$userId;
    }
}
