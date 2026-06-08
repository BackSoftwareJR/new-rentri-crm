<?php

namespace Tests\Feature\Sprint33;

use App\Enums\RegistroMovimentoTipo;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriTransmissione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\RentriEndpoints;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriRegistroTrasmissioneLiveFlowTest extends TestCase
{
    use SeedsRentriCertificate;

    private const TX_ID = 'tx-registro-min-001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.registro_poll_max_attempts', 5);
        Config::set('services.rentri.registro_poll_interval_ms', 1);
    }

    public function test_registro_request_maps_movimenti_to_mase_shape(): void
    {
        $service = app(RentriRegistryServiceInterface::class);
        $cer = CodiceCer::factory()->create(['codice' => '16 01 04']);

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 33.5,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = $service->buildTransmissionPayload(Carbon::now()->startOfMonth(), Carbon::now());
        $request = RentriRegistroTrasmissioneRequest::fromPayload($payload, \App\Models\RentriSetting::instance());
        $body = $request->body();

        $this->assertSame('OP12345678901-PD00001', $body['num_iscr_sito']);
        $this->assertSame('16 01 04', $body['movimenti'][0]['codice_cer']);
        $this->assertSame('SCARICO', $body['movimenti'][0]['tipo_movimento']);
        $this->assertSame(33.5, $body['movimenti'][0]['quantita_kg']);
        $this->assertSame(RentriEndpoints::REGISTRO_TRASMISSIONE, $request->livePath());
    }

    public function test_submit_poll_and_result_with_ministerial_http_shape(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/registro/v1.0/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/registro/v1.0/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'RENTRI-REG-001',
            ], 200, ['X-Correlation-Id' => 'corr-reg-001']),
        ]);

        $client = app(RentriApiClientInterface::class);
        $service = app(RentriRegistryServiceInterface::class);
        $cer = CodiceCer::factory()->create();

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 10,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = $service->buildTransmissionPayload(Carbon::now()->startOfMonth(), Carbon::now());
        $request = RentriRegistroTrasmissioneRequest::fromPayload($payload, \App\Models\RentriSetting::instance());

        $submit = $client->submitRegistroTrasmissione($request);
        $this->assertSame(self::TX_ID, $submit['transazione_id']);

        $result = $client->waitRegistroTrasmissioneResult(self::TX_ID);
        $this->assertSame('accettato', $result['esito']);
        $this->assertSame('RENTRI-REG-001', $result['protocollo']);
        $this->assertSame('corr-reg-001', $result['correlation_id']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://demoapi.rentri.gov.it/registro/v1.0/trasmissione'
                && isset($request->data()['movimenti'][0]['codice_cer']);
        });
    }

    public function test_registry_service_transmit_live_persists_protocollo(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/registro/v1.0/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/registro/v1.0/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'RENTRI-REG-LIVE',
            ], 200),
        ]);

        $service = app(RentriRegistryServiceInterface::class);
        $cer = CodiceCer::factory()->create();
        $movimento = RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 20,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        $payload = $service->buildTransmissionPayload(Carbon::now()->startOfMonth(), Carbon::now());
        $transmissione = $service->transmit($payload);

        $this->assertInstanceOf(RentriTransmissione::class, $transmissione);
        $this->assertSame('accettato', $transmissione->esito);
        $this->assertSame('RENTRI-REG-LIVE', $transmissione->response_json['protocollo']);
        $this->assertSame('live', $transmissione->response_json['api_mode']);
        $this->assertSame(self::TX_ID, $transmissione->response_json['transazione_id']);

        $movimento->refresh();
        $this->assertTrue($movimento->rentri_trasmesso);
    }

    public function test_poll_waits_until_status_completata(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/'.self::TX_ID.'/status' => Http::sequence()
                ->push(['stato' => 'IN_ELABORAZIONE'], 200)
                ->push(['stato' => 'COMPLETATA'], 200),
            'demoapi.rentri.gov.it/registro/v1.0/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'RENTRI-POLL',
            ], 200),
        ]);

        $result = app(RentriApiClientInterface::class)->waitRegistroTrasmissioneResult(self::TX_ID);

        $this->assertSame('RENTRI-POLL', $result['protocollo']);
        Http::assertSentCount(3);
    }
}
