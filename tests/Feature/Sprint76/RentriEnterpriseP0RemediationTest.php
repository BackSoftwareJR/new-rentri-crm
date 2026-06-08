<?php

namespace Tests\Feature\Sprint76;

use App\Domain\Rentri\RentriLiveModeService;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\RentriSetting;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\RentriCertificateService;
use App\Services\Rentri\RentriFirQrPayloadBuilder;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriEnterpriseP0RemediationTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_runtime_api_mode_label_live_after_ui_enable(): void
    {
        Config::set('services.rentri.api_stub', true);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $settings = $this->seedRentriFirmaCertificate([
            'ambiente'             => 'produzione',
            'piva'                 => '12345678903',
            'ragione_sociale'      => 'Impianto Test Srl',
            'last_health_status'   => ['status' => 'ok'],
            'last_health_check_at' => now(),
        ]);

        app(RentriLiveModeService::class)->enable($settings, $user->id);

        $runtime = app(RentriRuntimeModeService::class);

        $this->assertSame('live', $runtime->apiModeLabel($settings->fresh()));
        $this->assertSame('RENTRI live', $runtime->apiModeDisplayLabel($settings->fresh()));
    }

    public function test_qr_payload_api_mode_uses_runtime_not_env_config(): void
    {
        Config::set('services.rentri.api_stub', true);
        $settings = RentriSetting::instance();
        $settings->update(['live_mode_enabled_at' => now()]);

        $payload = app(RentriFirQrPayloadBuilder::class)->build(
            ['protocollo' => 'PROT-1'],
            'FIR-001',
            'BLK-A',
            1,
            'SITE-001',
            'tx-1',
            $settings->fresh(),
        );

        $this->assertSame('live', $payload['api_mode']);
    }

    public function test_health_check_works_without_cert_in_offline_stub_mode(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('services.rentri.api_stub', false);

        RentriSetting::instance()->update([
            'cert_path_encrypted'     => null,
            'cert_password_encrypted' => null,
            'cert_scadenza'           => null,
        ]);

        $result = app(RentriApiClientInterface::class)->healthCheck();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('stub', $result['api_mode']);
    }

    public function test_sign_request_for_mode_uses_offline_headers_without_cert(): void
    {
        $service = app(RentriCertificateService::class);
        $settings = RentriSetting::instance();

        $headers = $service->signRequestForMode($settings, 'GET', '/health', [], true);

        $this->assertSame('stub:offline', $headers['X-RENTRI-Signature']);
        $this->assertSame('offline-stub', $headers['X-RENTRI-Cert-Id']);
    }

    public function test_trasporto_show_api_stub_flag_follows_runtime_mode(): void
    {
        Config::set('services.rentri.api_stub', true);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $settings = $this->seedRentriFirmaCertificate([
            'ambiente'             => 'produzione',
            'piva'                 => '12345678903',
            'ragione_sociale'      => 'Impianto Test Srl',
            'last_health_status'   => ['status' => 'ok'],
            'last_health_check_at' => now(),
        ]);

        app(RentriLiveModeService::class)->enable($settings, $user->id);

        $trasporto = \App\Models\Trasporto::query()->first();

        if ($trasporto === null) {
            $this->markTestSkipped('Nessun trasporto seedato per verifica UI apiStub.');
        }

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertDontSee('invio via API stub', false);
    }

    public function test_ciclo_7_enterprise_audit_doc_exists(): void
    {
        $path = base_path('docs/CICLO-7-ENTERPRISE-AUDIT.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('P0', $content);
        $this->assertStringContainsString('vidimazione', $content);
        $this->assertStringContainsString('xFIR', $content);
    }

    public function test_sprint_76_review_handoff_doc_exists(): void
    {
        $path = base_path('docs/SPRINT-76-REVIEW-HANDOFF.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('Sprint 77', file_get_contents($path));
        $this->assertStringContainsString('REVIEW ONLY', file_get_contents($path));
    }
}
