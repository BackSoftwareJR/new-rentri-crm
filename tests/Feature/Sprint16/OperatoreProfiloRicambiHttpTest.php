<?php

namespace Tests\Feature\Sprint16;

use App\Http\Livewire\Operatore\Profilo;
use App\Http\Livewire\Operatore\Ricambi;
use App\Models\EcommerceProdotto;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class OperatoreProfiloRicambiHttpTest extends TestCase
{
    public function test_operatore_can_view_and_update_profilo(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('operatore.profilo'))
            ->assertOk()
            ->assertSee('Profilo')
            ->assertSee('operatore@example.com');

        Livewire::actingAs($user)
            ->test(Profilo::class)
            ->set('name', 'Mario Operatore')
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertSame('Mario Operatore', $user->fresh()->name);
        $this->assertSame('operatore@example.com', $user->fresh()->email);
    }

    public function test_operatore_can_browse_ricambi_catalog(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        EcommerceProdotto::factory()->create([
            'codice'   => 'OP-RIC-01',
            'nome'     => 'Faro posteriore',
            'giacenza' => 3,
            'attivo'   => true,
        ]);
        EcommerceProdotto::factory()->create([
            'codice'   => 'OP-RIC-ES',
            'nome'     => 'Esaurito',
            'giacenza' => 0,
            'attivo'   => true,
        ]);

        $this->actingAs($user)
            ->get(route('operatore.ricambi'))
            ->assertOk()
            ->assertSee('Faro posteriore')
            ->assertSee('OP-RIC-01')
            ->assertDontSee('OP-RIC-ES');

        Livewire::actingAs($user)
            ->test(Ricambi::class)
            ->set('search', 'Faro')
            ->assertSee('Faro posteriore');
    }

    public function test_segreteria_cannot_access_operatore_routes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('operatore.profilo'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('operatore.ricambi'))
            ->assertForbidden();
    }

    public function test_policies_allow_operatore_catalog_and_profile(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();
        $prodotto = EcommerceProdotto::factory()->create(['attivo' => true, 'giacenza' => 1]);

        $this->assertTrue(Gate::forUser($operatore)->allows('updateProfile', $operatore));
        $this->assertTrue(Gate::forUser($operatore)->allows('viewAny', EcommerceProdotto::class));
        $this->assertTrue(Gate::forUser($operatore)->allows('view', $prodotto));
        $this->assertFalse(Gate::forUser($segreteria)->allows('updateProfile', $operatore));
    }
}
