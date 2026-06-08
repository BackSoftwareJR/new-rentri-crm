<?php

namespace Tests\Feature\Sprint124;

use App\Domain\Infrastructure\ApplicationHealthService;
use App\Models\Sito;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Round10OpsReadinessTest extends TestCase
{
    public function test_health_endpoint_returns_json(): void
    {
        $response = $this->get('/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'checked_at',
                'checks' => [
                    'database',
                    'redis',
                    'queue_workers',
                    'horizon',
                    'storage_writable',
                    'rentri_cert',
                    'scheduler',
                ],
            ]);
    }

    public function test_up_endpoint_checks_database(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_app_health_check_command_exits_zero_in_testing(): void
    {
        $exitCode = Artisan::call('app:health-check', ['--json' => true]);

        $this->assertSame(0, $exitCode);

        $output = Artisan::output();
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertSame('healthy', $decoded['status']);
    }

    public function test_cache_warm_command_runs_successfully(): void
    {
        Sito::query()->create([
            'nome'        => 'Impianto test',
            'is_active'   => true,
            'is_default'  => true,
        ]);

        $exitCode = Artisan::call('cache:warm');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Cache warm completato', Artisan::output());
    }

    public function test_scheduler_heartbeat_marks_health_check_ok_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        app(ApplicationHealthService::class)->recordSchedulerHeartbeat();

        $check = app(ApplicationHealthService::class)->checkSchedulerHeartbeat();

        $this->assertSame('ok', $check['status']);
    }

    public function test_stale_scheduler_heartbeat_is_degraded_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        Cache::put(
            ApplicationHealthService::SCHEDULER_HEARTBEAT_KEY,
            now()->subHours(30)->toIso8601String(),
            now()->addDay(),
        );

        $report = app(ApplicationHealthService::class)->diagnose();

        $this->assertSame('degraded', $report['status']);
        $this->assertSame('fail', $report['checks']['scheduler']['status']);
    }

    public function test_deploy_and_rollback_scripts_exist(): void
    {
        $this->assertFileExists(base_path('scripts/deploy.sh'));
        $this->assertFileExists(base_path('scripts/rollback.sh'));
        $this->assertTrue(is_executable(base_path('scripts/deploy.sh')));
        $this->assertTrue(is_executable(base_path('scripts/rollback.sh')));
    }

    public function test_deploy_script_contains_required_steps(): void
    {
        $content = file_get_contents(base_path('scripts/deploy.sh'));

        $this->assertStringContainsString('php artisan down', $content);
        $this->assertStringContainsString('composer install --no-dev', $content);
        $this->assertStringContainsString('npm run build', $content);
        $this->assertStringContainsString('php artisan migrate --force', $content);
        $this->assertStringContainsString('SitoSeeder', $content);
        $this->assertStringContainsString('php artisan config:cache', $content);
        $this->assertStringContainsString('php artisan cache:warm', $content);
        $this->assertStringContainsString('php artisan horizon:terminate', $content);
        $this->assertStringContainsString('php artisan up', $content);
        $this->assertStringContainsString('php artisan rentri:preflight', $content);
    }
}
