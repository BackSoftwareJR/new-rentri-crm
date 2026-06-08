<?php

namespace Tests\Feature\Sprint6;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Http\Livewire\Segreteria\Fir\FirBlocchiIndex;
use App\Http\Livewire\Segreteria\Fir\FirIndex;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class FirHttpTest extends TestCase
{
    use SeedsRentriCertificate;
    public function test_segreteria_can_access_fir_list_and_blocchi(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('segreteria.fir'))->assertOk();
        $this->actingAs($user)->get(route('segreteria.fir.blocchi'))->assertOk();

        Livewire::actingAs($user)->test(FirIndex::class)->assertSuccessful();
        Livewire::actingAs($user)->test(FirBlocchiIndex::class)->assertSuccessful();
    }

    public function test_segreteria_can_create_blocco_and_vidima_fir(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-TEST']);

        Livewire::actingAs($user)
            ->test(FirBlocchiIndex::class)
            ->set('codice_blocco', 'BLK-01')
            ->set('num_iscr_sito', 'SITE-TEST')
            ->call('salvaBlocco')
            ->assertHasNoErrors();

        $trasporto = $this->seedTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('vidimaFir')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('firs', 1);
        $this->assertNotNull($trasporto->fresh()->fir_id);

        Livewire::actingAs($user)
            ->test(FirIndex::class)
            ->assertSee('SITE-TEST-BLK-01-0001');
    }

    public function test_operatore_cannot_access_fir_pages(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('segreteria.fir'))->assertForbidden();
    }

    private function seedTrasporto(): Trasporto
    {
        FirBlocco::firstOrCreate(
            ['codice_blocco' => 'BLK-01', 'num_iscr_sito' => 'SITE-TEST'],
            ['progressivo_ultimo' => 0],
        );

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
