<?php

namespace Tests\Feature\Sprint86;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Http\Livewire\Segreteria\Fir\FirIndex;
use App\Http\Livewire\Segreteria\Rentri;
use App\Http\Livewire\Segreteria\Trasporti\TrasportoShow;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Models\User;
use App\Enums\TrasportoStato;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\Support\SeedsRentriCertificate;
use Tests\TestCase;

class RentriApiModeBadgeUiTest extends TestCase
{
    use SeedsRentriCertificate;

    public function test_runtime_display_label_defaults_to_stub_sandbox(): void
    {
        $this->assertSame('stub sandbox', app(RentriRuntimeModeService::class)->apiModeDisplayLabel());
        $this->assertSame('info', app(RentriRuntimeModeService::class)->apiModeDisplayVariant());
    }

    public function test_runtime_display_label_live_after_ui_enable(): void
    {
        $settings = $this->seedRentriCertificate();
        $settings->update(['live_mode_enabled_at' => now()]);

        $runtime = app(RentriRuntimeModeService::class);

        $this->assertSame('RENTRI live', $runtime->apiModeDisplayLabel($settings->fresh()));
        $this->assertSame('success', $runtime->apiModeDisplayVariant($settings->fresh()));
    }

    public function test_runtime_display_label_demo_offline(): void
    {
        Config::set('demo.enabled', true);
        Config::set('demo.rentri.offline_no_http', true);

        $runtime = app(RentriRuntimeModeService::class);

        $this->assertSame('demo offline', $runtime->apiModeDisplayLabel());
        $this->assertSame('warning', $runtime->apiModeDisplayVariant());
    }

    public function test_trasporto_show_displays_api_mode_badge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'stato'                      => TrasportoStato::InPreparazione,
            'quantita_kg'                => 10,
        ]);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('stub sandbox');
    }

    public function test_rentri_hub_displays_api_mode_badge(): void
    {
        $this->seedRentriCertificate();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('stub sandbox');
    }

    public function test_fir_index_displays_api_mode_badge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(FirIndex::class)
            ->assertSee('stub sandbox');
    }

    public function test_rentri_settings_displays_api_mode_badge(): void
    {
        $this->seedRentriCertificate();
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSee('stub sandbox')
            ->assertSee('Modalità API');
    }

    public function test_trasporto_in_transito_shows_badge_in_tracking_section(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $trasporto = Trasporto::create([
            'codice_cer_id'              => CodiceCer::factory()->create()->id,
            'anagrafica_destinatario_id' => Anagrafica::factory()->create(['tipo' => 'impianto'])->id,
            'stato'                      => TrasportoStato::InTransito,
            'quantita_kg'                => 25,
        ]);

        Livewire::actingAs($user)
            ->test(TrasportoShow::class, ['trasporto' => $trasporto])
            ->assertSee('Tracking GPS')
            ->assertSee('stub sandbox');
    }
}
