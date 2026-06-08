<?php

namespace Tests\Feature\Sprint82;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriXfirTransmissionServiceInterface;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use App\Services\Rentri\Exceptions\RentriApiException;
use App\Services\Rentri\RentriXfirTransmissionMessageMapper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirPollConfigTest extends TestCase
{
    use SeedsRentriCertificate;

    private const TX_ID = 'tx-xfir-poll-s82';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-S82', 'onboarding_step_completed' => 3]);
    }

    public function test_xfir_poll_config_keys_exist_with_defaults(): void
    {
        $this->assertSame(20, config('services.rentri.xfir_poll_max_attempts'));
        $this->assertSame(300, config('services.rentri.xfir_poll_interval_ms'));
        $this->assertNotSame(
            config('services.rentri.fir_poll_max_attempts'),
            config('services.rentri.xfir_poll_max_attempts'),
        );
    }

    public function test_wait_xfir_poll_uses_xfir_config_not_fir_poll(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 50);
        Config::set('services.rentri.fir_poll_interval_ms', 1);
        Config::set('services.rentri.xfir_poll_max_attempts', 3);
        Config::set('services.rentri.xfir_poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'IN_ELABORAZIONE',
            ], 200),
        ]);

        try {
            app(RentriApiClientInterface::class)->waitXfirTrasmissioneResult(self::TX_ID);
            $this->fail('Expected RentriApiException');
        } catch (RentriApiException $e) {
            $this->assertSame(408, $e->getCode());
        }

        Http::assertSentCount(3);
    }

    public function test_xfir_message_mapper_timeout_uses_dedicated_config(): void
    {
        Config::set('services.rentri.xfir_poll_max_attempts', 4);
        Config::set('services.rentri.xfir_poll_interval_ms', 500);

        $message = RentriXfirTransmissionMessageMapper::fromException(
            new RentriApiException('Timeout attesa esito invio xFIR firmato RENTRI.', 408),
        );

        $this->assertStringContainsString('Timeout attesa esito invio xFIR firmato MASE', $message);
        $this->assertStringContainsString('4 tentativi', $message);
        $this->assertStringContainsString('~2 s', $message);
    }

    public function test_xfir_transmission_timeout_message_uses_dedicated_config(): void
    {
        $fir = $this->seedSignedFir();

        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 50);
        Config::set('services.rentri.xfir_poll_max_attempts', 2);
        Config::set('services.rentri.xfir_poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/trasmissione' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'IN_ELABORAZIONE',
            ], 200),
        ]);

        try {
            app(RentriXfirTransmissionServiceInterface::class)->transmit($fir);
            $this->fail('Expected timeout exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Timeout attesa esito invio xFIR firmato MASE', $e->getMessage());
            $this->assertStringContainsString('2 tentativi', $e->getMessage());
        }
    }

    public function test_xfir_transmission_live_poll_succeeds_with_xfir_config(): void
    {
        $fir = $this->seedSignedFir();

        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.xfir_poll_max_attempts', 5);
        Config::set('services.rentri.xfir_poll_interval_ms', 1);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/trasmissione' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/xfir/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'XFIR-S82-PROTO',
                'numero_fir' => $fir->numero_fir,
            ], 200),
        ]);

        $transmitted = app(RentriXfirTransmissionServiceInterface::class)->transmit($fir);

        $this->assertSame('XFIR-S82-PROTO', $transmitted->xfir_protocollo);
    }

    public function test_vidima_poll_unchanged_still_uses_fir_config(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 2);
        Config::set('services.rentri.fir_poll_interval_ms', 1);
        Config::set('services.rentri.xfir_poll_max_attempts', 50);
        Config::set('services.rentri.xfir_poll_interval_ms', 1);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-S82-V',
            'num_iscr_sito'      => 'SITE-S82',
            'progressivo_ultimo' => 0,
        ]);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-S82-V' => Http::response([
                'transazione_id' => 'tx-vidima-s82',
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/tx-vidima-s82/status' => Http::response([
                'stato' => 'IN_ELABORAZIONE',
            ], 200),
        ]);

        try {
            app(RentriFirServiceInterface::class)->vidima($this->seedTrasporto());
            $this->fail('Expected timeout exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Timeout attesa esito vidimazione MASE', $e->getMessage());
            $this->assertStringContainsString('2 tentativi', $e->getMessage());
        }
    }

    private function seedSignedFir(): \App\Models\Fir
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-S82',
            'num_iscr_sito'      => 'SITE-S82',
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
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
