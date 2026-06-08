<?php

namespace Tests\Feature\Sprint39;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\FirStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirTransmissionHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.api_stub', true);
        Config::set('services.rentri.firma_stub', true);
        $this->seedRentriFirmaCertificate(['num_iscr_sito' => 'SITE-HTTP', 'onboarding_step_completed' => 3]);
    }

    public function test_trasporto_ui_invia_xfir_and_shows_protocollo(): void
    {
        $trasporto = $this->seedSignedTrasporto();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Invia xFIR a MASE')
            ->assertSee('Da inviare')
            ->call('inviaXfirMase')
            ->assertHasNoErrors()
            ->assertSee('Trasmesso')
            ->assertSee('Protocollo:');

        $trasporto->refresh();
        $this->assertSame(FirStato::Trasmesso, $trasporto->firCollegato->stato);
        $this->assertNotNull($trasporto->firCollegato->xfir_protocollo);
    }

    public function test_storico_api_lists_xfir_tipo(): void
    {
        $trasporto = $this->seedSignedTrasporto();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('inviaXfirMase');

        $this->actingAs($user)
            ->get(route('segreteria.rentri.transazioni', ['tipo_api' => 'xfir']))
            ->assertOk()
            ->assertSee('xFIR firmato');
    }

    private function seedSignedTrasporto(): Trasporto
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-HTTP',
            'num_iscr_sito'      => 'SITE-HTTP',
            'progressivo_ultimo' => 0,
        ]);

        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@http.test']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        $trasporto = Trasporto::firstOrFail();
        app(RentriFirServiceInterface::class)->vidima($trasporto);
        app(RentriFirSigningServiceInterface::class)->sign($trasporto->fresh()->firCollegato);

        return $trasporto->fresh()->load('firCollegato');
    }
}
