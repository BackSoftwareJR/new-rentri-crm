<?php

namespace Tests\Feature\Sprint37;

use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class DemoWalkthroughDashboardTest extends TestCase
{
    public function test_walkthrough_card_visible_in_demo_mode(): void
    {
        Config::set('demo.enabled', true);
        Artisan::call('rentri:demo-seed');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Prova flusso RENTRI')
            ->assertSee('Impostazioni RENTRI')
            ->assertSee('Blocchi FIR')
            ->assertSee('Trasmissione registro')
            ->assertSee('Fixture demo caricate');

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Vidima e firma xFIR');
    }

    public function test_walkthrough_card_hidden_in_production_mode(): void
    {
        Config::set('demo.enabled', false);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertDontSee('Prova flusso RENTRI');
    }

    public function test_walkthrough_shows_seed_hint_when_not_seeded(): void
    {
        Config::set('demo.enabled', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Prova flusso RENTRI')
            ->assertSee('rentri:demo-seed');
    }
}
