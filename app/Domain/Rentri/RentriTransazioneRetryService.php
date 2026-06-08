<?php

namespace App\Domain\Rentri;

use App\Jobs\RetryRentriTransazioneJob;
use App\Models\RentriTransazione;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class RentriTransazioneRetryService
{
    /** @var list<string> */
    private const RETRYABLE_TIPI = ['fir', 'xfir', 'registro'];

    public function isEnabled(): bool
    {
        return (bool) config('services.rentri.retry_enabled', true);
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('services.rentri.retry_max_attempts', 5));
    }

    public function backoffSeconds(int $retryCount): int
    {
        $base = max(1, (int) config('services.rentri.retry_base_delay_seconds', 60));
        $max = max($base, (int) config('services.rentri.retry_max_delay_seconds', 3600));

        $delay = $base * (2 ** max(0, $retryCount));

        return (int) min($delay, $max);
    }

    public function shouldScheduleRetry(RentriTransazione $transazione, Throwable $exception): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($transazione->dead_letter_at !== null) {
            return false;
        }

        if (! in_array($transazione->tipo_api, self::RETRYABLE_TIPI, true)) {
            return false;
        }

        if ($transazione->retry_count >= $this->maxAttempts()) {
            return false;
        }

        return $this->isRetryableException($exception);
    }

    public function scheduleRetry(RentriTransazione $transazione): void
    {
        if ($transazione->retry_count >= $this->maxAttempts()) {
            $this->markDeadLetter($transazione);

            return;
        }

        $delay = $this->backoffSeconds($transazione->retry_count);
        $nextAt = now()->addSeconds($delay);

        $transazione->update([
            'next_retry_at' => $nextAt,
        ]);

        RetryRentriTransazioneJob::dispatch($transazione->id)
            ->delay($nextAt);
    }

    public function retryNow(RentriTransazione $transazione): void
    {
        if (! $this->canRetryNow($transazione)) {
            throw new \RuntimeException('Transazione non idonea al retry manuale.');
        }

        $transazione->update([
            'next_retry_at' => null,
        ]);

        RetryRentriTransazioneJob::dispatchSync($transazione->id);
    }

    public function canRetryNow(RentriTransazione $transazione): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($transazione->dead_letter_at !== null) {
            return false;
        }

        if (! in_array($transazione->tipo_api, self::RETRYABLE_TIPI, true)) {
            return false;
        }

        if ($transazione->stato === 'completata') {
            return false;
        }

        return $transazione->retry_count < $this->maxAttempts();
    }

    public function markDeadLetter(RentriTransazione $transazione): void
    {
        $transazione->update([
            'dead_letter_at' => now(),
            'next_retry_at'    => null,
            'stato'            => 'errore',
        ]);
    }

    public function isDeadLetter(RentriTransazione $transazione): bool
    {
        return $transazione->dead_letter_at !== null;
    }

    public function hasPendingRetry(RentriTransazione $transazione): bool
    {
        return $transazione->next_retry_at !== null
            && $transazione->dead_letter_at === null
            && $transazione->stato === 'errore';
    }

    private function isRetryableException(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RentriApiException) {
            return in_array($exception->getCode(), $this->retryableHttpCodes(), true);
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'temporaneamente non disponibile');
    }

    /**
     * @return list<int>
     */
    private function retryableHttpCodes(): array
    {
        return [408, 429, 500, 502, 503, 504];
    }
}
