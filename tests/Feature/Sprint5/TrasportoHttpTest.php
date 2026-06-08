<?php

namespace Tests\Feature\Sprint5;

use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportiIndex;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\MagazzinoRifiuto;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class TrasportoHttpTest extends TestCase
{
    use SeedsRentriCertificate;
    public function test_segreteria_can_access_trasporti_list(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.trasporti'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(TrasportiIndex::class)
            ->assertSuccessful()
            ->assertSee('Trasporti rifiuti');
    }

    public function test_operatore_cannot_access_trasporti_list(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.trasporti'))
            ->assertForbidden();
    }

    public function test_segreteria_can_advance_trasporto_via_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->seedTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->call('avviaTransito')
            ->assertHasNoErrors();

        $this->assertSame(TrasportoStato::InTransito, $trasporto->fresh()->stato);

        app(RentriFirServiceInterface::class)->vidima($trasporto->fresh());

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto->fresh()])
            ->call('completa')
            ->assertHasNoErrors();

        $this->assertSame(TrasportoStato::Completato, $trasporto->fresh()->stato);
    }

    private function seedTrasporto(): Trasporto
    {
        $this->seedRentriCertificate(['num_iscr_sito' => 'SITE-HTTP']);
        FirBlocco::firstOrCreate(
            ['codice_blocco' => 'BLK-HTTP', 'num_iscr_sito' => 'SITE-HTTP'],
            ['progressivo_ultimo' => 0],
        );

        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 60]);
        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        app(MagazzinoSvuotamentoService::class)->richiediSvuotamento(
            $cer->id, $impianto->id, $trasportatore->id, false, 25, null,
            User::factory()->create()->id,
        );

        return Trasporto::firstOrFail();
    }
}
