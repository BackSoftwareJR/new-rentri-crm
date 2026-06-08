<?php

namespace Tests\Feature\Sprint38;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
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
use App\Services\Rentri\Exceptions\RentriXfirValidationException;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriXfirValidationHttpTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.rentri.firma_stub', true);
        $this->seedRentriCertificate([
            'num_iscr_sito'             => 'SITE-TEST',
            'onboarding_step_completed' => 3,
        ]);
    }

    public function test_signing_still_succeeds_with_xsd_validation_enabled(): void
    {
        $trasporto = $this->seedVidimatoTrasporto();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('firmaXfir')
            ->assertHasNoErrors()
            ->assertSee('Firmato');
    }

    public function test_ui_shows_italian_xsd_errors_on_validation_failure(): void
    {
        $trasporto = $this->seedVidimatoTrasporto();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $mock = Mockery::mock(RentriFirSigningServiceInterface::class);
        $mock->shouldReceive('canSign')->andReturn(true);
        $mock->shouldReceive('signBlockReason')->andReturn(null);
        $mock->shouldReceive('sign')->andThrow(new RentriXfirValidationException(
            'Validazione XSD xFIR fallita.',
            ['Elemento obbligatorio mancante: Quantità trasporto (kg).'],
        ));
        $mock->shouldReceive('signedPayloadFilename')->andReturn('xfir-test.json');
        $this->app->instance(RentriFirSigningServiceInterface::class, $mock);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('firmaXfir')
            ->assertSee('Validazione xFIR non superata')
            ->assertSee('Errori conformità xFIR (XSD MASE v1.0)')
            ->assertSee('Quantità trasporto (kg)');
    }

    private function seedVidimatoTrasporto(): Trasporto
    {
        FirBlocco::create([
            'codice_blocco'      => 'BLK-38',
            'num_iscr_sito'      => 'SITE-TEST',
            'progressivo_ultimo' => 0,
        ]);

        $trasporto = $this->seedTrasporto();
        app(RentriFirServiceInterface::class)->vidima($trasporto);

        return $trasporto->fresh()->load('firCollegato');
    }

    private function seedTrasporto(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@s38.test']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 20, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
