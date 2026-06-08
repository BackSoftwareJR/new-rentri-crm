<?php

namespace Tests\Feature\Sprint110;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class Cycle9ClosureGoLiveTest extends TestCase
{
    public function test_go_live_produzione_consolidates_cycle_9_deliverables(): void
    {
        $path = base_path('docs/GO-LIVE-PRODUZIONE.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('MudTelematicoEndpoints', $content);
        $this->assertStringContainsString('StripeProductionPreflightService', $content);
        $this->assertStringContainsString('OwaspExternalPrepService', $content);
        $this->assertStringContainsString('WafDeploymentPreflightService', $content);
        $this->assertStringContainsString('RentriProductionSwitchService', $content);
        $this->assertStringContainsString('HaBackupPreflightService', $content);
        $this->assertStringContainsString('BusinessKpiDashboardService', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 9', $content);
    }

    public function test_go_live_produzione_documents_smoke_commands(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-PRODUZIONE.md'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('--filter=Sprint110', $content);
        $this->assertStringContainsString('--filter=Sprint109', $content);
        $this->assertStringContainsString('--filter=Sprint106', $content);
        $this->assertStringContainsString('rentri:preflight', $content);
        $this->assertStringContainsString('rentri:production-switch-check', $content);
        $this->assertStringContainsString('rentri:monitor', $content);
    }

    public function test_ciclo_9_piano_marks_cycle_closed(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-9-PIANO.md'));

        $this->assertStringContainsString('✅ CHIUSO', $content);
        $this->assertStringContainsString('Sprint 110 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-PRODUZIONE.md', $content);
    }

    public function test_backlog_section_12_marks_ciclo_9_closed(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI_VERTICAL_BACKLOG.md'));

        $this->assertStringContainsString('Ciclo 9 — Produzione e gap infra (sprint 101–110) ✅ CHIUSO', $content);
        $this->assertStringContainsString('Completato Sprint 110', $content);
        $this->assertStringContainsString('GO-LIVE-PRODUZIONE.md', $content);
    }

    public function test_readme_links_ciclo_9_and_go_live_produzione(): void
    {
        $content = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('Ciclo 9 — Produzione e gap infra', $content);
        $this->assertStringContainsString('CHIUSO', $content);
        $this->assertStringContainsString('GO-LIVE-PRODUZIONE.md', $content);
        $this->assertStringContainsString('CICLO-9-PIANO.md', $content);
    }

    public function test_ciclo_10_piano_stub_exists(): void
    {
        $path = base_path('docs/CICLO-10-PIANO-STUB.md');

        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('111', $content);
        $this->assertStringContainsString('120', $content);
        $this->assertStringContainsString('RENTRI cert produzione', $content);
    }

    public function test_rentri_production_switch_check_dry_run_outputs_report(): void
    {
        Config::set('services.rentri.env', 'sandbox');
        Config::set('services.rentri.api_stub', true);

        $exitCode = Artisan::call('rentri:production-switch-check', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Checklist unificata', $output);
        $this->assertStringContainsString('RENTRI-PRODUCTION-SWITCH-RUNBOOK.md', $output);
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
