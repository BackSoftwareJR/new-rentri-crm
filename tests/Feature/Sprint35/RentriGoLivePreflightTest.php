<?php

namespace Tests\Feature\Sprint35;

use App\Domain\Deploy\PreflightService;
use App\Models\RentriSetting;
use Illuminate\Support\Facades\Config;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriGoLivePreflightTest extends TestCase
{
    use SeedsRentriCertificate;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('framework/testing/preflight-s35');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->manifestPath = $dir.'/manifest.json';
        file_put_contents($this->manifestPath, '{"resources/css/app.css":{"file":"app.css"}}');
    }

    public function test_live_mode_requires_firma_certificate_when_firma_stub_disabled(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_firma_cert' && $c['status'] === 'fail',
        ));
    }

    public function test_live_mode_passes_with_mtls_and_firma_certificates(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);
        Config::set('app.debug', false);
        $this->seedRentriFirmaCertificate(['onboarding_step_completed' => 3]);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_firma_cert' && $c['status'] === 'ok',
        ));
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_operator' && $c['status'] === 'ok',
        ));
    }

    public function test_live_mode_fails_without_operator_data(): void
    {
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', true);
        RentriSetting::instance()->update([
            'num_iscr_sito'             => null,
            'cf_operatore'              => null,
            'onboarding_step_completed' => 0,
        ]);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_operator' && $c['status'] === 'fail',
        ));
    }

    public function test_production_warns_on_stub_flags(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', false);
        Config::set('services.rentri.api_stub', true);
        Config::set('services.rentri.firma_stub', true);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_stub' && $c['status'] === 'warn',
        ));
        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_firma_stub' && $c['status'] === 'warn',
        ));
    }
}
