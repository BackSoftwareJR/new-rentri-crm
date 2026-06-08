<?php

namespace Tests\Feature\Sprint31;

use App\Domain\Rentri\RentriConnectionStatusService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriProductionSettingsTest extends TestCase
{
    use SeedsRentriCertificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRentriCertificate();
    }

    public function test_settings_page_shows_connection_status_and_stub_badge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('stub sandbox')
            ->assertSee('Modalità stub');
    }

    public function test_connection_status_service_detects_live_mode(): void
    {
        Config::set('services.rentri.api_stub', false);

        $status = app(RentriConnectionStatusService::class)->resolve();

        $this->assertSame('live', $status['api_mode']);
        $this->assertSame(RentriConnectionStatusService::STATE_NOT_CONFIGURED, $status['state']);
    }

    public function test_live_test_connection_calls_blocchi_and_codifiche(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response(['items' => []], 200),
            'demoapi.rentri.gov.it/codifiche/v1.0/cer' => Http::response([
                'items' => [
                    ['codice' => '16.01.04', 'descrizione' => 'Test', 'pericoloso' => false, 'attivo' => true],
                ],
            ], 200),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('testConnection')
            ->assertHasNoErrors()
            ->assertSet('lastCodificheCount', 1);

        // health (blocchi FIR) + fetch CER + inline sync + RentriInitialSyncJob (CER + FIR blocchi)
        Http::assertSentCount(5);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'vidimazione-formulari'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'codifiche/v1.0/cer'));
    }

    public function test_live_api_translates_401_error_in_italian(): void
    {
        Config::set('services.rentri.api_stub', false);

        Http::fake([
            'demoapi.rentri.gov.it/vidimazione-formulari/v1.0*' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $this->expectException(\App\Services\Rentri\Exceptions\RentriApiException::class);
        $this->expectExceptionMessage('Autenticazione RENTRI fallita');

        app(RentriApiClientInterface::class)->healthCheck();
    }
}
