<?php

namespace Tests\Feature\Sprint39;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriTransazione;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriXfirTransmissionServiceInterface;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirTransmissionTest extends TestCase
{
    use SeedsRentriCertificate;

    private const TX_ID = 'tx-xfir-ministeriale-001';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.api_stub', true);
        Config::set('services.rentri.firma_stub', true);
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-XFIR', 'onboarding_step_completed' => 3]);
    }

    protected function tearDown(): void
    {
        Config::set('demo.enabled', false);
        Config::set('demo.rentri.offline_no_http', false);

        parent::tearDown();
    }

    public function test_stub_transmit_persists_protocollo_and_transazione(): void
    {
        $fir = $this->seedSignedFir();

        $transmitted = app(RentriXfirTransmissionServiceInterface::class)->transmit($fir);

        $this->assertSame(FirStato::Trasmesso, $transmitted->stato);
        $this->assertNotNull($transmitted->xfir_trasmesso_at);
        $this->assertNotNull($transmitted->xfir_protocollo);
        $this->assertNotNull($transmitted->xfir_transazione_id);
        $this->assertStringStartsWith('XFIR-', $transmitted->xfir_protocollo);

        $this->assertGreaterThanOrEqual(1, RentriTransazione::where('tipo_api', 'xfir')->where('stato', 'completata')->count());
    }

    public function test_transmit_is_idempotent_guard(): void
    {
        $fir = $this->seedSignedFir();
        $service = app(RentriXfirTransmissionServiceInterface::class);
        $service->transmit($fir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('già stato inviato');

        $service->transmit($fir->fresh());
    }

    public function test_live_submit_poll_result_with_ministerial_http_shape(): void
    {
        $fir = $this->seedSignedFir();

        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 5);
        Config::set('services.rentri.fir_poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/trasmissione' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'XFIR-MIN-PROTO-99',
                'numero_fir' => $fir->numero_fir,
            ], 200, ['X-Correlation-Id' => 'corr-xfir-99']),
        ]);

        /** @var array<string, mixed> $signed */
        $signed = json_decode($fir->xfir_signed_payload, true, 512, JSON_THROW_ON_ERROR);

        $client = app(RentriApiClientInterface::class);
        $submit = $client->submitXfirFirmato(new RentriXfirTrasmissioneRequest(
            $fir,
            $signed,
            \App\Models\RentriSetting::instance(),
        ));

        $this->assertSame(self::TX_ID, $submit['transazione_id']);

        $result = $client->waitXfirTrasmissioneResult(self::TX_ID);

        $this->assertSame('XFIR-MIN-PROTO-99', $result['protocollo']);
        $this->assertSame('corr-xfir-99', $result['correlation_id']);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/trasmissione');
    }

    public function test_demo_offline_mode_uses_stub_without_http(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('services.rentri.api_stub', false);
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-XFIR-DEMO', 'onboarding_step_completed' => 3]);

        Http::fake();

        $fir = $this->seedSignedFir('SITE-XFIR-DEMO', 'BLK-X39-D');
        app(RentriXfirTransmissionServiceInterface::class)->transmit($fir);

        Http::assertNothingSent();
        $this->assertSame(FirStato::Trasmesso, $fir->fresh()->stato);
        $this->assertTrue($fir->fresh()->is_demo);
    }

    private function seedSignedFir(string $numIscrSito = 'SITE-XFIR', string $codiceBlocco = 'BLK-X39'): \App\Models\Fir
    {
        FirBlocco::create([
            'codice_blocco'      => $codiceBlocco,
            'num_iscr_sito'      => $numIscrSito,
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();
        app(RentriFirServiceInterface::class)->vidima($trasporto);

        return app(RentriFirSigningServiceInterface::class)->sign($trasporto->fresh()->firCollegato);
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@s39.test']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
