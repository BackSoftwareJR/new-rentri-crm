<?php

namespace Tests\Feature\Sprint51;

use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\DemoModeToggle;
use App\Http\Livewire\Segreteria\Rentri;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class UxSecurityQuickWinsTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_sidebar_groups_and_critical_routes_return_ok(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $response = $this->actingAs($user)->get(route('segreteria.dashboard'));

        $response->assertOk()
            ->assertSee('Operativo')
            ->assertSee('RENTRI')
            ->assertSee('Amministrazione');

        $routes = [
            'segreteria.dashboard',
            'segreteria.magazzino',
            'segreteria.rentri',
            'segreteria.trasporti',
            'segreteria.fir',
            'segreteria.impostazioni.rentri',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_critical_pages_use_page_header_component(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.rentri'))
            ->assertOk()
            ->assertSee('RENTRI — Trasmissione registro');

        $this->actingAs($user)
            ->get(route('segreteria.magazzino'))
            ->assertOk()
            ->assertSee('Magazzino rifiuti');
    }

    public function test_operatore_cannot_toggle_demo_via_gate_or_livewire(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($operatore)->allows('demo.toggle'));

        Livewire::actingAs($operatore)
            ->test(DemoModeToggle::class)
            ->call('confirmActivate')
            ->assertForbidden();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email'    => 'unknown@example.com',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post(route('login.store'), [
            'email'    => 'unknown@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_livewire_stub_action_shows_success_flash_in_view(): void
    {
        $this->seedRentriCertificate();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();

        RegistroMovimento::create([
            'tipo'           => RegistroMovimentoTipo::Carico,
            'codice_cer_id'  => $cer->id,
            'peso_kg'        => 15,
            'data_movimento' => now()->subDay(),
            'source_type'    => MagazzinoCaricoManuale::class,
            'source_id'      => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->set('periodo_da', now()->startOfMonth()->toDateString())
            ->set('periodo_a', now()->toDateString())
            ->call('trasmetti')
            ->assertHasNoErrors()
            ->assertSee('seg-alert-success')
            ->assertSee('Trasmissione registro completata');
    }
}
