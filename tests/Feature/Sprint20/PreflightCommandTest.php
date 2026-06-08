<?php

namespace Tests\Feature\Sprint20;

use App\Domain\Deploy\PreflightService;
use App\Models\RentriSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class PreflightCommandTest extends TestCase
{
    use SeedsRentriCertificate;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('framework/testing/preflight');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->manifestPath = $dir.'/manifest.json';
        file_put_contents($this->manifestPath, '{"resources/css/app.css":{"file":"app.css"}}');
    }

    public function test_preflight_passes_with_db_and_manifest(): void
    {
        Config::set('app.debug', false);
        Config::set('services.rentri.api_stub', true);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'database' && $c['status'] === 'ok'));
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'vite_manifest' && $c['status'] === 'ok'));
    }

    public function test_preflight_fails_without_app_key(): void
    {
        Config::set('app.key', '');

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'app_key' && $c['status'] === 'fail'));
    }

    public function test_preflight_fails_without_vite_manifest(): void
    {
        $result = app(PreflightService::class)->run(storage_path('framework/testing/missing-manifest.json'));

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'vite_manifest' && $c['status'] === 'fail'));
    }

    public function test_preflight_fails_without_rentri_cert_when_live_mode(): void
    {
        Config::set('services.rentri.api_stub', false);
        RentriSetting::instance()->update([
            'cert_path_encrypted' => null,
            'cert_password_encrypted' => null,
        ]);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'rentri_cert' && $c['status'] === 'fail'));
    }

    public function test_preflight_passes_with_rentri_cert_in_live_mode(): void
    {
        Config::set('services.rentri.api_stub', false);
        $this->seedRentriCertificate();

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(fn ($c) => $c['name'] === 'rentri_cert' && $c['status'] === 'ok'));
    }

    public function test_artisan_command_exits_zero_when_passed(): void
    {
        Config::set('app.debug', false);
        Config::set('services.rentri.api_stub', true);

        $service = $this->mock(PreflightService::class);
        $service->shouldReceive('run')->once()->andReturn([
            'passed' => true,
            'checks' => [
                ['name' => 'database', 'status' => 'ok', 'message' => 'OK'],
            ],
        ]);

        $exit = Artisan::call('rentri:preflight');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('nessun errore bloccante', Artisan::output());
    }
}
