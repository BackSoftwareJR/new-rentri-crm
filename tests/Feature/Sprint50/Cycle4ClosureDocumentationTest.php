<?php

namespace Tests\Feature\Sprint50;

use App\Domain\Deploy\Cycle3MonitoringService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Cycle4ClosureDocumentationTest extends TestCase
{
    public function test_cycle_4_closure_documents_exist(): void
    {
        $this->assertFileExists(base_path('docs/UAT-FORMAZIONE-PALESTRA.md'));
        $this->assertFileExists(base_path('docs/RUNBOOK-POST-DEPLOY.md'));

        $uat = file_get_contents(base_path('docs/UAT-FORMAZIONE-PALESTRA.md'));
        $this->assertStringContainsString('Checklist firmabile', $uat);
        $this->assertStringContainsString('Preset multi-operatore', $uat);

        $runbook = file_get_contents(base_path('docs/RUNBOOK-POST-DEPLOY.md'));
        $this->assertStringContainsString('rentri:monitor', $runbook);
        $this->assertStringContainsString('dead-letter', $runbook);
        $this->assertStringContainsString('Escalation', $runbook);
    }

    public function test_ciclo_4_piano_marked_closed(): void
    {
        $piano = file_get_contents(base_path('docs/CICLO-4-PIANO.md'));
        $this->assertStringContainsString('CHIUSO', $piano);
        $this->assertStringContainsString('Sprint 50', $piano);
        $this->assertStringContainsString('Gap residui', $piano);
    }

    public function test_go_live_rentri_links_ciclo_4_checklist(): void
    {
        $goLive = file_get_contents(base_path('docs/GO-LIVE-RENTRI.md'));
        $this->assertStringContainsString('GO-LIVE-CICLO-4.md', $goLive);
        $this->assertStringContainsString('UAT-FORMAZIONE-PALESTRA.md', $goLive);
        $this->assertStringContainsString('RUNBOOK-POST-DEPLOY.md', $goLive);
        $this->assertStringContainsString('CHIUSO', $goLive);
    }

    public function test_go_live_ciclo_4_marked_closed(): void
    {
        $checklist = file_get_contents(base_path('docs/GO-LIVE-CICLO-4.md'));
        $this->assertStringContainsString('CHIUSO', $checklist);
        $this->assertStringContainsString('UAT-FORMAZIONE-PALESTRA.md', $checklist);
    }

    public function test_monitor_command_smoke_after_cycle_4_closure(): void
    {
        $this->mock(Cycle3MonitoringService::class)
            ->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'framework_health' => ['status' => 'ok', 'http_code' => 200, 'message' => 'OK'],
                'demo_mode'        => false,
                'app_env'          => 'testing',
                'rentri'           => [
                    'totale' => 0, 'completate' => 0, 'errori' => 0,
                    'in_corso' => 0, 'dead_letter' => 0, 'retry_pianificati' => 0,
                ],
                'alerts'           => [],
            ]);

        $exit = Artisan::call('rentri:monitor');

        $this->assertSame(0, $exit);
    }
}
