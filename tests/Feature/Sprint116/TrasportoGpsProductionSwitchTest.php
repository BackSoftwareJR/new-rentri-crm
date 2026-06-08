<?php

namespace Tests\Feature\Sprint116;

use App\Domain\Trasporti\TrasportoGpsProductionSwitchService;
use App\Enums\TrasportoStato;
use App\Http\Livewire\Segreteria\Trasporti\TrasportiIndex;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\Trasporto;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TrasportoGpsProductionSwitchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.trasporto_gps.stub', true);
    }

    private function configureLiveProductionEnv(string $fieldMapPreset = 'flat_default'): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', 'https://gps-vendor.prod.net/api/v1');
        Config::set('services.trasporto_gps.api_key', 'gps-prod-key-s116');
        Config::set('services.trasporto_gps.positions_path', '/trasporti/{id}/position');

        if ($fieldMapPreset === 'nested_fleet') {
            Config::set('services.trasporto_gps.field_map', [
                'latitude'    => 'location.lat',
                'longitude'   => 'location.lng',
                'recorded_at' => 'timestamp',
                'speed_kmh'   => 'speed',
            ]);
        } else {
            Config::set('services.trasporto_gps.field_map', [
                'latitude'    => 'latitude',
                'longitude'   => 'longitude',
                'recorded_at' => 'recorded_at',
                'speed_kmh'   => 'speed_kmh',
            ]);
        }
    }

    public function test_stub_mode_switch_checklist_passes_dry_run(): void
    {
        $switch = app(TrasportoGpsProductionSwitchService::class);

        $report = $switch->dryRunReport();

        $this->assertTrue($report['passed']);
        $this->assertFalse($report['live_active']);
        $this->assertSame('stub', $report['summary']['mode']);
    }

    public function test_live_mode_requires_real_url_and_api_key(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', 'https://gps-provider.example.com/api/v1');
        Config::set('services.trasporto_gps.api_key', '');

        $switch = app(TrasportoGpsProductionSwitchService::class);

        $this->assertFalse($switch->canSwitchToLive());

        $keys = array_column($switch->unifiedChecklist(), 'key');
        $this->assertContains('provider_url_not_placeholder', $keys);
        $this->assertContains('api_key', $keys);
    }

    public function test_can_switch_when_live_env_and_flat_preset_configured(): void
    {
        $this->configureLiveProductionEnv('flat_default');

        $switch = app(TrasportoGpsProductionSwitchService::class);

        $this->assertTrue($switch->canSwitchToLive());
        $this->assertTrue($switch->isLiveActive());
        $this->assertSame('flat_default', $switch->activeFieldMapPreset());
    }

    public function test_probe_skips_in_stub_mode(): void
    {
        $probe = app(TrasportoGpsProductionSwitchService::class)->probeProvider();

        $this->assertTrue($probe['ok']);
        $this->assertStringContainsString('stub', strtolower($probe['message']));
    }

    public function test_probe_http_succeeds_with_live_provider_fake(): void
    {
        $this->configureLiveProductionEnv('flat_default');

        Http::fake([
            'gps-vendor.prod.net/*' => Http::response([
                'latitude'    => 45.46,
                'longitude'   => 9.19,
                'recorded_at' => '2026-06-04T14:30:00+02:00',
                'speed_kmh'   => 60,
            ], 200),
        ]);

        $probe = app(TrasportoGpsProductionSwitchService::class)->probeProvider();

        $this->assertTrue($probe['ok']);
        $this->assertSame(200, $probe['http_status']);
        $this->assertSame(45.46, $probe['sample']['latitude']);
    }

    public function test_gps_switch_check_command_outputs_dry_run_report(): void
    {
        Config::set('services.trasporto_gps.stub', false);
        Config::set('services.trasporto_gps.provider_url', '');
        Config::set('services.trasporto_gps.api_key', '');

        $exitCode = Artisan::call('trasporto:gps-switch-check', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('GPS provider switch', $output);
        $this->assertStringContainsString('GPS-PROVIDER-PRODUZIONE-RUNBOOK.md', $output);
        $this->assertStringContainsString('flat_default', $output);
    }

    public function test_trasporti_hub_shows_gps_switch_status_section(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportiIndex::class)
            ->assertSee('Provider GPS')
            ->assertSee('trasporto:gps-switch-check')
            ->assertSee('GPS-PROVIDER-PRODUZIONE-RUNBOOK.md')
            ->assertSee('Rollback stub');
    }

    public function test_runbook_documents_contract_fallback_and_probe(): void
    {
        $content = file_get_contents(base_path('docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md'));

        $this->assertStringContainsString('TRASPORTO_GPS_STUB', $content);
        $this->assertStringContainsString('flat_default', $content);
        $this->assertStringContainsString('nested_fleet', $content);
        $this->assertStringContainsString('Fallback stub', $content);
        $this->assertStringContainsString('trasporto:gps-switch-check', $content);
    }

    public function test_rollback_steps_include_stub_redeploy_and_monitor(): void
    {
        $steps = app(TrasportoGpsProductionSwitchService::class)->rollbackSteps();

        $this->assertGreaterThanOrEqual(4, count($steps));
        $actions = array_column($steps, 'action');
        $this->assertTrue(
            collect($actions)->contains(fn (string $a): bool => str_contains($a, 'TRASPORTO_GPS_STUB=true')),
        );
    }

    public function test_nested_fleet_preset_detected_when_field_map_configured(): void
    {
        $this->configureLiveProductionEnv('nested_fleet');

        $switch = app(TrasportoGpsProductionSwitchService::class);

        $this->assertSame('nested_fleet', $switch->activeFieldMapPreset());
        $this->assertArrayHasKey('nested_fleet', $switch->productionFieldMapPresets());
    }

    public function test_probe_uses_in_transito_transport_when_probe_id_set(): void
    {
        $this->configureLiveProductionEnv('nested_fleet');

        $trasporto = $this->inTransitoTrasporto();
        Config::set('services.trasporto_gps.probe_transport_id', $trasporto->id);

        Http::fake([
            'gps-vendor.prod.net/*' => Http::response([
                'location'  => ['lat' => 45.12, 'lng' => 9.45],
                'timestamp' => '2026-06-04T14:30:00+02:00',
                'speed'     => 80,
            ], 200),
        ]);

        $probe = app(TrasportoGpsProductionSwitchService::class)->probeProvider();

        $this->assertTrue($probe['ok']);
        $this->assertStringContainsString((string) $trasporto->id, $probe['message']);
        $this->assertSame(45.12, $probe['sample']['latitude']);
    }

    private function inTransitoTrasporto(): Trasporto
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $cer = CodiceCer::factory()->create();
        $dest = Anagrafica::factory()->create();

        return Trasporto::create([
            'codice_cer_id'              => $cer->id,
            'anagrafica_destinatario_id' => $dest->id,
            'quantita_kg'                => 100,
            'stato'                      => TrasportoStato::InTransito,
            'user_id'                    => $user->id,
        ]);
    }
}
