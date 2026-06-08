<?php

namespace Tests\Feature\Sprint100;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Cycle8ClosureGoLiveTest extends TestCase
{
    public function test_go_live_operativo_unified_env_checklist(): void
    {
        $path = base_path('docs/GO-LIVE-OPERATIVO.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('NOTIFICATIONS_LIVE', $content);
        $this->assertStringContainsString('MUD_TELEMATICO_STUB', $content);
        $this->assertStringContainsString('ECOMMERCE_PAYMENT_STUB', $content);
        $this->assertStringContainsString('TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA', $content);
        $this->assertStringContainsString('TRASPORTO_GPS_STUB', $content);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_PATH', $content);
        $this->assertStringContainsString('rentri-sandbox-integration.yml', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 8', $content);
    }

    public function test_go_live_operativo_documents_smoke_commands(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-OPERATIVO.md'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('--filter=Sprint100', $content);
        $this->assertStringContainsString('--filter=Sprint99', $content);
        $this->assertStringContainsString('--filter=Sprint91', $content);
        $this->assertStringContainsString('rentri:preflight', $content);
        $this->assertStringContainsString('rentri:monitor', $content);
    }

    public function test_ciclo_8_piano_marks_cycle_closed(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-8-PIANO.md'));

        $this->assertStringContainsString('✅ CHIUSO', $content);
        $this->assertStringContainsString('Sprint 100 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-OPERATIVO.md', $content);
    }

    public function test_backlog_section_11_marks_ciclo_8_closed(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI_VERTICAL_BACKLOG.md'));

        $this->assertStringContainsString('Ciclo 8 — Validazione operativa reale (sprint 91–100) ✅ CHIUSO', $content);
        $this->assertStringContainsString('Completato Sprint 100', $content);
        $this->assertStringContainsString('GO-LIVE-OPERATIVO.md', $content);
    }

    public function test_readme_links_ciclo_8_and_go_live_operativo(): void
    {
        $content = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('Ciclo 8 — Validazione operativa reale', $content);
        $this->assertStringContainsString('CHIUSO', $content);
        $this->assertStringContainsString('GO-LIVE-OPERATIVO.md', $content);
        $this->assertStringContainsString('CICLO-8-PIANO.md', $content);
    }

    public function test_go_live_enterprise_cross_links_operativo(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-ENTERPRISE.md'));

        $this->assertStringContainsString('GO-LIVE-OPERATIVO.md', $content);
        $this->assertStringContainsString('Ciclo 8', $content);
    }

    public function test_ciclo_9_piano_stub_exists(): void
    {
        $path = base_path('docs/CICLO-9-PIANO-STUB.md');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('101', $content);
        $this->assertStringContainsString('110', $content);
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
