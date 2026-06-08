<?php

namespace App\Services\Rentri;

use App\Services\Rentri\Exceptions\RentriApiException;

class RentriFirVidimaMessageMapper
{
    public static function fromException(RentriApiException $exception): string
    {
        if ($exception->getCode() === 408) {
            $maxAttempts = max(1, (int) config('services.rentri.fir_poll_max_attempts', 15));
            $intervalMs = max(1, (int) config('services.rentri.fir_poll_interval_ms', 200));
            $seconds = (int) ceil(($maxAttempts * $intervalMs) / 1000);

            return sprintf(
                'Timeout attesa esito vidimazione MASE (%d tentativi, ~%d s). Riprova tra qualche minuto o verifica lo stato su RENTRI.',
                $maxAttempts,
                $seconds,
            );
        }

        $message = $exception->getMessage();

        if (str_contains(strtolower($message), 'rifiutata')) {
            return $message;
        }

        if (in_array($exception->getCode(), [429, 500, 502, 503, 504], true)) {
            return 'MASE temporaneamente non disponibile per la vidimazione FIR. Riprova più tardi.';
        }

        return $message !== '' ? $message : 'Vidimazione FIR non riuscita verso MASE.';
    }
}
