<?php

namespace App\Jobs;

use App\Domain\Fatturazione\SdiTransmissionException;
use App\Domain\Fatturazione\SdiTransmissionService;
use App\Models\Fattura;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransmitFatturaSdiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $fatturaId,
    ) {}

    public function handle(SdiTransmissionService $transmission): void
    {
        $fattura = Fattura::query()->find($this->fatturaId);

        if (! $fattura) {
            Log::warning('TransmitFatturaSdiJob: fattura non trovata', ['id' => $this->fatturaId]);

            return;
        }

        try {
            $transmission->transmit($fattura);
        } catch (SdiTransmissionException $e) {
            Log::error('TransmitFatturaSdiJob: trasmissione fallita', [
                'fattura_id' => $this->fatturaId,
                'message'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
