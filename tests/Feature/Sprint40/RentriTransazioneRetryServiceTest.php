<?php

namespace Tests\Feature\Sprint40;

use App\Domain\Rentri\RentriTransazioneRetryService;
use App\Models\RentriTransazione;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentriTransazioneRetryServiceTest extends TestCase
{
    public function test_exponential_backoff_respects_base_and_max_delay(): void
    {
        Config::set('services.rentri.retry_base_delay_seconds', 60);
        Config::set('services.rentri.retry_max_delay_seconds', 3600);

        $service = app(RentriTransazioneRetryService::class);

        $this->assertSame(60, $service->backoffSeconds(0));
        $this->assertSame(120, $service->backoffSeconds(1));
        $this->assertSame(240, $service->backoffSeconds(2));
        $this->assertSame(3600, $service->backoffSeconds(10));
    }

    public function test_should_schedule_retry_only_for_retryable_tipi_and_http_codes(): void
    {
        $service = app(RentriTransazioneRetryService::class);

        $fir = $this->seedErrore('fir');
        $health = $this->seedErrore('health');

        $this->assertTrue($service->shouldScheduleRetry($fir, new RentriApiException('503', 503)));
        $this->assertFalse($service->shouldScheduleRetry($health, new RentriApiException('503', 503)));
        $this->assertFalse($service->shouldScheduleRetry($fir, new RentriApiException('422', 422)));
    }

    public function test_dead_letter_when_max_attempts_reached_on_schedule(): void
    {
        Config::set('services.rentri.retry_max_attempts', 2);

        $service = app(RentriTransazioneRetryService::class);
        $tx = $this->seedErrore('registro', ['retry_count' => 2]);

        $service->scheduleRetry($tx);

        $tx->refresh();
        $this->assertNotNull($tx->dead_letter_at);
        $this->assertNull($tx->next_retry_at);
        $this->assertSame('errore', $tx->stato);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedErrore(string $tipoApi, array $overrides = []): RentriTransazione
    {
        return RentriTransazione::create(array_merge([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => $tipoApi,
            'stato'          => 'errore',
            'request_json'   => [
                'method'   => 'POST',
                'endpoint' => '/registro/trasmetti',
                'payload'  => ['movimenti' => []],
            ],
            'response_json'  => ['error' => 'fail'],
            'completed_at'   => now(),
            'retry_count'    => 0,
        ], $overrides));
    }
}
