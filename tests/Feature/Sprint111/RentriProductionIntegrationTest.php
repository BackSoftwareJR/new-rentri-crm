<?php

namespace Tests\Feature\Sprint111;

use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

/**
 * Test integrazione opzionale con API RENTRI produzione (api.rentri.gov.it).
 *
 * Eseguire solo con credenziali valide fuori repo:
 * RENTRI_ENV=production RENTRI_API_STUB=false RENTRI_FIRMA_STUB=false \
 *   RENTRI_PRODUCTION_INTEGRATION_TEST=true \
 *   RENTRI_PRODUCTION_CERT_PATH=/path/to/produzione.p12 \
 *   RENTRI_PRODUCTION_CERT_PASSWORD=secret \
 *   php artisan test --filter=RentriProductionIntegrationTest
 */
class RentriProductionIntegrationTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();

        if (! config('services.rentri.production_integration_test')) {
            $this->markTestSkipped('Impostare RENTRI_PRODUCTION_INTEGRATION_TEST=true per test integrazione produzione.');
        }

        $certPath = config('services.rentri.production_cert_path');
        if (blank($certPath) || ! is_readable($certPath)) {
            $this->markTestSkipped('Impostare RENTRI_PRODUCTION_CERT_PATH con percorso PKCS#12 produzione leggibile.');
        }

        Config::set('services.rentri.env', 'production');
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);
        Config::set('services.rentri.base_url_production', 'https://api.rentri.gov.it');
        $this->seedRentriCertificateFromProductionPath();
    }

    public function test_live_health_check_against_production_api(): void
    {
        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status'] ?? null);
        $this->assertSame('live', $result['api_mode'] ?? null);
    }

    public function test_live_fetch_codifiche_cer_against_production_api(): void
    {
        $result = app(RentriApiClientInterface::class)->fetchCodificheCer();

        $this->assertIsArray($result);
        $this->assertTrue(
            isset($result['items']) || isset($result['data']),
            'Risposta codifiche CER attesa con items/data',
        );
    }
}
