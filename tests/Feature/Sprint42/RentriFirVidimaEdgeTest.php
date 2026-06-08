<?php

namespace Tests\Feature\Sprint42;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Domain\Fir\FirBloccoService;
use App\Enums\FirStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriFirVidimaEdgeTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-EDGE']);
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.fir_poll_max_attempts', 2);
        Config::set('services.rentri.fir_poll_interval_ms', 1);
        Config::set('services.rentri.fir_progressivo_max', 100);
    }

    public function test_vidima_rejects_exhausted_blocco(): void
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-FULL',
            'num_iscr_sito'      => 'SITE-EDGE',
            'progressivo_ultimo' => 100,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('esaurito');

        app(RentriFirServiceInterface::class)->vidima($this->seedTrasporto());
    }

    public function test_blocco_service_reports_zero_disponibilita_when_esaurito(): void
    {
        $blocco = FirBlocco::create([
            'codice_blocco'      => 'BLK-ZERO',
            'num_iscr_sito'      => 'SITE-EDGE',
            'progressivo_ultimo' => 100,
        ]);

        $service = app(FirBloccoService::class);
        $this->assertTrue($service->isEsaurito($blocco));
        $this->assertSame(0, $service->conteggioDisponibile($blocco));
    }

    public function test_poll_timeout_returns_italian_message(): void
    {
        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/BLK-TIMEOUT' => Http::response([
                'transazione_id' => 'tx-timeout',
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/tx-timeout/status' => Http::response([
                'stato' => 'IN_ELABORAZIONE',
            ], 200),
        ]);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-TIMEOUT',
            'num_iscr_sito'      => 'SITE-EDGE',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();

        try {
            app(RentriFirServiceInterface::class)->vidima($trasporto);
            $this->fail('Expected timeout exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Timeout attesa esito vidimazione MASE', $e->getMessage());
            $this->assertStringContainsString('2 tentativi', $e->getMessage());
        }
    }

    public function test_signing_blocked_when_qr_payload_invalid(): void
    {
        $fir = Fir::create([
            'numero_fir'       => 'FIR-INCOMPLETE',
            'codice_blocco'    => 'BLK-X',
            'progressivo'      => 1,
            'stato'            => FirStato::Vidimato,
            'vidimato_at'      => now(),
            'peso_partenza_kg' => 10,
            'qr_payload'       => json_encode(['numero_fir' => 'FIR-INCOMPLETE'], JSON_THROW_ON_ERROR),
        ]);

        $signing = app(RentriFirSigningServiceInterface::class);
        $this->assertFalse($signing->canSign($fir));
        $this->assertStringContainsString('QR', (string) $signing->signBlockReason($fir));

        $this->expectException(\RuntimeException::class);
        $signing->sign($fir);
    }

    public function test_trasporto_ui_shows_exhausted_blocco_blocker(): void
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-UI',
            'num_iscr_sito'      => RentriSetting::instance()->num_iscr_sito ?? 'SITE-EDGE',
            'progressivo_ultimo' => 100,
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->seedTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('esaurito')
            ->assertSee('Vidima FIR');
    }

    private function seedTrasporto(): Trasporto
    {
        if (FirBlocco::query()->where('num_iscr_sito', 'SITE-EDGE')->doesntExist()) {
            FirBlocco::create([
                'codice_blocco'      => 'BLK-DEFAULT',
                'num_iscr_sito'      => 'SITE-EDGE',
                'progressivo_ultimo' => 0,
            ]);
        }

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
