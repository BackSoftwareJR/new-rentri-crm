<?php

namespace Tests\Feature\Sprint46;

use App\Domain\Demo\DemoRentriPresetService;
use App\Domain\Demo\DemoSeedService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\RentriSetting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class DemoRentriPresetTest extends TestCase
{
    public function test_apply_sandbox_preset_writes_demo_settings(): void
    {
        Config::set('demo.enabled', false);
        session([config('demo.session.key') => true]);

        RentriSetting::query()->where('is_demo', true)->delete();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('applySandboxPreset')
            ->assertHasNoErrors();

        $settings = RentriSetting::includingAllDemoModes()->where('is_demo', true)->first();
        $this->assertNotNull($settings);
        $this->assertSame('sandbox', $settings->ambiente);
        $this->assertSame(DemoSeedService::NUM_ISCR_SITO, $settings->num_iscr_sito);
    }

    public function test_preset_defaults_use_config_when_set(): void
    {
        Config::set('demo.rentri_preset.num_iscr_sito', 'CUSTOM-SITE-99');

        $defaults = app(DemoRentriPresetService::class)->sandboxDefaults();

        $this->assertSame('CUSTOM-SITE-99', $defaults['num_iscr_sito']);
    }

    public function test_ciclo_4_documentation_exists(): void
    {
        $this->assertFileExists(base_path('docs/CICLO-4-PIANO.md'));
        $this->assertFileExists(base_path('docs/PALESTRA-OPERATIVA.md'));

        $piano = file_get_contents(base_path('docs/CICLO-4-PIANO.md'));
        $this->assertStringContainsString('Sprint 46', $piano);
        $this->assertStringContainsString('DemoModeSessionService', $piano);
    }
}
