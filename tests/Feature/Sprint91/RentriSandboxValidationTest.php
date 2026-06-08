<?php

namespace Tests\Feature\Sprint91;

use App\Domain\Deploy\PreflightService;
use App\Domain\Rentri\RentriSandboxValidationService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriSandboxValidationTest extends TestCase
{
    use SeedsRentriCertificate;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('framework/testing/preflight-s91');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->manifestPath = $dir.'/manifest.json';
        file_put_contents($this->manifestPath, '{"resources/css/app.css":{"file":"app.css"}}');
    }

    public function test_sandbox_validation_service_blocks_when_api_stub(): void
    {
        Config::set('services.rentri.api_stub', true);
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);

        $result = app(RentriSandboxValidationService::class)->run(
            app(RentriApiClientInterface::class),
        );

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'prereq_api_mode' && $s['status'] === 'fail',
        ));
    }

    public function test_sandbox_validation_service_runs_health_and_codifiche_in_live_mode(): void
    {
        Config::set('services.rentri.api_stub', false);
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
            'demoapi.rentri.gov.it/codifiche/v1.0/cer' => Http::response([
                'items' => [
                    ['codice' => '16.01.04', 'descrizione' => 'Test', 'pericoloso' => false, 'attivo' => true],
                    ['codice' => '17.04.05', 'descrizione' => 'Test 2', 'pericoloso' => true, 'attivo' => true],
                ],
            ], 200),
        ]);

        $result = app(RentriSandboxValidationService::class)->run(
            app(RentriApiClientInterface::class),
        );

        $this->assertSame('ok', $result['overall']);
        $this->assertSame(2, $result['codifiche_count']);
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'health' && $s['status'] === 'ok',
        ));
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'vidima_dry_run' && $s['status'] === 'info',
        ));
    }

    public function test_rentri_settings_shows_sandbox_validation_section(): void
    {
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('Validazione reale sandbox MASE')
            ->assertSee('Esegui test reale MASE')
            ->assertSee('demoapi.rentri.gov.it');
    }

    public function test_rentri_settings_run_sandbox_validation_populates_steps(): void
    {
        Config::set('services.rentri.api_stub', false);
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
            'demoapi.rentri.gov.it/codifiche/v1.0/cer' => Http::response(['items' => [['codice' => '16.01.04']]], 200),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('runSandboxValidation')
            ->assertSet('sandboxValidationResult.overall', 'ok')
            ->assertSet('lastCodificheCount', 1);
    }

    public function test_preflight_fails_when_sandbox_cert_path_not_readable(): void
    {
        Config::set('services.rentri.sandbox_cert_path', '/tmp/rentri-missing-sandbox-cert-s91.p12');

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertFalse($result['passed']);
        $this->assertTrue(collect($result['checks'])->contains(
            fn (array $c) => $c['name'] === 'rentri_sandbox_cert' && $c['status'] === 'fail',
        ));
    }

    public function test_preflight_passes_when_sandbox_cert_path_readable(): void
    {
        $certFile = storage_path('framework/testing/sandbox-cert-s91.p12');
        file_put_contents($certFile, 'fake-pkcs12-for-preflight');
        Config::set('services.rentri.sandbox_cert_path', $certFile);
        Config::set('app.debug', false);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue(collect($result['checks'])->contains(
            fn (array $c) => $c['name'] === 'rentri_sandbox_cert' && $c['status'] === 'ok',
        ));
    }

    public function test_validazione_sandbox_mase_doc_exists(): void
    {
        $path = base_path('docs/VALIDAZIONE-SANDBOX-MASE.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_PATH', $content);
        $this->assertStringContainsString('RENTRI_INTEGRATION_TEST', $content);
        $this->assertStringContainsString('vidima', $content);
    }

    public function test_integration_test_requires_both_env_flags_in_source(): void
    {
        $source = file_get_contents(base_path('tests/Feature/Sprint31/RentriIntegrationTest.php'));

        $this->assertStringContainsString('RENTRI_INTEGRATION_TEST', $source);
        $this->assertStringContainsString('RENTRI_SANDBOX_CERT_PATH', $source);
        $this->assertStringContainsString('markTestSkipped', $source);
        $this->assertStringContainsString('seedRentriCertificateFromSandboxPath', $source);
    }
}
