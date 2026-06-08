<?php

namespace Tests\Feature\Sprint59;

use App\Http\Livewire\Segreteria\Dashboard;
use App\Http\Livewire\Segreteria\Magazzino\RegistroMovimentiIndex;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class TabletPrintContrastWafTest extends TestCase
{
    public function test_segreteria_layout_has_tablet_sidebar_markers(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('data-seg-layout="segreteria"', false)
            ->assertSee('data-seg-tablet-sidebar="true"', false)
            ->assertSee('id="seg-tablet-nav-toggle"', false)
            ->assertSee('seg-tablet-nav-toggle', false);
    }

    public function test_gestionale_css_includes_tablet_breakpoint_rules(): void
    {
        $css = file_get_contents(resource_path('css/gestionale.css'));

        $this->assertStringContainsString('@media (min-width: 768px) and (max-width: 1024px)', $css);
        $this->assertStringContainsString('.seg-tablet-nav-toggle', $css);
    }

    public function test_topbar_includes_high_contrast_toggle(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('id="seg-contrast-toggle"', false)
            ->assertSee('data-seg-contrast-toggle', false)
            ->assertSee('high-contrast', false);
    }

    public function test_gestionale_css_includes_high_contrast_theme(): void
    {
        $css = file_get_contents(resource_path('css/gestionale.css'));

        $this->assertStringContainsString('[data-theme="high-contrast"]', $css);
    }

    public function test_registro_has_print_stylesheet_markers(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RegistroMovimentiIndex::class)
            ->assertSee('id="seg-registro-print"', false)
            ->assertSee('seg-registro-print', false)
            ->assertSee('Stampa registro')
            ->assertSee('seg-no-print', false);

        $css = file_get_contents(resource_path('css/gestionale.css'));
        $this->assertStringContainsString('.seg-registro-print', $css);
        $this->assertStringContainsString('@media print', $css);
    }

    public function test_waf_rules_prep_doc_exists(): void
    {
        $path = base_path('docs/WAF-RULES-PREP.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('WAF non attivo', file_get_contents($path));
    }

    public function test_k6_smoke_script_exists(): void
    {
        $path = base_path('scripts/k6-smoke.js');

        $this->assertFileExists($path);
        $this->assertStringContainsString('export default function', file_get_contents($path));
    }

    public function test_audit_export_scheduled_command_dry_run(): void
    {
        $exit = Artisan::call('audit:export-scheduled', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Export audit (dry-run) completato', $output);
    }

    public function test_audit_export_scheduling_prep_doc_exists(): void
    {
        $path = base_path('docs/AUDIT-EXPORT-SCHEDULING-PREP.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('audit:export-scheduled', file_get_contents($path));
    }

    public function test_dashboard_renders_on_tablet_layout_context(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Dashboard')
            ->assertSee('data-tour="welcome"', false);

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertSee('data-seg-tablet-sidebar="true"', false);
    }
}
