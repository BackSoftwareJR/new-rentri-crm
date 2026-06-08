<?php

namespace Tests\Feature\Sprint45;

use App\Domain\Deploy\Cycle3MonitoringService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RentriMonitorCommandTest extends TestCase
{
    public function test_monitor_command_exits_zero_when_no_alerts(): void
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
        $this->assertStringContainsString('Nessun alert attivo', Artisan::output());
    }

    public function test_monitor_command_exits_failure_on_critical_alert(): void
    {
        $this->mock(Cycle3MonitoringService::class)
            ->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'framework_health' => ['status' => 'ok', 'http_code' => 200, 'message' => 'OK'],
                'demo_mode'        => false,
                'app_env'          => 'testing',
                'rentri'           => [
                    'totale' => 1, 'completate' => 0, 'errori' => 0,
                    'in_corso' => 0, 'dead_letter' => 1, 'retry_pianificati' => 0,
                ],
                'alerts'           => [
                    ['level' => 'critical', 'code' => 'rentri_dead_letter', 'message' => '1 dead-letter'],
                ],
            ]);

        $exit = Artisan::call('rentri:monitor');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('rentri_dead_letter', Artisan::output());
    }

    public function test_monitor_json_output_includes_alerts(): void
    {
        $this->mock(Cycle3MonitoringService::class)
            ->shouldReceive('snapshot')
            ->once()
            ->andReturn([
                'framework_health' => ['status' => 'ok', 'http_code' => 200, 'message' => 'OK'],
                'demo_mode'        => true,
                'app_env'          => 'demo',
                'rentri'           => [
                    'totale' => 0, 'completate' => 0, 'errori' => 0,
                    'in_corso' => 0, 'dead_letter' => 0, 'retry_pianificati' => 0,
                ],
                'alerts'           => [],
            ]);

        Artisan::call('rentri:monitor', ['--json' => true]);

        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['demo_mode']);
        $this->assertArrayHasKey('rentri', $decoded);
    }
}
