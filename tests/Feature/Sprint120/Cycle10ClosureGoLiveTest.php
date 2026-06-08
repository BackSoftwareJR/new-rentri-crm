<?php

namespace Tests\Feature\Sprint120;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class Cycle10ClosureGoLiveTest extends TestCase
{
    public function test_go_live_cert_produzione_consolidates_cycle_10_deliverables(): void
    {
        $path = base_path('docs/GO-LIVE-CERT-PRODUZIONE.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('RentriProductionCertValidationService', $content);
        $this->assertStringContainsString('RentriSlaAlertService', $content);
        $this->assertStringContainsString('PenTestRemediationService', $content);
        $this->assertStringContainsString('OperatoreMobileApiService', $content);
        $this->assertStringContainsString('TrasportoGpsProductionSwitchService', $content);
        $this->assertStringContainsString('StripeProductionSwitchService', $content);
        $this->assertStringContainsString('HaFailoverDrillService', $content);
        $this->assertStringContainsString('BusinessKpiAlertService', $content);
        $this->assertStringContainsString('Gap residui post-ciclo 10', $content);
    }

    public function test_go_live_cert_documents_smoke_commands(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-CERT-PRODUZIONE.md'));

        $this->assertStringContainsString('php artisan test', $content);
        $this->assertStringContainsString('--filter=Sprint120', $content);
        $this->assertStringContainsString('--filter=Sprint111', $content);
        $this->assertStringContainsString('rentri:sla-check', $content);
        $this->assertStringContainsString('trasporto:gps-switch-check', $content);
        $this->assertStringContainsString('stripe:production-switch-check', $content);
        $this->assertStringContainsString('ha:failover-drill', $content);
        $this->assertStringContainsString('kpi:business-check', $content);
    }

    public function test_ciclo_10_piano_marks_cycle_closed(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-10-PIANO.md'));

        $this->assertStringContainsString('✅ CHIUSO', $content);
        $this->assertStringContainsString('Sprint 120 — ✅ completato', $content);
        $this->assertStringContainsString('GO-LIVE-CERT-PRODUZIONE.md', $content);
    }

    public function test_backlog_section_13_marks_ciclo_10_closed(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI_VERTICAL_BACKLOG.md'));

        $this->assertStringContainsString('Ciclo 10 — RENTRI cert produzione (sprint 111–120) ✅ CHIUSO', $content);
        $this->assertStringContainsString('Completato Sprint 120', $content);
        $this->assertStringContainsString('GO-LIVE-CERT-PRODUZIONE.md', $content);
    }

    public function test_readme_links_ciclo_10_and_go_live_cert(): void
    {
        $content = file_get_contents(base_path('README.md'));

        $this->assertStringContainsString('Ciclo 10 — RENTRI cert produzione', $content);
        $this->assertStringContainsString('CHIUSO', $content);
        $this->assertStringContainsString('GO-LIVE-CERT-PRODUZIONE.md', $content);
        $this->assertStringContainsString('CICLO-10-PIANO.md', $content);
    }

    public function test_kpi_business_check_command_smoke(): void
    {
        $exitCode = Artisan::call('kpi:business-check', ['--json' => true]);

        $output = Artisan::output();
        $this->assertContains($exitCode, [0, 1]);
        $this->assertStringContainsString('"overall"', $output);
        $this->assertStringContainsString('"period_key"', $output);
    }

    public function test_gps_switch_check_dry_run_outputs_report(): void
    {
        Config::set('services.trasporto_gps.stub', true);

        $exitCode = Artisan::call('trasporto:gps-switch-check', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('GPS provider switch', $output);
        $this->assertStringContainsString('GPS-PROVIDER-PRODUZIONE-RUNBOOK.md', $output);
    }

    public function test_rentri_sla_check_json_smoke(): void
    {
        $exitCode = Artisan::call('rentri:sla-check', ['--json' => true]);

        $output = Artisan::output();
        $this->assertContains($exitCode, [0, 1]);
        $this->assertStringContainsString('"overall"', $output);
    }

    public function test_go_live_cert_documents_e2e_checklist(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-CERT-PRODUZIONE.md'));

        $this->assertStringContainsString('Checklist certificazione produzione E2E', $content);
        $this->assertStringContainsString('VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md', $content);
        $this->assertStringContainsString('847', $content);
    }
}
