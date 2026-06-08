<?php

namespace Tests\Feature\Sprint47;

use App\Domain\Demo\DemoModeSessionService;
use App\Domain\Demo\DemoSeedService;
use App\Http\Livewire\Segreteria\DemoModeToggle;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\RentriSetting;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class PalestraOperativaE2eTest extends TestCase
{
    use SeedsRentriCertificate;

    private const VID_TX = 'tx-palestra-e2e-47';

    private const REG_TX = 'tx-registro-palestra-47';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('demo.allow_session_toggle', true);
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', true);
        Config::set('services.rentri.fir_poll_max_attempts', 5);
        Config::set('services.rentri.fir_poll_interval_ms', 1);
        Config::set('services.rentri.registro_poll_max_attempts', 5);
        Config::set('services.rentri.registro_poll_interval_ms', 1);
    }

    public function test_palestra_flow_toggle_seed_vidima_sign_registro_with_http_fake(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DemoModeToggle::class)
            ->call('confirmActivate');

        $this->assertTrue(session(config('demo.session.key')));

        Artisan::call('rentri:demo-seed');

        $this->seedRentriCertificate([
            'num_iscr_sito'             => DemoSeedService::NUM_ISCR_SITO,
            'onboarding_step_completed' => 3,
        ]);

        RentriSetting::instance()->update([
            'note_operatore' => 'Sandbox formazione — certificato MASE demo',
        ]);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/'.DemoSeedService::BLOCCO_CODICE => Http::response([
                'transazione_id' => self::VID_TX,
            ], 202),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/'.self::VID_TX.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0/verifica/result*' => Http::response([
                'numero_fir'  => 'DEMO-SITE-001-DEMO-BLK-001-0001',
                'protocollo'  => 'FIR-PALESTRA-E2E',
                'progressivo' => 1,
                'qr_code'     => json_encode([
                    'v'          => '1.0',
                    'numero_fir' => 'DEMO-SITE-001-DEMO-BLK-001-0001',
                    'protocollo' => 'FIR-PALESTRA-E2E',
                ]),
            ], 200),
            'demoapi.rentri.gov.it/registro/v1.0/trasmissione' => Http::response([
                'transazione_id' => self::REG_TX,
            ], 202),
            'demoapi.rentri.gov.it/registro/v1.0/'.self::REG_TX.'/status' => Http::response([
                'stato' => 'COMPLETATA',
            ], 200),
            'demoapi.rentri.gov.it/registro/v1.0/verifica/result*' => Http::response([
                'esito'      => 'accettato',
                'protocollo' => 'RENTRI-PALESTRA-E2E',
            ], 200),
        ]);

        $trasporto = app(DemoSeedService::class)->demoTrasporto();
        $this->assertNotNull($trasporto);

        app(RentriFirServiceInterface::class)->vidima($trasporto);
        $fir = app(RentriFirSigningServiceInterface::class)->sign($trasporto->fresh()->firCollegato);
        $this->assertNotNull($fir->vidimato_at);

        $registry = app(RentriRegistryServiceInterface::class);
        $periodoDa = Carbon::now()->subMonth()->startOfMonth();
        $periodoA = Carbon::now()->endOfMonth();
        $payload = $registry->buildTransmissionPayload($periodoDa, $periodoA);
        $transmissione = $registry->transmit($payload);

        $this->assertSame('accettato', $transmissione->fresh()->esito);
        $this->assertTrue(app(DemoSeedService::class)->demoMovimento()?->fresh()->rentri_trasmesso ?? false);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'demoapi.rentri.gov.it'));

        $steps = app(DemoSeedService::class)->walkthroughSteps();
        $this->assertTrue(collect($steps)->first()['done']);
        $this->assertTrue(collect($steps)->contains(fn ($s) => str_contains($s['label'], 'Certificato') && $s['done']));
    }

    public function test_rentri_settings_shows_cert_expiry_and_saves_note_in_demo(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $this->seedRentriCertificate(['cert_scadenza' => now()->addMonths(2)->toDateString()]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->set('note_operatore', 'Nota test palestra')
            ->call('saveNoteOperatore')
            ->assertHasNoErrors();

        $this->assertSame('Nota test palestra', RentriSetting::instance()->fresh()->note_operatore);
    }
}
