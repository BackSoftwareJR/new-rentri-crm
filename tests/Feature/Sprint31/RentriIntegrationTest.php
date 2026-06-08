<?php

namespace Tests\Feature\Sprint31;

use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

/**
 * Test integrazione opzionale con API RENTRI demo reali.
 *
 * Eseguire solo con credenziali valide (certificato fuori repo):
 * RENTRI_API_STUB=false RENTRI_INTEGRATION_TEST=true \
 *   RENTRI_SANDBOX_CERT_PATH=/path/to/sandbox.p12 \
 *   RENTRI_SANDBOX_CERT_PASSWORD=secret \
 *   php artisan test --filter=RentriIntegrationTest
 */
class RentriIntegrationTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();

        if (! config('services.rentri.integration_test')) {
            $this->markTestSkipped('Impostare RENTRI_INTEGRATION_TEST=true per test integrazione live.');
        }

        $certPath = config('services.rentri.sandbox_cert_path');
        if (blank($certPath) || ! is_readable($certPath)) {
            $this->markTestSkipped('Impostare RENTRI_SANDBOX_CERT_PATH con percorso PKCS#12 sandbox leggibile.');
        }

        Config::set('services.rentri.api_stub', false);
        $this->seedRentriCertificateFromSandboxPath();
    }

    public function test_live_health_check_against_demo_api(): void
    {
        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status'] ?? null);
        $this->assertSame('live', $result['api_mode'] ?? null);
    }

    public function test_live_fetch_codifiche_cer_against_demo_api(): void
    {
        $result = app(RentriApiClientInterface::class)->fetchCodificheCer();

        $this->assertIsArray($result);
        $this->assertTrue(
            isset($result['items']) || isset($result['data']),
            'Risposta codifiche CER attesa con items/data',
        );
    }

    public function test_live_fetch_fir_blocchi_against_demo_api(): void
    {
        $result = app(RentriApiClientInterface::class)->fetchFirBlocchi();

        $this->assertIsArray($result);
        $this->assertTrue(
            isset($result['items']) || isset($result['data']) || isset($result['elementi']),
            'Risposta blocchi FIR attesa con items/data/elementi',
        );
    }
}
