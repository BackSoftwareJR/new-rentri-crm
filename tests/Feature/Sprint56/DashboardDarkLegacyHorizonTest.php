<?php

namespace Tests\Feature\Sprint56;

use App\Domain\Legacy\LegacyImportService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardDarkLegacyHorizonTest extends TestCase
{
    public function test_dashboard_has_draggable_widget_container(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('id="seg-dashboard-widgets"', false)
            ->assertSee('data-widget-id="vfu-bonifica"', false)
            ->assertSee('Trascina le sezioni per riordinare', false);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('data-widget-id="migrazione-legacy"', false)
            ->assertSee('Stato import per entità');
    }

    public function test_segreteria_layout_includes_theme_toggle_stub(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('id="seg-theme-toggle"', false)
            ->assertSee('data-seg-theme-toggle', false)
            ->assertSee('document.documentElement.dataset.theme', false);
    }

    public function test_admin_sees_horizon_link_when_installed(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Horizon', false);
    }

    public function test_legacy_report_rows_include_status(): void
    {
        $rows = app(LegacyImportService::class)->reportRows();

        $this->assertCount(5, $rows);
        $this->assertSame('Anagrafiche', $rows[0]['label']);
        $this->assertSame('empty', $rows[0]['status']);
    }

    public function test_artisan_legacy_report_outputs_table_summary(): void
    {
        $service = app(LegacyImportService::class);
        $service->import('anagrafiche', $service->defaultFixturePath('anagrafiche'));

        $exit = Artisan::call('rentri:import-legacy', ['--report' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Report import legacy', $output);
        $this->assertStringContainsString('Anagrafiche', $output);
        $this->assertStringContainsString('✓ importato', $output);
        $this->assertStringContainsString('Totale record legacy tracciati:', $output);
    }

    public function test_owasp_internal_checklist_doc_exists(): void
    {
        $path = base_path('docs/OWASP-INTERNAL-CHECKLIST.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('A01 — Broken Access Control', file_get_contents($path));
    }
}
