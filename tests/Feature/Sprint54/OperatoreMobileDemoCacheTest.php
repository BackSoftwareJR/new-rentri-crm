<?php

namespace Tests\Feature\Sprint54;

use App\Http\Livewire\Operatore\Profilo;
use App\Http\Livewire\Segreteria\Ecommerce\EcommerceIndex;
use App\Enums\MudStato;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\MudDichiarazione;
use App\Models\RentriSetting;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class OperatoreMobileDemoCacheTest extends TestCase
{
    public function test_operatore_pages_use_layout_header_without_duplicate_section_titles(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        foreach (['operatore.ricambi', 'operatore.vetrina', 'operatore.profilo'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('op-header-title', false)
                ->assertDontSee('op-section-title', false);
        }

        $this->actingAs($user)
            ->get(route('operatore.ricambi'))
            ->assertSee('Ricambi', false);

        $this->actingAs($user)
            ->get(route('operatore.vetrina'))
            ->assertSee('Vetrina', false);

        $this->actingAs($user)
            ->get(route('operatore.profilo'))
            ->assertSee('Profilo', false);
    }

    public function test_bottom_nav_shows_bonifica_badge_when_veicoli_pending(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        VfuRegistration::factory()->attesaBonifica()->create();

        $this->actingAs($user)
            ->get(route('operatore.dashboard'))
            ->assertOk()
            ->assertSee('op-nav-badge', false)
            ->assertSee('veicoli da bonificare', false);
    }

    public function test_demo_session_denies_cross_write_on_production_ecommerce_ordini(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $ordineId = (int) DB::table('ecommerce_ordini')->insertGetId([
            'user_id'    => $user->id,
            'stato'      => OrdineEcommerceStato::Bozza->value,
            'totale'     => 25,
            'righe'      => '[]',
            'is_demo'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $ordine = EcommerceOrdine::withoutGlobalScopes()->findOrFail($ordineId);

        $this->assertFalse(Gate::forUser($user)->allows('view', $ordine));
        $this->assertFalse(Gate::forUser($user)->allows('update', $ordine));
    }

    public function test_demo_session_denies_mud_update_on_production_dichiarazione(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $mudId = (int) DB::table('mud_dichiarazioni')->insertGetId([
            'anno_riferimento' => 2022,
            'stato'            => MudStato::Bozza->value,
            'righe'            => '[]',
            'user_id'          => $user->id,
            'is_demo'          => false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $mud = MudDichiarazione::withoutGlobalScopes()->findOrFail($mudId);

        $this->assertFalse(Gate::forUser($user)->allows('update', $mud));
    }

    public function test_rentri_setting_instance_caches_within_request(): void
    {
        $first = RentriSetting::instance();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $second = RentriSetting::instance();
        $third = RentriSetting::instance();

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame($second->getKey(), $third->getKey());

        $settingQueries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => str_contains($q['query'], 'rentri_settings'));

        $this->assertCount(2, $settingQueries);
    }

    public function test_ecommerce_index_shows_empty_state_and_form_field_filters(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(EcommerceIndex::class)
            ->set('search', 'ZZZ-NOMATCH-S54')
            ->assertSee('seg-empty-state', false)
            ->assertSee('Nessun ricambio trovato')
            ->assertSee('id="categoria"', false)
            ->assertSee('id="search"', false);
    }

    public function test_flash_alert_exposes_status_and_aria_live_for_screen_readers(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Profilo::class)
            ->set('name', 'Operatore Sprint 54')
            ->call('salva')
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false);
    }
}
