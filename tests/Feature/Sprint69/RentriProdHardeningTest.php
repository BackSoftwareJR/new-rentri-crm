<?php

namespace Tests\Feature\Sprint69;

use App\Domain\Rentri\RentriLiveModeService;
use App\Domain\Rentri\RentriProdReadinessService;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\RentriSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriProdHardeningTest extends TestCase
{
    use SeedsRentriCertificate;

    private function seedProductionReadySettings(): RentriSetting
    {
        return $this->seedRentriFirmaCertificate([
            'ambiente'             => 'produzione',
            'piva'                 => '12345678903',
            'ragione_sociale'      => 'Impianto Test Srl',
            'last_health_status'   => ['status' => 'ok', 'message' => 'OK'],
            'last_health_check_at' => now(),
        ]);
    }

    public function test_prod_readiness_checklist_requires_production_setup(): void
    {
        $this->seedRentriCertificate();

        $checklist = app(RentriProdReadinessService::class)->checklist();

        $this->assertFalse(app(RentriProdReadinessService::class)->canEnableLiveMode());
        $this->assertFalse(collect($checklist)->firstWhere('key', 'ambiente_produzione')['ok']);
    }

    public function test_prod_readiness_passes_with_full_production_setup(): void
    {
        $this->seedProductionReadySettings();

        $readiness = app(RentriProdReadinessService::class);

        $this->assertTrue($readiness->canEnableLiveMode());
        $this->assertSame(6, $readiness->summary()['ok']);
    }

    public function test_enable_live_mode_sets_runtime_override_and_logs_activity(): void
    {
        Config::set('services.rentri.api_stub', true);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $settings = $this->seedProductionReadySettings();

        $this->assertTrue(app(RentriRuntimeModeService::class)->isApiStub($settings));

        app(RentriLiveModeService::class)->enable($settings, $user->id);

        $fresh = $settings->fresh();
        $this->assertNotNull($fresh->live_mode_enabled_at);
        $this->assertFalse(app(RentriRuntimeModeService::class)->isApiStub($fresh));

        $this->assertDatabaseHas('activity_log', [
            'log_name'     => 'rentri',
            'description'  => 'Passaggio modalità live RENTRI (stub disabilitato via UI)',
            'causer_id'    => $user->id,
        ]);
    }

    public function test_enable_live_mode_fails_when_checklist_incomplete(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $settings = $this->seedRentriCertificate(['ambiente' => 'sandbox']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(RentriLiveModeService::class)->enable($settings, $user->id);
    }

    public function test_rentri_settings_shows_production_step_checklist(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedProductionReadySettings();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('goToStep', 4)
            ->assertSee('Passaggio produzione')
            ->assertSee('Checklist switch produzione')
            ->assertSee('Attiva modalità live');
    }

    public function test_dashboard_shows_prod_stub_banner_when_mismatch(): void
    {
        Config::set('services.rentri.api_stub', true);
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedProductionReadySettings();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('RENTRI produzione — API ancora in stub');
    }

    public function test_connection_status_labels_prod_stub_mismatch(): void
    {
        Config::set('services.rentri.api_stub', true);
        $this->seedProductionReadySettings();

        $status = app(\App\Domain\Rentri\RentriConnectionStatusService::class)->resolve();

        $this->assertSame('stub', $status['api_mode']);
        $this->assertStringContainsString('passaggio live', $status['label']);
    }
}
