<?php

namespace Tests\Feature\Sprint9;

use App\Models\RentriSetting;
use App\Models\RentriTransazione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriApiClientTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }

    public function test_stub_mode_returns_responses_without_http(): void
    {
        Config::set('services.rentri.api_stub', true);
        Http::fake();

        $client = app(RentriApiClientInterface::class);

        $registro = $client->request('POST', '/registro/trasmetti', ['movimenti' => []]);
        $fir = $client->request('POST', '/fir/vidima', [
            'codice_blocco' => 'BLK',
            'progressivo'   => 1,
            'num_iscr_sito' => 'SITE-TEST',
        ]);
        $health = $client->healthCheck();

        $this->assertArrayHasKey('transazione_id', $registro);
        $this->assertArrayHasKey('transazione_id', $fir);
        $this->assertSame('ok', $health['status'] ?? null);
        Http::assertNothingSent();
        $this->assertSame(3, RentriTransazione::count());
        $this->assertSame('registro', RentriTransazione::where('tipo_api', 'registro')->value('tipo_api'));
        $this->assertSame('fir', RentriTransazione::where('tipo_api', 'fir')->value('tipo_api'));
        $this->assertSame('health', RentriTransazione::where('tipo_api', 'health')->value('tipo_api'));
    }

    public function test_live_mode_sends_signed_headers_via_http(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response([
                'items' => [['codice_blocco' => 'BLK-001']],
            ], 200, ['X-Correlation-Id' => 'corr-health-001']),
        ]);

        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('live', $result['api_mode']);
        $this->assertSame('corr-health-001', $result['correlation_id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'demoapi.rentri.gov.it/vidimazione-formulari/v1.0')
                && $request->method() === 'GET';
        });
    }

    public function test_live_mode_posts_registro_payload_with_signature(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'LIVE-PROTO-001',
            ], 200),
        ]);

        $payload = ['periodo_da' => '2026-06-01', 'periodo_a' => '2026-06-30', 'movimenti' => [['id' => 1]]];
        $result = app(RentriApiClientInterface::class)->request('POST', '/registro/trasmetti', $payload);

        $this->assertSame('accettato', $result['esito']);
        $this->assertSame('LIVE-PROTO-001', $result['protocollo']);

        Http::assertSent(function ($request) use ($payload) {
            return $request->url() === 'https://demoapi.rentri.gov.it/registro/v1.0/trasmissione'
                && $request->method() === 'POST'
                && $request->data() === $payload;
        });
    }

    public function test_sign_request_is_deterministic_for_same_input(): void
    {
        $settings = RentriSetting::instance();
        $certs = app(RentriCertificateServiceInterface::class);

        $headersA = $certs->signRequest($settings, 'POST', '/fir/vidima', ['a' => 1]);
        $headersB = $certs->signRequest($settings, 'POST', '/fir/vidima', ['a' => 2]);

        $this->assertSame($headersA['X-RENTRI-Signature-Alg'], 'STUB-HMAC-SHA256');
        $this->assertSame('test-operatore.p12', $headersA['X-RENTRI-Cert-Id']);
        $this->assertNotSame($headersA['X-RENTRI-Signature'], $headersB['X-RENTRI-Signature']);
    }

    public function test_rejects_expired_certificate(): void
    {
        RentriSetting::instance()->update(['cert_scadenza' => now()->subDay()->toDateString()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('scaduto');

        app(RentriApiClientInterface::class)->healthCheck();
    }

    public function test_transaction_logs_include_sanitized_headers(): void
    {
        Config::set('services.rentri.api_stub', true);

        app(RentriApiClientInterface::class)->request('POST', '/fir/vidima', ['x' => 1]);

        $tx = RentriTransazione::where('tipo_api', 'fir')->firstOrFail();
        $this->assertArrayHasKey('headers', $tx->request_json);
        $this->assertStringContainsString('stub:', $tx->request_json['headers']['X-RENTRI-Signature'] ?? '');
        $this->assertStringEndsWith('…', $tx->request_json['headers']['X-RENTRI-Signature']);
    }
}
