<?php

namespace Tests\Feature\Sprint111;

use App\Domain\Rentri\RentriProductionCertValidationService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriProductionCertValidationTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_production_validation_blocks_when_not_production_env(): void
    {
        Config::set('services.rentri.env', 'sandbox');
        $this->seedRentriFirmaCertificate([
            'ambiente' => 'produzione',
            'onboarding_step_completed' => 3,
        ]);
        Config::set('services.rentri.api_stub', false);

        $result = app(RentriProductionCertValidationService::class)->run(
            app(RentriApiClientInterface::class),
        );

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'prereq_rentri_env' && $s['status'] === 'fail',
        ));
    }

    public function test_production_validation_blocks_demoapi_in_production_mode(): void
    {
        Config::set('services.rentri.env', 'production');
        Config::set('services.rentri.base_url_production', 'https://demoapi.rentri.gov.it');
        $this->seedRentriFirmaCertificate([
            'ambiente' => 'produzione',
            'onboarding_step_completed' => 3,
        ]);
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);

        $service = app(RentriProductionCertValidationService::class);

        $this->assertFalse($service->isProductionHostOnly());
        $result = $service->run(app(RentriApiClientInterface::class));

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'prereq_no_demoapi' && $s['status'] === 'fail',
        ));
    }

    public function test_production_validation_runs_health_and_codifiche_against_api_rentri(): void
    {
        Config::set('services.rentri.env', 'production');
        Config::set('services.rentri.base_url_production', 'https://api.rentri.gov.it');
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);

        $settings = $this->seedRentriFirmaCertificate([
            'ambiente' => 'produzione',
            'onboarding_step_completed' => 3,
            'live_mode_enabled_at' => now(),
        ]);

        Http::fake([
            'api.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
            'api.rentri.gov.it/codifiche/v1.0/cer' => Http::response([
                'items' => [
                    ['codice' => '16.01.04', 'descrizione' => 'Prod test', 'pericoloso' => false, 'attivo' => true],
                ],
            ], 200),
        ]);

        $result = app(RentriProductionCertValidationService::class)->run(
            app(RentriApiClientInterface::class),
            $settings,
        );

        $this->assertSame('ok', $result['overall']);
        $this->assertSame(1, $result['codifiche_count']);
        $this->assertTrue(collect($result['steps'])->contains(
            fn (array $s) => $s['key'] === 'health' && $s['status'] === 'ok',
        ));
    }

    public function test_rentri_settings_shows_production_cert_validation_section(): void
    {
        $this->seedRentriCertificate(['onboarding_step_completed' => 3]);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('Validazione certificato produzione')
            ->assertSee('Esegui validazione certificato produzione')
            ->assertSee('api.rentri.gov.it');
    }

    public function test_rentri_settings_run_production_validation_populates_steps(): void
    {
        Config::set('services.rentri.env', 'production');
        Config::set('services.rentri.base_url_production', 'https://api.rentri.gov.it');
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);

        $this->seedRentriFirmaCertificate([
            'ambiente' => 'produzione',
            'onboarding_step_completed' => 3,
            'live_mode_enabled_at' => now(),
        ]);

        Http::fake([
            'api.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
            'api.rentri.gov.it/codifiche/v1.0/cer' => Http::response(['items' => [['codice' => '16.01.04']]], 200),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('runProductionCertValidation')
            ->assertSet('productionCertValidationResult.overall', 'ok')
            ->assertSet('lastCodificheCount', 1);
    }

    public function test_validazione_cert_produzione_rentri_doc_exists(): void
    {
        $path = base_path('docs/VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('RENTRI_PRODUCTION_INTEGRATION_TEST', $content);
        $this->assertStringContainsString('api.rentri.gov.it', $content);
    }

    public function test_production_integration_test_requires_env_gate_in_source(): void
    {
        $source = file_get_contents(base_path('tests/Feature/Sprint111/RentriProductionIntegrationTest.php'));

        $this->assertStringContainsString('RENTRI_PRODUCTION_INTEGRATION_TEST', $source);
        $this->assertStringContainsString('RENTRI_PRODUCTION_CERT_PATH', $source);
        $this->assertStringContainsString('markTestSkipped', $source);
    }

    public function test_ciclo_10_piano_documents_sprint_111(): void
    {
        $content = file_get_contents(base_path('docs/CICLO-10-PIANO.md'));

        $this->assertFileExists(base_path('docs/CICLO-10-PIANO.md'));
        $this->assertStringContainsString('RentriProductionCertValidationService', $content);
        $this->assertStringContainsString('111', $content);
    }
}
