<?php

namespace Tests\Feature\Sprint58;

use App\Domain\Trasporti\TrasportoTrackingService;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Operatore\Profilo;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Http\Livewire\Segreteria\Rentri;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Http\Livewire\Segreteria\Vfu\VfuIndex;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingHelpAriaLiveTest extends TestCase
{
    public function test_dashboard_exposes_onboarding_tour_markers(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('data-tour="welcome"', false)
            ->assertSee('data-tour="quick-actions"', false)
            ->assertSee('data-tour="dashboard-widgets"', false)
            ->assertSee('data-tour="rentri-shortcut"', false)
            ->assertSee('seg-contextual-help', false);
    }

    public function test_rentri_page_exposes_tour_and_contextual_help(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('data-tour="rentri-trasmissione"', false)
            ->assertSee('Trasmissione RENTRI')
            ->assertSee('seg-contextual-help', false);
    }

    public function test_flash_region_exposes_aria_live_polite_wrapper(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Profilo::class)
            ->set('name', 'Operatore Sprint 58')
            ->call('salva')
            ->assertSee('id="seg-flash-region"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('aria-atomic="true"', false);
    }

    public function test_alert_component_includes_aria_atomic(): void
    {
        $html = Blade::render('<x-alert type="success">Operazione completata</x-alert>');

        $this->assertStringContainsString('aria-atomic="true"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
    }

    public function test_trasporto_in_transito_shows_tracking_stub(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->seedTrasportoInTransito();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Tracking GPS')
            ->assertSee('seg-tracking-map-placeholder', false)
            ->assertSee('Apri mappa destinazione')
            ->assertSee('openstreetmap.org', false);
    }

    public function test_trasporto_tracking_service_returns_null_when_not_in_transito(): void
    {
        $trasporto = $this->seedTrasportoInTransito();
        $trasporto->update(['stato' => TrasportoStato::InPreparazione]);

        $service = app(TrasportoTrackingService::class);

        $this->assertNull($service->mapSearchUrl($trasporto->fresh()));
        $this->assertFalse($service->isTrackingAvailable($trasporto->fresh()));
    }

    public function test_redis_session_prep_doc_exists(): void
    {
        $path = base_path('docs/REDIS-SESSION-PREP.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('SESSION_DRIVER=redis', file_get_contents($path));
        $this->assertStringContainsString('Redis session non attivo', file_get_contents($path));
    }

    public function test_vfu_index_has_contextual_help(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(VfuIndex::class)
            ->assertSee('Pratiche VFU')
            ->assertSee('seg-contextual-help', false);
    }

    private function seedTrasportoInTransito(): Trasporto
    {
        $cer = CodiceCer::factory()->create();
        $dest = Anagrafica::factory()->create([
            'tipo' => 'impianto',
            'ragione_sociale' => 'Impianto Test Sprint 58',
        ]);

        return Trasporto::create([
            'codice_cer_id' => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'stato' => TrasportoStato::InTransito,
            'quantita_kg' => 100,
        ]);
    }
}
