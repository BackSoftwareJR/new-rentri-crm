<?php

namespace Tests\Feature\Sprint68;

use App\Domain\Dashboard\DashboardAnalyticsService;
use App\Domain\Dashboard\KpiExportService;
use App\Enums\MudStato;
use App\Enums\RegistroMovimentoTipo;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\RentriTransazione;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Support\DashboardReport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    public function test_metrics_for_current_month_count_cross_module_activity(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $analytics = app(DashboardAnalyticsService::class);
        [$from, $to] = $analytics->resolvePeriod('current_month');

        VfuRegistration::factory()->create(['created_at' => now()]);
        VfuRegistration::factory()->create([
            'bonifica_pericolosi_completata_at' => now(),
        ]);

        $cer = CodiceCer::factory()->create();
        RegistroMovimento::create([
            'tipo'             => RegistroMovimentoTipo::Carico,
            'codice_cer_id'    => $cer->id,
            'peso_kg'          => 40,
            'data_movimento'   => now(),
            'rentri_trasmesso' => false,
            'source_type'      => MagazzinoCaricoManuale::class,
            'source_id'        => 1,
        ]);

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'health',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'GET'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
            'created_at'     => now(),
        ]);

        MudDichiarazione::create([
            'anno_riferimento' => 2024,
            'stato'            => MudStato::Inviata,
            'righe'            => [],
            'user_id'          => $user->id,
            'inviata_at'       => now(),
        ]);

        $metrics = $analytics->metricsForRange($from, $to);

        $this->assertGreaterThanOrEqual(2, $metrics['vfu']['nuove_pratiche']);
        $this->assertGreaterThanOrEqual(1, $metrics['vfu']['bonifiche_pericolosi']);
        $this->assertGreaterThanOrEqual(1, $metrics['magazzino']['movimenti']);
        $this->assertGreaterThanOrEqual(1, $metrics['rentri']['totale']);
        $this->assertGreaterThanOrEqual(1, $metrics['mud']['inviate']);
    }

    public function test_monthly_trend_returns_six_month_rows(): void
    {
        $rows = app(DashboardAnalyticsService::class)->monthlyTrend(6);

        $this->assertCount(6, $rows);
        $this->assertArrayHasKey('vfu_nuove', $rows[0]);
        $this->assertArrayHasKey('label', $rows[5]);
    }

    public function test_comparison_includes_delta_vs_previous_period(): void
    {
        VfuRegistration::factory()->create(['created_at' => now()]);

        $comparison = app(DashboardAnalyticsService::class)->comparisonForPeriod('current_month');

        $this->assertArrayHasKey('delta', $comparison);
        $this->assertArrayHasKey('vfu_nuove', $comparison['delta']);
        $this->assertContains($comparison['delta']['vfu_nuove']['direction'], ['up', 'down', 'flat']);
    }

    public function test_kpi_export_service_builds_monthly_csv_rows(): void
    {
        $rows = app(KpiExportService::class)->rowsForMonthlyTrend(3);

        $this->assertCount(3, $rows);
        $this->assertSame(
            ['mese', 'vfu_nuove_pratiche', 'magazzino_movimenti', 'rentri_transazioni', 'mud_inviate'],
            array_keys($rows[0]),
        );
    }

    public function test_dashboard_renders_analytics_widget(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Report & analytics')
            ->assertSee('Trend mensile')
            ->assertSee('Export KPI mensile');
    }

    public function test_dashboard_export_kpi_csv_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('exportKpiCsv')
            ->assertSuccessful();
    }

    public function test_operatore_cannot_export_dashboard_kpi(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('export', DashboardReport::instance()));
    }
}
