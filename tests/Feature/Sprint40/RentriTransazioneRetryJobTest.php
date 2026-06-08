<?php

namespace Tests\Feature\Sprint40;

use App\Domain\Rentri\RentriTransazioneRetryExecutor;
use App\Jobs\RetryRentriTransazioneJob;
use App\Models\RentriTransazione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriTransazioneRetryJobTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
        Config::set('services.rentri.retry_enabled', true);
        Config::set('services.rentri.retry_max_attempts', 5);
        Config::set('services.rentri.retry_base_delay_seconds', 60);
    }

    public function test_api_failure_auto_dispatches_retry_job_for_registro(): void
    {
        Queue::fake();
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response(['error' => 'down'], 503),
        ]);

        try {
            app(RentriApiClientInterface::class)->request('POST', '/registro/trasmetti', ['movimenti' => []]);
        } catch (RentriApiException) {
            // expected
        }

        $tx = RentriTransazione::where('tipo_api', 'registro')->firstOrFail();
        $this->assertSame('errore', $tx->stato);
        $this->assertNotNull($tx->next_retry_at);
        $this->assertSame(0, $tx->retry_count);

        Queue::assertPushed(RetryRentriTransazioneJob::class, fn (RetryRentriTransazioneJob $job) => $job->transazioneId === $tx->id);
    }

    public function test_retry_executor_replays_successfully(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'RETRY-OK-001',
            ], 200),
        ]);

        $tx = $this->seedFailedRegistro();

        app(RentriTransazioneRetryExecutor::class)->run($tx->id);

        $tx->refresh();
        $this->assertSame('completata', $tx->stato);
        $this->assertSame(1, $tx->retry_count);
        $this->assertNull($tx->next_retry_at);
        $this->assertNull($tx->dead_letter_at);
        $this->assertSame('accettato', $tx->response_json['esito'] ?? null);
    }

    public function test_retry_executor_marks_dead_letter_after_max_attempts(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.retry_max_attempts', 2);

        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response(['error' => 'down'], 503),
        ]);

        $tx = $this->seedFailedRegistro(['retry_count' => 1]);

        app(RentriTransazioneRetryExecutor::class)->run($tx->id);

        $tx->refresh();
        $this->assertSame(2, $tx->retry_count);
        $this->assertNotNull($tx->dead_letter_at);
        $this->assertNull($tx->next_retry_at);
        $this->assertSame('errore', $tx->stato);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedFailedRegistro(array $overrides = []): RentriTransazione
    {
        return RentriTransazione::create(array_merge([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'registro',
            'stato'          => 'errore',
            'request_json'   => [
                'method'           => 'POST',
                'endpoint'         => '/registro/v1.0/trasmissione',
                'logical_endpoint' => '/registro/trasmetti',
                'payload'          => ['periodo_da' => '2026-06-01', 'movimenti' => []],
            ],
            'response_json'  => ['error' => '503'],
            'completed_at'   => now(),
            'retry_count'    => 0,
        ], $overrides));
    }
}
