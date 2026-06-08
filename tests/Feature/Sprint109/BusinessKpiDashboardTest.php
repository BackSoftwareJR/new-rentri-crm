<?php

namespace Tests\Feature\Sprint109;

use App\Domain\Dashboard\BusinessKpiDashboardService;
use App\Enums\OrdineEcommerceStato;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\CodiceCer;
use App\Models\EcommerceOrdine;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\User;
use App\Models\VfuRegistration;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessKpiDashboardTest extends TestCase
{
    public function test_metrics_count_confirmed_orders_in_window(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(BusinessKpiDashboardService::class);
        [$from, $to] = $service->resolvePeriod('last_7_days');

        EcommerceOrdine::create([
            'user_id'       => $user->id,
            'stato'         => OrdineEcommerceStato::Confermato,
            'totale'        => 120.50,
            'righe'         => [],
            'confermato_at' => now(),
        ]);

        EcommerceOrdine::create([
            'user_id'       => $user->id,
            'stato'         => OrdineEcommerceStato::Bozza,
            'totale'        => 50,
            'righe'         => [],
        ]);

        $metrics = $service->metricsForRange($from, $to);

        $this->assertGreaterThanOrEqual(1, $metrics['ecommerce']['ordini_confermati']);
        $this->assertGreaterThanOrEqual(120.50, $metrics['ecommerce']['revenue_eur']);
    }

    public function test_metrics_count_vfu_accettate_by_data_accettazione(): void
    {
        $service = app(BusinessKpiDashboardService::class);
        [$from, $to] = $service->resolvePeriod('last_7_days');

        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create();
        $vfu->forceFill(['data_accettazione' => now()->toDateString()])->save();

        $metrics = $service->metricsForRange($from, $to);

        $this->assertGreaterThanOrEqual(1, $metrics['vfu']['accettate']);
    }

    public function test_metrics_sum_magazzino_movimenti_kg(): void
    {
        $service = app(BusinessKpiDashboardService::class);
        [$from, $to] = $service->resolvePeriod('last_7_days');

        $cer = CodiceCer::factory()->create();
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 75,
            'data_movimento'   => now(),
            'rentri_trasmesso' => false,
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
        ]);
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Scarico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 25,
            'data_movimento'   => now(),
            'rentri_trasmesso' => false,
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 2,
        ]);

        $metrics = $service->metricsForRange($from, $to);

        $this->assertGreaterThanOrEqual(100, $metrics['magazzino']['movimenti_kg']);
        $this->assertGreaterThanOrEqual(2, $metrics['magazzino']['movimenti']);
    }

    public function test_comparison_includes_delta_vs_previous_window(): void
    {
        $vfu = VfuRegistration::factory()->accettatoPerBonifica()->create();
        $vfu->forceFill(['data_accettazione' => now()->toDateString()])->save();

        $comparison = app(BusinessKpiDashboardService::class)->comparisonForPeriod('last_7_days');

        $this->assertArrayHasKey('delta', $comparison);
        $this->assertArrayHasKey('ordini_confermati', $comparison['delta']);
        $this->assertArrayHasKey('vfu_accettate', $comparison['delta']);
        $this->assertArrayHasKey('magazzino_kg', $comparison['delta']);
        $this->assertArrayHasKey('revenue_eur', $comparison['delta']);
        $this->assertContains($comparison['delta']['vfu_accettate']['direction'], ['up', 'down', 'flat']);
        $this->assertSame(7, $comparison['days']);
    }

    public function test_dashboard_method_supports_7_and_30_day_windows(): void
    {
        $service = app(BusinessKpiDashboardService::class);

        $week = $service->dashboard(7);
        $month = $service->dashboard(30);

        $this->assertSame('Ultimi 7 giorni', $week['label']);
        $this->assertSame('Ultimi 30 giorni', $month['label']);
        $this->assertSame(7, $week['days']);
        $this->assertSame(30, $month['days']);
    }

    public function test_threshold_status_uses_configured_soglie(): void
    {
        $service = app(BusinessKpiDashboardService::class);

        $this->assertSame('alert', $service->thresholdStatus('ordini_confermati', 0));
        $this->assertSame('warn', $service->thresholdStatus('ordini_confermati', 3));
        $this->assertSame('ok', $service->thresholdStatus('ordini_confermati', 10));
    }

    public function test_dashboard_renders_business_kpi_v2_widget_with_drill_down(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('KPI business v3')
            ->assertSee('Ordini e-commerce confermati')
            ->assertSee('VFU accettate')
            ->assertSee('Movimenti magazzino (kg)')
            ->assertSee('Revenue (stub ordini)')
            ->assertSeeHtml('href="'.route('segreteria.ecommerce').'"')
            ->assertSeeHtml('href="'.route('segreteria.vfu.index').'"')
            ->assertSeeHtml('href="'.route('segreteria.registro-movimenti').'"');
    }

    public function test_kpi_business_dashboard_v2_doc_exists(): void
    {
        $path = base_path('docs/KPI-BUSINESS-DASHBOARD-V2.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('ordini_confermati', file_get_contents($path));
        $this->assertStringContainsString('threshold', strtolower(file_get_contents($path)));
    }
}
