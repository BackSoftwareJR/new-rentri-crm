<?php

namespace App\Services\Rentri;

use App\Services\Rentri\Exceptions\RentriApiException;

class RentriXfirTransmissionMessageMapper
{
    public static function fromException(RentriApiException $exception): string
    {
        if ($exception->getCode() === 408) {
            $maxAttempts = max(1, (int) config('services.rentri.xfir_poll_max_attempts', 20));
            $intervalMs = max(1, (int) config('services.rentri.xfir_poll_interval_ms', 300));
            $seconds = (int) ceil(($maxAttempts * $intervalMs) / 1000);

            return sprintf(
                'Timeout attesa esito invio xFIR firmato MASE (%d tentativi, ~%d s). Riprova tra qualche minuto o verifica lo stato su RENTRI.',
                $maxAttempts,
                $seconds,
            );
        }

        $message = $exception->getMessage();

        if (str_contains(strtolower($message), 'rifiutat')) {
            return $message;
        }

        if (in_array($exception->getCode(), [429, 500, 502, 503, 504], true)) {
            return 'MASE temporaneamente non disponibile per l\'invio xFIR firmato. Riprova più tardi.';
        }

        return $message !== '' ? $message : 'Invio xFIR firmato non riuscito verso MASE.';
    }
}
