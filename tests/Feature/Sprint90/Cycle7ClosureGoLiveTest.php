<?php

namespace Tests\Feature\Sprint90;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Cycle7ClosureGoLiveTest extends TestCase
{
    public function test_go_live_enterprise_consolidates_remediation_checklist(): void
    {
        $path = base_path('docs/GO-LIVE-ENTERPRISE.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('CICLO-7-ENTERPRISE-AUDIT.md', $content);
        $this->assertStringContainsString('RentriFirVidimaValidator', $content);
        $this->assertStringContainsString('RENTRI_XFIR_POLL', $content);
        $this->assertStringContainsString('RentriXfirCoseTransmissionMapper', $content);
        $this->assertStringContainsString('x-rentri-api-mode-badge', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 7', $content);
    }

    public function test_go_live_enterprise_documents_smoke_commands(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-ENTERPRISE.md'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('--filter=Sprint90', $content);
        $this->assertStringContainsString('--filter=Sprint76', $content);
        $this->assertStringContainsString('--filter=Sprint88', $content);
        $this->assertStringContainsString('rentri:preflight', $content);
        $this->assertStringContainsString('rentri:monitor', $content);
    }

    public function test_ciclo_7_piano_marks_cycle_closed(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-7-PIANO.md'));

        $this->assertStringContainsString('✅ CHIUSO', $content);
        $this->assertStringContainsString('Sprint 90 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-ENTERPRISE.md', $content);
    }

    public function test_ciclo_7_audit_marks_p0_p1_p2_remediated(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-7-ENTERPRISE-AUDIT.md'));

        $this->assertStringContainsString('CHIUSO ✅', $content);
        $this->assertStringContainsString('P0 — bloccanti', $content);
        $this->assertStringContainsString('RentriXfirCoseTransmissionMapper', $content);
        $this->assertStringContainsString('RentriFirVidimaValidator', $content);
    }

    public function test_backlog_section_10_marks_ciclo_7_closed(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI_VERTICAL_BACKLOG.md'));

        $this->assertStringContainsString('Ciclo 7 — Enterprise RENTRI/FIR (sprint 76–90) ✅ CHIUSO', $content);
        $this->assertStringContainsString('Completato Sprint 90', $content);
        $this->assertStringContainsString('Gap post-ciclo 7', $content);
    }

    public function test_readme_links_ciclo_7_and_go_live_enterprise(): void
    {
        $content = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('Ciclo 7 — Enterprise RENTRI/FIR', $content);
        $this->assertStringContainsString('CHIUSO', $content);
        $this->assertStringContainsString('GO-LIVE-ENTERPRISE.md', $content);
        $this->assertStringContainsString('CICLO-7-PIANO.md', $content);
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
}
