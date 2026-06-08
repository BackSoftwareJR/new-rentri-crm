<?php

namespace Tests\Feature\Sprint98;

use App\Domain\Trasporti\TrasportoGpsRuntimeModeService;
use App\Domain\Trasporti\TrasportoGpsTrackingService;
use App\Domain\Trasporti\TrasportoTrackingPrepService;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class TrasportoGpsTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.trasporto_gps.stub', true);
    }

    public function test_gps_runtime_defaults_to_stub(): void
    {
        $runtime = app(TrasportoGpsRuntimeModeService::class);

        $this->assertTrue($runtime->isStub());
        $this->assertSame('GPS stub', $runtime->modeDisplayLabel());
    }

    public function test_gps_stub_poll_persists_last_position(): void
    {
        $trasporto = $this->inTransitoTrasporto();

        $position = app(TrasportoGpsTrackingService::class)->pollPosition($trasporto);
        $this->assertSame('stub', $position['source']);

        $fresh = app(TrasportoGpsTrackingService::class)->refreshPosition($trasporto);

        $this->assertNotNull($fresh->gps_tracked_at);
        $this->assertSame($position['latitude'], $fresh->gps_last_position['latitude'] ?? null);
    }

    public function test_gps_live_http_poll_from_provider(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', 'https://gps.test/api/v1');
        Config::set('services.trasporto_gps.api_key', 'gps-test-key');

        Http::fake([
            'gps.test/api/v1/trasporti/*/position' => Http::response([
                'latitude'    => 45.12,
                'longitude'   => 9.45,
                'recorded_at' => now()->toIso8601String(),
                'speed_kmh'   => 80,
            ], 200),
        ]);

        $trasporto = $this->inTransitoTrasporto();
        $position = app(TrasportoGpsTrackingService::class)->pollPosition($trasporto);

        $this->assertSame('live', $position['source']);
        $this->assertSame(45.12, $position['latitude']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/trasporti/')
                && $request->hasHeader('Authorization', 'Bearer gps-test-key');
        });
    }

    public function test_tracking_prep_timeline_logs_stub_channel(): void
    {
        Log::spy();

        $trasporto = $this->inTransitoTrasporto();
        $timeline = app(TrasportoTrackingPrepService::class)->timeline($trasporto);

        $this->assertSame('gps_stub', $timeline[1]['key']);
        $this->assertSame('GPS stub attivo', $timeline[1]['label']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('trasporto.tracking.stub', \Mockery::on(fn (array $ctx) => $ctx['trasporto_id'] === $trasporto->id));
    }

    public function test_trasporto_show_displays_gps_stub_badge_and_refresh(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->inTransitoTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('GPS stub')
            ->assertSee('Aggiorna posizione')
            ->call('refreshGpsPosition')
            ->assertHasNoErrors()
            ->assertSee('Posizione GPS aggiornata');
    }

    public function test_trasporto_show_live_mode_badge(): void
    {
        Config::set('services.trasporto_gps.stub', false);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->inTransitoTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('GPS live');
    }

    public function test_gps_unavailable_for_non_in_transito(): void
    {
        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'stato'                      => TrasportoStato::InPreparazione,
            'quantita_kg'                => 10,
        ]);

        $this->assertFalse(app(TrasportoGpsTrackingService::class)->isTrackingAvailable($trasporto));
    }

    public function test_openstreetmap_embed_url_from_position(): void
    {
        $url = app(TrasportoGpsTrackingService::class)->openStreetMapEmbedUrl([
            'latitude'  => 45.46,
            'longitude' => 9.19,
            'source'    => 'stub',
            'recorded_at' => now()->toIso8601String(),
        ]);

        $this->assertNotNull($url);
        $this->assertStringContainsString('openstreetmap.org/export/embed.html', $url);
        $this->assertStringContainsString('marker=45.46', $url);
    }

    public function test_sprint_98_audit_notes_document_m98_gap(): void
    {
        $path = base_path('docs/SPRINT-98-AUDIT-NOTES.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('M-98-1', $content);
        $this->assertStringContainsString('TrasportoGpsTrackingService', $content);
        $this->assertStringContainsString('TRASPORTO_GPS_STUB', $content);
    }

    private function inTransitoTrasporto(): Trasporto
    {
        return Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'stato'                      => TrasportoStato::InTransito,
            'quantita_kg'                => 50,
        ]);
    }
}
