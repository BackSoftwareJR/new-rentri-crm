<?php

namespace Tests\Feature\Sprint7;

use App\Domain\Rentri\RentriOnboardingService;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RentriOnboardingServiceTest extends TestCase
{
    public function test_save_operator_data_advances_step(): void
    {
        $service = app(RentriOnboardingService::class);

        $settings = $service->saveOperatorData([
            'ambiente'        => 'sandbox',
            'cf'              => 'RSSMRA80A01H501Z',
            'piva'            => '98765432109',
            'ragione_sociale' => 'Operatore Test',
            'num_iscr_sito'   => 'SITE-SVC-001',
        ]);

        $this->assertSame('SITE-SVC-001', $settings->num_iscr_sito);
        $this->assertSame(1, $settings->onboarding_step_completed);
        $this->assertSame(2, $service->currentStep($settings));
    }

    public function test_save_certificate_and_health_check_complete_onboarding(): void
    {
        $onboarding = app(RentriOnboardingService::class);
        $certificates = app(RentriCertificateServiceInterface::class);
        $apiClient = app(RentriApiClientInterface::class);

        $onboarding->saveOperatorData([
            'ambiente'        => 'sandbox',
            'cf'              => 'RSSMRA80A01H501Z',
            'piva'            => '11111111111',
            'ragione_sociale' => 'Cert Test',
            'num_iscr_sito'   => 'SITE-CERT-001',
        ]);

        $file = UploadedFile::fake()->create('cert.p12', 100);
        $settings = $onboarding->saveCertificate($file, 'pass1234', $certificates);

        $this->assertTrue($certificates->validate($settings));
        $this->assertSame(2, $settings->onboarding_step_completed);

        $settings = $onboarding->runHealthCheck($apiClient);

        $this->assertTrue($onboarding->isComplete($settings));
        $this->assertSame('ok', $settings->last_health_status['status'] ?? null);
        $this->assertDatabaseHas('rentri_transazioni', ['tipo_api' => 'health']);
    }

    public function test_health_check_requires_certificate(): void
    {
        RentriSetting::instance()->update([
            'num_iscr_sito'             => 'SITE-NOCERT',
            'onboarding_step_completed' => 1,
            'cert_path_encrypted'       => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Caricare il certificato');

        app(RentriOnboardingService::class)->runHealthCheck(app(RentriApiClientInterface::class));
    }
}
