<?php

namespace Tests\Feature\Sprint75;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Cycle6ClosureGoLiveTest extends TestCase
{
    public function test_uat_ciclo_6_checklist_covers_all_sprint_modules(): void
    {
        $path = base_path('docs/UAT-CICLO-6-CHECKLIST.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('Sprint 61', $content);
        $this->assertStringContainsString('Sprint 74', $content);
        $this->assertStringContainsString('/segreteria/ecommerce', $content);
        $this->assertStringContainsString('/admin/audit', $content);
        $this->assertStringContainsString('KPI cache', $content);
    }

    public function test_go_live_ciclo_6_consolidates_sign_off_and_smoke_commands(): void
    {
        $path = base_path('docs/GO-LIVE-CICLO-6.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('UAT-CICLO-6-CHECKLIST.md', $content);
        $this->assertStringContainsString('PERFORMANCE-MONITORING.md', $content);
        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('rentri:preflight', $content);
        $this->assertStringContainsString('k6-authenticated.js', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 6', $content);
    }

    public function test_ciclo_6_piano_marks_cycle_closed(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-6-PIANO-MODULI-COMPLETI.md'));

        $this->assertStringContainsString('CHIUSO ✅', $content);
        $this->assertStringContainsString('Sprint 75 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-CICLO-6.md', $content);
    }

    public function test_backlog_section_9_marks_ciclo_6_closed(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI_VERTICAL_BACKLOG.md'));

        $this->assertStringContainsString('Ciclo 6 — Completamento verticale moduli (sprint 61–75) ✅ CHIUSO', $content);
        $this->assertStringContainsString('Completato Sprint 75', $content);
        $this->assertStringContainsString('Gap post-ciclo 6', $content);
    }

    public function test_readme_links_ciclo_6_and_performance_monitoring(): void
    {
        $content = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('Ciclo 6 — Completamento verticale moduli', $content);
        $this->assertStringContainsString('CHIUSO', $content);
        $this->assertStringContainsString('GO-LIVE-CICLO-6.md', $content);
        $this->assertStringContainsString('PERFORMANCE-MONITORING.md', $content);
        $this->assertStringContainsString('k6-authenticated.js', $content);
    }

    public function test_rentri_preflight_demo_smoke_exits_successfully(): void
    {
        config([
            'demo.enabled'                  => true,
            'demo.rentri.force_sandbox_api' => true,
            'demo.rentri.offline_no_http'   => true,
            'app.env'                       => 'demo',
            'services.rentri.api_stub'      => true,
        ]);

        $exitCode = Artisan::call('rentri:preflight', ['--demo' => true]);

        $this->assertSame(0, $exitCode);
    }

    public function test_k6_authenticated_script_referenced_in_performance_doc(): void
    {
        $perf = file_get_contents(base_path('docs/PERFORMANCE-MONITORING.md'));
        $k6 = file_get_contents(base_path('scripts/k6-authenticated.js'));

        $this->assertStringContainsString('k6-authenticated.js', $perf);
        $this->assertStringContainsString('segreteriaFlow', $k6);
        $this->assertStringContainsString('operatoreFlow', $k6);
    }
}
