<?php

namespace Tests\Feature\Sprint14;

use App\Enums\MudStato;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\MudDichiarazione;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardHttpTest extends TestCase
{
    public function test_segreteria_dashboard_shows_cross_module_kpi_sections(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        EcommerceProdotto::factory()->create(['nome' => 'KPI Ricambio Test', 'giacenza' => 5]);
        MudDichiarazione::create([
            'anno_riferimento' => 2018,
            'stato'            => MudStato::Bozza,
            'righe'            => [],
            'user_id'          => $user->id,
        ]);
        EcommerceOrdine::create([
            'user_id' => $user->id,
            'stato'   => OrdineEcommerceStato::Bozza,
            'totale'  => 99,
            'righe'   => [],
        ]);
        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'registro',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/registro'],
            'response_json'  => [],
            'completed_at'   => now(),
        ]);

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('VFU & Bonifica')
            ->assertSee('Magazzino & Registro')
            ->assertSee('RENTRI')
            ->assertSee('MUD & E-commerce')
            ->assertSee('Movimenti da trasmettere')
            ->assertSee('Ordini bozza');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Dichiarazioni MUD')
            ->assertSee('Ricambi in catalogo')
            ->assertSee('Errori API');
    }

    public function test_operatore_cannot_access_segreteria_dashboard(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertForbidden();
    }
}
