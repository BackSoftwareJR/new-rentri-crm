<?php

namespace Tests\Feature\Sprint48;

use App\Domain\Demo\DemoRentriPresetService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\RentriSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class DemoOperatorPresetTest extends TestCase
{
    public function test_operator_profiles_listed_in_config(): void
    {
        $profiles = app(DemoRentriPresetService::class)->operatorProfiles();

        $this->assertNotEmpty($profiles);
        $this->assertSame('default', $profiles[0]['key']);
        $this->assertArrayHasKey('label', $profiles[0]);
        $this->assertArrayHasKey('cf_operatore', $profiles[0]);
    }

    public function test_apply_sede_nord_preset_writes_demo_settings(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        RentriSetting::query()->where('is_demo', true)->delete();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->set('selectedOperatorPreset', 'sede_nord')
            ->call('applySandboxPreset')
            ->assertHasNoErrors();

        $settings = RentriSetting::includingAllDemoModes()->where('is_demo', true)->first();
        $this->assertNotNull($settings);
        $this->assertSame('DEMO-SITE-NORD-001', $settings->num_iscr_sito);
        $this->assertSame('VRDLGU85M01F205X', $settings->cf_operatore);
    }

    public function test_sandbox_defaults_preview_updates_with_selected_profile(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->set('selectedOperatorPreset', 'sede_sud')
            ->assertSee('DEMO-SITE-SUD-001')
            ->assertSee('BNCMRA90A01H501U');
    }
}
