<?php

namespace Tests\Feature\Sprint44;

use App\Domain\Deploy\DemoPreflightService;
use App\Domain\Demo\DemoSeedService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoPreflightTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('framework/testing/demo-preflight');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->manifestPath = $dir.'/manifest.json';
        file_put_contents($this->manifestPath, '{"resources/css/app.css":{"file":"app.css"}}');
    }

    public function test_demo_preflight_passes_with_demo_mode_and_seed(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('app.env', 'demo');
        Config::set('services.rentri.api_stub', true);

        Artisan::call('rentri:demo-seed');

        $result = app(DemoPreflightService::class)->run($this->manifestPath, requireSeed: true);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_mode' && $c['status'] === 'ok',
        ));
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_seed' && $c['status'] === 'ok',
        ));
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'framework_health' && $c['status'] === 'ok',
        ));
    }

    public function test_demo_preflight_fails_without_demo_mode(): void
    {
        Config::set('demo.enabled', false);

        $result = app(DemoPreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_mode' && $c['status'] === 'fail',
        ));
    }

    public function test_demo_preflight_fails_on_production_env(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('app.env', 'production');

        $result = app(DemoPreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_env' && $c['status'] === 'fail',
        ));
    }

    public function test_demo_preflight_fails_when_sandbox_not_forced(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', false);
        Config::set('app.env', 'demo');

        $result = app(DemoPreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_sandbox' && $c['status'] === 'fail',
        ));
    }

    public function test_demo_preflight_warns_without_seed_unless_required(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('app.env', 'demo');
        Config::set('services.rentri.api_stub', true);

        $this->assertFalse(app(DemoSeedService::class)->isSeeded());

        $result = app(DemoPreflightService::class)->run($this->manifestPath, requireSeed: false);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_seed' && $c['status'] === 'warn',
        ));
    }

    public function test_demo_preflight_fails_without_seed_when_required(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('app.env', 'demo');

        $result = app(DemoPreflightService::class)->run($this->manifestPath, requireSeed: true);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'demo_seed' && $c['status'] === 'fail',
        ));
    }

    public function test_artisan_demo_preflight_exits_zero_when_passed(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('app.env', 'demo');
        Config::set('services.rentri.api_stub', true);

        Artisan::call('rentri:demo-seed');

        $service = $this->mock(DemoPreflightService::class);
        $service->shouldReceive('run')
            ->once()
            ->with(null, true)
            ->andReturn([
                'passed' => true,
                'checks' => [
                    ['name' => 'demo_mode', 'status' => 'ok', 'message' => 'OK'],
                ],
            ]);

        $exit = Artisan::call('rentri:preflight', ['--demo' => true, '--require-seed' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Preflight deploy demo', Artisan::output());
    }
}
