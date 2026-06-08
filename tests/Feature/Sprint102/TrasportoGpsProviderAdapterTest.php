<?php

namespace Tests\Feature\Sprint102;

use App\Domain\Trasporti\TrasportoGpsGeofenceService;
use App\Domain\Trasporti\TrasportoGpsPreflightService;
use App\Domain\Trasporti\TrasportoGpsProviderAdapter;
use App\Domain\Trasporti\TrasportoGpsTrackingService;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TrasportoGpsProviderAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.trasporto_gps.stub', true);
    }

    public function test_provider_adapter_default_field_map(): void
    {
        $normalized = app(TrasportoGpsProviderAdapter::class)->normalize([
            'latitude' => 45.46,
            'longitude' => 9.19,
            'recorded_at' => '2026-06-04T10:00:00+02:00',
            'speed_kmh' => 70,
        ]);

        $this->assertSame(45.46, $normalized['latitude']);
        $this->assertSame('live', $normalized['source']);
        $this->assertSame(70.0, $normalized['speed_kmh']);
    }

    public function test_provider_adapter_nested_field_map(): void
    {
        Config::set('services.trasporto_gps.field_map', [
            'latitude' => 'location.lat',
            'longitude' => 'location.lng',
            'recorded_at' => 'timestamp',
            'speed_kmh' => 'speed',
        ]);

        $contract = json_decode(
            file_get_contents(base_path('tests/fixtures/gps/position-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $normalized = app(TrasportoGpsProviderAdapter::class)->normalize(
            $contract['provider_variants']['nested_fleet'],
        );

        $this->assertSame(45.12, $normalized['latitude']);
        $this->assertSame(9.45, $normalized['longitude']);
        $this->assertSame(80.0, $normalized['speed_kmh']);
    }

    public function test_gps_preflight_requires_url_and_api_key_in_live_mode(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', '');
        Config::set('services.trasporto_gps.api_key', '');

        $checklist = app(TrasportoGpsPreflightService::class)->checklist();

        $this->assertFalse(app(TrasportoGpsPreflightService::class)->isReady());
        $this->assertFalse(collect($checklist)->firstWhere('key', 'provider_url')['ok']);
        $this->assertFalse(collect($checklist)->firstWhere('key', 'api_key')['ok']);
    }

    public function test_live_poll_uses_provider_adapter(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', 'https://gps.test/api/v1');
        Config::set('services.trasporto_gps.api_key', 'gps-s102');
        Config::set('services.trasporto_gps.field_map', [
            'latitude' => 'location.lat',
            'longitude' => 'location.lng',
            'recorded_at' => 'timestamp',
            'speed_kmh' => 'speed',
        ]);

        Http::fake([
            'gps.test/api/v1/trasporti/*/position' => Http::response([
                'location' => ['lat' => 45.5, 'lng' => 9.3],
                'timestamp' => '2026-06-04T12:00:00+02:00',
                'speed' => 55,
            ], 200),
        ]);

        $trasporto = $this->inTransitoTrasporto();
        $position = app(TrasportoGpsTrackingService::class)->pollPosition($trasporto);

        $this->assertSame(45.5, $position['latitude']);
        $this->assertSame('live', $position['source']);
    }

    public function test_geofence_dispatches_notification_when_beyond_radius(): void
    {
        Config::set('services.trasporto_gps.geofence_enabled', true);
        Config::set('services.trasporto_gps.geofence_radius_km', 10);
        Config::set('services.trasporto_gps.geofence_destination_lat', 45.0);
        Config::set('services.trasporto_gps.geofence_destination_lng', 9.0);

        $trasporto = $this->inTransitoTrasporto();
        $distance = app(TrasportoGpsGeofenceService::class)->checkAndNotify($trasporto, [
            'latitude' => 46.5,
            'longitude' => 10.0,
        ]);

        $this->assertNotNull($distance);
        $this->assertGreaterThan(10, $distance);
    }

    public function test_trasporto_show_live_displays_gps_preflight_checklist(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', 'https://gps.test/api/v1');
        Config::set('services.trasporto_gps.api_key', 'gps-key');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = $this->inTransitoTrasporto();

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('GPS live')
            ->assertSee('TRASPORTO_GPS_PROVIDER_URL')
            ->assertSee('TRASPORTO_GPS_API_KEY');
    }

    public function test_fixture_documents_provider_contract_variants(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('tests/fixtures/gps/position-response.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertArrayHasKey('provider_variants', $contract);
        $this->assertArrayHasKey('nested_field_map', $contract);
        $this->assertSame('location.lat', $contract['nested_field_map']['latitude']);
    }

    public function test_sprint_102_audit_notes_document_adapter(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-102-AUDIT-NOTES.md'));

        $this->assertStringContainsString('TrasportoGpsProviderAdapter', $content);
        $this->assertStringContainsString('TRASPORTO_GPS_FIELD_LAT', $content);
        $this->assertStringContainsString('geofence', $content);
    }

    private function inTransitoTrasporto(): Trasporto
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();
        $dest = Anagrafica::factory()->create();

        return Trasporto::create([
            'codice_cer_id' => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'quantita_kg' => 100,
            'stato' => TrasportoStato::InTransito,
            'user_id' => $user->id,
        ]);
    }
}
