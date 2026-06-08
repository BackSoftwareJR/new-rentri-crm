<?php

namespace Tests\Feature\Sprint32;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriFirVidimaLiveFlowTest extends TestCase
{
    use SeedsRentriCertificate;

    private const TX_ID = 'tx-ministeriale-001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 5);
        Config::set('services.rentri.fir_poll_interval_ms', 1);
    }

    public function test_submit_poll_and_result_with_ministerial_http_shape(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-LIVE-01' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/verifica/result*' => Http::response([
                'numero_fir'  => 'OP12345678901-PD00001-BLK-LIVE-01-0042',
                'progressivo' => 42,
                'protocollo'  => 'RENTRI-PROTO-42',
                'qr_code'     => 'QR-BASE64-MINISTERIALE',
            ], 200, ['X-Correlation-Id' => 'corr-vidima-42']),
        ]);

        $client = app(RentriApiClientInterface::class);
        $submit = $client->submitFirVidima(new RentriFirVidimaRequest(
            codiceBlocco: 'BLK-LIVE-01',
            numIscrSito: 'OP12345678901-PD00001',
            payload: ['progressivo' => 42],
        ));

        $this->assertSame(self::TX_ID, $submit['transazione_id']);

        $result = $client->waitFirVidimaResult(self::TX_ID);

        $this->assertSame('RENTRI-PROTO-42', $result['protocollo']);
        $this->assertSame('corr-vidima-42', $result['correlation_id']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-LIVE-01';
        });
    }

    public function test_rentri_fir_service_vidima_live_persists_qr_payload(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-LIVE-01' => Http::response([
                'transazione_id' => self::TX_ID,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/'.self::TX_ID.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/verifica/result*' => Http::response([
                'numero_fir'  => 'OP12345678901-PD00001-BLK-LIVE-01-0001',
                'progressivo' => 1,
                'protocollo'  => 'RENTRI-PROTO-001',
                'qr_code'     => 'QR-LIVE-001',
            ], 200),
        ]);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-LIVE-01',
            'num_iscr_sito'      => 'OP12345678901-PD00001',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();
        $fir = app(RentriFirServiceInterface::class)->vidima($trasporto);

        $this->assertSame(FirStato::Vidimato, $fir->stato);
        $this->assertSame('OP12345678901-PD00001-BLK-LIVE-01-0001', $fir->numero_fir);

        /** @var array<string, mixed> $qr */
        $qr = json_decode($fir->qr_payload ?? '{}', true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('live', $qr['api_mode']);
        $this->assertSame('RENTRI-PROTO-001', $qr['protocollo']);
        $this->assertSame(self::TX_ID, $qr['transazione_id']);
        $this->assertSame('QR-LIVE-001', $qr['qr_code']);
    }

    public function test_poll_waits_until_status_completata(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/'.self::TX_ID.'/status' => Http::sequence()
                ->push(['stato' => 'IN_ELABORAZIONE'], 200)
                ->push(['stato' => 'COMPLETATA'], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/verifica/result*' => Http::response([
                'numero_fir'  => 'FIR-POLL-OK',
                'progressivo' => 7,
                'protocollo'  => 'RENTRI-POLL',
            ], 200),
        ]);

        $result = app(RentriApiClientInterface::class)->waitFirVidimaResult(self::TX_ID);

        $this->assertSame('FIR-POLL-OK', $result['numero_fir']);
        Http::assertSentCount(3);
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
