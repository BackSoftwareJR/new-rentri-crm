<?php

namespace Tests\Feature\Sprint74;

use App\Domain\Dashboard\DashboardKpiService;
use App\Domain\Dashboard\KpiRedisCacheService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Support\DashboardReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceKpiCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'dashboard.kpi_cache.enabled' => true,
            'dashboard.kpi_cache.store'   => 'array',
            'dashboard.kpi_cache.ttl_seconds' => 300,
        ]);
        Cache::store('array')->flush();
        app(KpiRedisCacheService::class)->forget();
    }

    public function test_kpi_cache_returns_miss_then_hit(): void
    {
        $service = app(KpiRedisCacheService::class);
        $kpi = app(DashboardKpiService::class);

        $first = $service->aggregate($kpi);
        $this->assertFalse($first['cache']['hit']);
        $this->assertTrue($first['cache']['enabled']);
        $this->assertSame('array', $first['cache']['driver']);

        $second = $service->aggregate($kpi);
        $this->assertTrue($second['cache']['hit']);
        $this->assertSame($first['kpi'], $second['kpi']);
    }

    public function test_event_driven_invalidation_clears_cache_on_vfu_save(): void
    {
        $service = app(KpiRedisCacheService::class);
        $kpi = app(DashboardKpiService::class);

        $service->aggregate($kpi);
        $this->assertTrue($service->aggregate($kpi)['cache']['hit']);

        VfuRegistration::factory()->create(['stato' => \App\Enums\VfuStato::Accettato]);

        $this->assertFalse($service->aggregate($kpi)['cache']['hit']);
    }

    public function test_manual_forget_invalidates_cache(): void
    {
        $service = app(KpiRedisCacheService::class);
        $kpi = app(DashboardKpiService::class);

        $service->aggregate($kpi);
        $service->forget();

        $this->assertFalse($service->aggregate($kpi)['cache']['hit']);
    }

    public function test_dashboard_shows_cache_badge_and_refresh_button(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('KPI cache:')
            ->assertSee('Refresh KPI');
    }

    public function test_refresh_kpi_clears_cache_via_livewire(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(KpiRedisCacheService::class);
        $kpi = app(DashboardKpiService::class);

        $service->aggregate($kpi);
        $this->assertTrue($service->aggregate($kpi)['cache']['hit']);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->call('refreshKpi')
            ->assertHasNoErrors()
            ->assertSee('KPI cache: miss');
    }

    public function test_operatore_cannot_refresh_kpi(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($user)->allows('refreshKpi', DashboardReport::instance()));
    }

    public function test_k6_authenticated_script_exists_with_scenarios(): void
    {
        $path = base_path('scripts/k6-authenticated.js');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('segreteriaFlow', $content);
        $this->assertStringContainsString('operatoreFlow', $content);
        $this->assertStringContainsString('_token', $content);
    }

    public function test_performance_monitoring_doc_exists(): void
    {
        $path = base_path('docs/PERFORMANCE-MONITORING.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('KpiRedisCacheService', file_get_contents($path));
        $this->assertStringContainsString('Horizon', file_get_contents($path));
    }
}
