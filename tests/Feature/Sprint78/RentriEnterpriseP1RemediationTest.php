<?php

namespace Tests\Feature\Sprint78;

use App\Domain\Deploy\DemoPreflightService;
use App\Domain\Deploy\PreflightService;
use App\Domain\Rentri\RentriLiveModeService;
use App\Http\Livewire\Segreteria\Rentri;
use App\Models\FirBlocco;
use App\Models\RentriSetting;
use App\Models\RentriTransmissione;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriEnterpriseP1RemediationTest extends TestCase
{
    use SeedsRentriCertificate;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('framework/testing/sprint78-preflight');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->manifestPath = $dir.'/manifest.json';
        file_put_contents($this->manifestPath, '{"resources/css/app.css":{"file":"app.css"}}');
    }

    public function test_blocchi_sync_updates_progressivo_on_existing_block(): void
    {
        Config::set('services.rentri.api_stub', false);
        $this->seedRentriCertificate(['num_iscr_sito' => 'OP12345678901-PD00001']);

        FirBlocco::create([
            'codice_blocco'      => 'BLK-API-01',
            'num_iscr_sito'      => 'OP12345678901-PD00001',
            'progressivo_ultimo' => 2,
        ]);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response([
                'items' => [
                    [
                        'codice_blocco'      => 'BLK-API-01',
                        'num_iscr_sito'      => 'OP12345678901-PD00001',
                        'progressivo_ultimo' => 7,
                    ],
                ],
            ], 200),
        ]);

        $result = app(RentriFirBlocchiSyncInterface::class)->sync();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(7, FirBlocco::firstOrFail()->progressivo_ultimo);
    }

    public function test_preflight_reports_live_stub_check_when_runtime_live_enabled(): void
    {
        Config::set('services.rentri.api_stub', true);
        Config::set('app.debug', false);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $settings = $this->seedRentriFirmaCertificate([
            'ambiente'             => 'produzione',
            'piva'                 => '12345678903',
            'ragione_sociale'      => 'Impianto Test Srl',
            'last_health_status'   => ['status' => 'ok'],
            'last_health_check_at' => now(),
            'onboarding_step_completed' => 3,
        ]);

        app(RentriLiveModeService::class)->enable($settings, $user->id);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $stubCheck = collect($result['checks'])->firstWhere('name', 'rentri_stub');
        $this->assertSame('ok', $stubCheck['status']);
        $this->assertStringContainsString('live', strtolower($stubCheck['message']));
    }

    public function test_preflight_requires_certificate_when_runtime_live_despite_env_stub(): void
    {
        Config::set('services.rentri.api_stub', true);
        Config::set('services.rentri.firma_stub', true);
        Config::set('app.debug', false);

        RentriSetting::instance()->update([
            'live_mode_enabled_at' => now(),
            'cert_path_encrypted'  => null,
            'cert_password_encrypted' => null,
            'cert_scadenza'      => null,
        ]);

        $result = app(PreflightService::class)->run($this->manifestPath);

        $this->assertTrue(collect($result['checks'])->contains(
            fn ($c) => $c['name'] === 'rentri_cert' && $c['status'] === 'fail',
        ));
    }

    public function test_demo_preflight_uses_runtime_stub_when_env_live_but_offline(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.offline_no_http', true);
        Config::set('services.rentri.api_stub', false);
        RentriSetting::instance()->update(['live_mode_enabled_at' => now()]);

        $result = app(DemoPreflightService::class)->run($this->manifestPath);

        $stubCheck = collect($result['checks'])->firstWhere('name', 'rentri_stub');
        $this->assertSame('ok', $stubCheck['status']);
        $this->assertStringContainsString('offline', strtolower($stubCheck['message']));
    }

    public function test_rentri_trasmissione_message_uses_runtime_when_api_mode_missing(): void
    {
        Config::set('services.rentri.api_stub', true);
        RentriSetting::instance()->update(['live_mode_enabled_at' => now()]);

        $transmissione = RentriTransmissione::create([
            'periodo_da'    => now()->startOfMonth(),
            'periodo_a'     => now()->endOfMonth(),
            'payload_hash'  => hash('sha256', 'test-payload'),
            'esito'         => 'accettato',
            'trasmesso_at'  => now(),
            'response_json' => ['protocollo' => 'REG-001'],
        ]);

        $method = new ReflectionMethod(Rentri::class, 'trasmissioneSuccessMessage');
        $method->setAccessible(true);
        $message = $method->invoke(new Rentri, $transmissione, 3);

        $this->assertStringContainsString('RENTRI live', $message);
    }

    public function test_sprint_78_review_handoff_doc_exists(): void
    {
        $path = base_path('docs/SPRINT-78-REVIEW-HANDOFF.md');

        $this->assertFileExists($path);
        $this->assertStringContainsString('Sprint 79', file_get_contents($path));
        $this->assertStringContainsString('REVIEW ONLY', file_get_contents($path));
    }
}
