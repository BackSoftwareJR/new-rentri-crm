<?php

namespace Tests\Feature\Sprint40;

use App\Http\Livewire\Segreteria\Rentri\RentriTransazioneShow;
use App\Http\Livewire\Segreteria\Rentri\RentriTransazioniIndex;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriTransazioniRetryHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
        Config::set('services.rentri.retry_enabled', true);
    }

    public function test_storico_api_shows_retry_badge_and_riprova_ora(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $tx = $this->seedRetryableTransazione();

        Livewire::actingAs($user)
            ->test(RentriTransazioniIndex::class)
            ->assertSee('Retry pianificati')
            ->assertSee('Dead-letter')
            ->assertSee('Retry');

        Livewire::actingAs($user)
            ->test(RentriTransazioneShow::class, ['transazione' => $tx])
            ->assertSee('Retry MASE')
            ->assertSee('Riprova ora');
    }

    public function test_riprova_ora_replays_transaction(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-RETRY' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'MANUAL-RETRY-001',
            ], 200),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $tx = $this->seedRetryableTransazione('fir', [
            'request_json' => [
                'method'           => 'POST',
                'endpoint'         => '/vidimazione-formulari/v1.0/BLK-RETRY',
                'logical_endpoint' => '/fir/vidima',
                'payload'          => [
                    'codice_blocco' => 'BLK-RETRY',
                    'progressivo'   => 1,
                    'num_iscr_sito' => 'SITE-TEST',
                ],
            ],
        ]);

        Livewire::actingAs($user)
            ->test(RentriTransazioneShow::class, ['transazione' => $tx])
            ->call('retryNow')
            ->assertHasNoErrors();

        $tx->refresh();
        $this->assertSame('completata', $tx->stato);
        $this->assertGreaterThanOrEqual(1, $tx->retry_count);
    }

    public function test_demo_mode_retry_stays_on_demo_scoped_transactions(): void
    {
        Config::set('demo.enabled', true);
        Config::set('services.rentri.api_stub', true);

        $tx = $this->seedRetryableTransazione('registro', ['is_demo' => true]);

        $this->assertTrue(RentriTransazione::query()->whereKey($tx->id)->exists());
        $this->assertNull(
            RentriTransazione::includingAllDemoModes()->where('is_demo', false)->whereKey($tx->id)->first()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedRetryableTransazione(string $tipoApi = 'registro', array $overrides = []): RentriTransazione
    {
        return RentriTransazione::create(array_merge([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => $tipoApi,
            'stato'          => 'errore',
            'request_json'   => [
                'method'           => 'POST',
                'endpoint'         => '/registro/v1.0/trasmissione',
                'logical_endpoint' => '/registro/trasmetti',
                'payload'          => ['movimenti' => []],
            ],
            'response_json'  => ['error' => '503'],
            'completed_at'   => now(),
            'retry_count'    => 1,
            'next_retry_at'  => now()->addHour(),
        ], $overrides));
    }
}
