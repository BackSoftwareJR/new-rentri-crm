<?php

namespace Tests\Feature\Sprint5;

use App\Http\Livewire\Segreteria\Magazzino\SerbatoioShow;
use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\MagazzinoSvuotamento;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MagazzinoSvuotamentoHttpTest extends TestCase
{
    public function test_segreteria_can_request_svuotamento_via_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 80]);

        $impianto = Anagrafica::factory()->create(['tipo' => 'impianto', 'email' => 'imp@test.local']);
        $trasportatore = Anagrafica::factory()->create(['tipo' => 'trasportatore', 'gestisce_trasporti' => true]);
        Authorization::factory()->create(['anagrafica_id' => $trasportatore->id, 'scade_il' => now()->addYear()]);

        Livewire::actingAs($user)
            ->test(SerbatoioShow::class, ['codiceCer' => $cer])
            ->set('impianto_id', $impianto->id)
            ->set('trasportatore_id', $trasportatore->id)
            ->set('svuotamento_quantita_kg', '40')
            ->set('svuotamento_note', 'Richiesta test HTTP')
            ->call('richiediSvuotamento')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('magazzino_svuotamenti', [
            'codice_cer_id' => $cer->id,
            'anagrafica_id' => $impianto->id,
            'stato' => 'richiesto',
            'quantita_kg' => 40,
        ]);
        $this->assertDatabaseCount('trasporti', 1);
    }

    public function test_operatore_cannot_access_serbatoio_for_svuotamento(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);

        $this->actingAs($user)
            ->get(route('segreteria.magazzino.show', $cer))
            ->assertForbidden();

        $this->assertSame(0, MagazzinoSvuotamento::count());
    }
}
