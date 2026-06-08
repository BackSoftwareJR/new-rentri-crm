<?php

namespace Tests\Feature\Sprint106;

use App\Domain\Rentri\RentriLiveModeService;
use App\Domain\Rentri\RentriProductionSwitchService;
use App\Http\Livewire\Segreteria\Rentri;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriProductionSwitchTest extends TestCase
{
    use SeedsRentriCertificate;

    private function seedFullProductionSettings(): void
    {
        $this->seedRentriFirmaCertificate([
            'ambiente'             => 'produzione',
            'piva'                 => '12345678903',
            'ragione_sociale'      => 'Impianto Test Srl',
            'last_health_status'   => ['status' => 'ok', 'message' => 'OK'],
            'last_health_check_at' => now(),
        ]);
    }

    private function configureProductionEnv(): void
    {
        Config::set('services.rentri.env', 'production');
        Config::set('services.rentri.api_stub', false);
        Config::set('services.rentri.firma_stub', false);
        Config::set('services.rentri.base_url_production', 'https://api.rentri.gov.it');
    }

    public function test_unified_checklist_requires_rentri_env_production(): void
    {
        Config::set('services.rentri.env', 'sandbox');
        $this->seedFullProductionSettings();

        $switch = app(RentriProductionSwitchService::class);

        $this->assertFalse($switch->canSwitchToProduction());
        $keys = array_column($switch->unifiedChecklist(), 'key');
        $this->assertContains('rentri_env', $keys);
        $this->assertContains('waf_block_gate', $keys);
    }

    public function test_can_switch_when_production_env_ui_and_stubs_off(): void
    {
        $this->configureProductionEnv();
        $this->seedFullProductionSettings();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(RentriLiveModeService::class)->enable(
            \App\Models\RentriSetting::instance(),
            $user->id,
        );

        $switch = app(RentriProductionSwitchService::class);

        $this->assertTrue($switch->canSwitchToProduction());
        $this->assertTrue($switch->isProductionActive());
    }

    public function test_rollback_steps_include_stub_and_activity_log(): void
    {
        $steps = app(RentriProductionSwitchService::class)->rollbackSteps();

        $this->assertGreaterThanOrEqual(4, count($steps));
        $actions = array_column($steps, 'action');
        $this->assertTrue(
            collect($actions)->contains(fn (string $a): bool => str_contains($a, 'RENTRI_API_STUB=true')),
        );
        $this->assertTrue(
            collect($actions)->contains(fn (string $a): bool => str_contains($a, 'Rientra in stub')),
        );
    }

    public function test_production_switch_runbook_documents_48h_and_rollback(): void
    {
        $content = file_get_contents(base_path('docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md'));

        $this->assertStringContainsString('48h', $content);
        $this->assertStringContainsString('Rollback', $content);
        $this->assertStringContainsString('activity log', $content);
        $this->assertStringContainsString('rentri:production-switch-check', $content);
    }

    public function test_production_switch_check_command_outputs_dry_run_report(): void
    {
        Config::set('services.rentri.env', 'sandbox');
        Config::set('services.rentri.api_stub', true);

        $exitCode = Artisan::call('rentri:production-switch-check', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Checklist unificata', $output);
        $this->assertStringContainsString('RENTRI-PRODUCTION-SWITCH-RUNBOOK.md', $output);
    }

    public function test_rentri_hub_shows_production_switch_status_card(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedFullProductionSettings();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('Switch produzione MASE')
            ->assertSee('rentri-production-switch-status')
            ->assertSee('rentri:production-switch-check');
    }

    public function test_rentri_settings_step4_shows_unified_switch_checklist(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->seedFullProductionSettings();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('goToStep', 4)
            ->assertSee('Checklist switch produzione')
            ->assertSee('RENTRI_ENV=production')
            ->assertSee('RENTRI-PRODUCTION-SWITCH-RUNBOOK.md');
    }

    public function test_go_live_rentri_documents_post_waf_gate(): void
    {
        $content = file_get_contents(base_path('docs/GO-LIVE-RENTRI.md'));

        $this->assertStringContainsString('WAF', $content);
        $this->assertStringContainsString('RENTRI-PRODUCTION-SWITCH-RUNBOOK.md', $content);
        $this->assertStringContainsString('rentri:production-switch-check', $content);
    }

    public function test_fixture_documents_production_switch_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/rentri/production-switch.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(106, $fixture['sprint']);
        $this->assertSame('production', $fixture['env_required']['RENTRI_ENV']);
        $this->assertSame(48, $fixture['monitor_hours']);
    }

    public function test_waf_block_gate_is_optional_in_checklist(): void
    {
        Config::set('waf.mode', 'off');
        $this->configureProductionEnv();
        $this->seedFullProductionSettings();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(RentriLiveModeService::class)->enable(
            \App\Models\RentriSetting::instance(),
            $user->id,
        );

        $wafItem = collect(app(RentriProductionSwitchService::class)->unifiedChecklist())
            ->firstWhere('key', 'waf_block_gate');

        $this->assertTrue($wafItem['optional']);
        $this->assertFalse($wafItem['ok']);
        $this->assertTrue(app(RentriProductionSwitchService::class)->canSwitchToProduction());
    }
}
