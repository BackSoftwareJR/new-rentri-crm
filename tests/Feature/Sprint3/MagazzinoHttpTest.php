<?php

namespace Tests\Feature\Sprint3;

use App\Http\Livewire\Segreteria\Magazzino\MagazzinoIndex;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentiIndex;
use App\Http\Livewire\Segreteria\Magazzino\SerbatoioShow;
use App\Models\CodiceCer;
use App\Models\MagazzinoRifiuto;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MagazzinoHttpTest extends TestCase
{
    public function test_segreteria_can_access_magazzino_index(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.magazzino'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(MagazzinoIndex::class)
            ->assertSuccessful();
    }

    public function test_operatore_cannot_access_magazzino_index(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(MagazzinoIndex::class)
            ->assertForbidden();
    }

    public function test_segreteria_can_access_serbatoio_show(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['limite_kg' => 1000]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 50]);

        $this->actingAs($user)
            ->get(route('segreteria.magazzino.show', $cer))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(SerbatoioShow::class, ['codiceCer' => $cer])
            ->assertSuccessful()
            ->assertSee($cer->codice);
    }

    public function test_segreteria_can_register_carico_manuale_via_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create(['limite_kg' => 500]);
        MagazzinoRifiuto::create(['codice_cer_id' => $cer->id, 'quantita_attuale_kg' => 10]);

        Livewire::actingAs($user)
            ->test(SerbatoioShow::class, ['codiceCer' => $cer])
            ->set('peso_kg', '25.5')
            ->set('note', 'Carico test da HTTP')
            ->call('salvaCarico')
            ->assertHasNoErrors();

        $this->assertSame(35.5, (float) MagazzinoRifiuto::where('codice_cer_id', $cer->id)->value('quantita_attuale_kg'));
        $this->assertDatabaseHas('registro_movimenti', [
            'codice_cer_id' => $cer->id,
            'peso_kg'       => 25.5,
        ]);
    }

    public function test_segreteria_can_access_registro_movimenti(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.registro-movimenti'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(RegistroMovimentiIndex::class)
            ->assertSuccessful();
    }

    public function test_operatore_cannot_access_registro_movimenti(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RegistroMovimentiIndex::class)
            ->assertForbidden();
    }
}
