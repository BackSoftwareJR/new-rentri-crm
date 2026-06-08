<?php

namespace App\Domain\Rentri;

use App\Models\RentriTransazione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;

class RentriTransazioneRetryExecutor
{
    public function __construct(
        private RentriApiClientInterface $apiClient,
        private RentriTransazioneRetryService $retryService,
    ) {}

    public function run(int $transazioneId): void
    {
        $transazione = RentriTransazione::query()->findOrFail($transazioneId);

        if ($transazione->dead_letter_at !== null) {
            return;
        }

        if ($transazione->retry_count >= $this->retryService->maxAttempts()) {
            $this->retryService->markDeadLetter($transazione);

            return;
        }

        $transazione->update([
            'stato'         => 'in_corso',
            'next_retry_at' => null,
            'completed_at'  => null,
        ]);

        $transazione->increment('retry_count');

        try {
            $this->apiClient->replayTransazione($transazione->fresh());
        } catch (\Throwable $e) {
            $transazione->refresh();

            if ($this->retryService->shouldScheduleRetry($transazione, $e)) {
                $this->retryService->scheduleRetry($transazione);
            } elseif ($transazione->retry_count >= $this->retryService->maxAttempts()) {
                $this->retryService->markDeadLetter($transazione);
            }
        }
    }
}
