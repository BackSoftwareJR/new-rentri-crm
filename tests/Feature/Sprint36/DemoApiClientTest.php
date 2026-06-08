<?php

namespace Tests\Feature\Sprint36;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class DemoApiClientTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_demo_mode_forces_sandbox_url_even_with_produzione_setting(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', true);
        Config::set('demo.rentri.offline_no_http', false);
        Config::set('services.rentri.api_stub', false);

        $this->seedRentriCertificate();
        RentriSetting::instance()->update(['ambiente' => 'produzione']);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
        ]);

        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'demoapi.rentri.gov.it'));
    }

    public function test_demo_offline_no_http_never_sends_requests(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('services.rentri.api_stub', false);

        $this->seedRentriCertificate();

        Http::fake();

        $client = app(RentriApiClientInterface::class);

        $client->healthCheck();
        $client->request('POST', '/registro/trasmetti', ['movimenti' => []]);

        Http::assertNothingSent();
    }

    public function test_demo_mode_blocks_production_api_when_force_sandbox_disabled(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.force_sandbox_api', false);
        Config::set('demo.rentri.offline_no_http', false);
        Config::set('services.rentri.api_stub', false);

        $this->seedRentriCertificate();
        RentriSetting::instance()->update(['ambiente' => 'produzione']);

        Http::fake([
            'api.rentri.gov.it/*' => Http::response(['items' => []], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Modalità demo: chiamate API produzione MASE');

        app(RentriApiClientInterface::class)->healthCheck();
    }
}
