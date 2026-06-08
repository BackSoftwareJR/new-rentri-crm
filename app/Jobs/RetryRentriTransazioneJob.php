<?php

namespace App\Jobs;

use App\Domain\Rentri\RentriTransazioneRetryExecutor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryRentriTransazioneJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $transazioneId,
    ) {
        $this->onQueue('rentri');
    }

    public function handle(RentriTransazioneRetryExecutor $executor): void
    {
        $executor->run($this->transazioneId);
    }
}
