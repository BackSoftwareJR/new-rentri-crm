<?php

namespace Tests\Feature\Sprint7;

use App\Domain\Rentri\RentriOnboardingService;
use App\Http\Livewire\Settings\RentriSettings;
use App\Models\RentriSetting;
use App\Models\User;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RentriOnboardingHttpTest extends TestCase
{
    public function test_segreteria_can_access_rentri_settings_wizard(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.rentri'))
            ->assertOk()
            ->assertSee('Dati operatore');

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSuccessful()
            ->assertSee('Impostazioni RENTRI');
    }

    public function test_operatore_cannot_access_rentri_settings(): void
    {
        Role::findOrCreate('operatore');
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.rentri'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertForbidden();
    }

    public function test_seeder_provides_sandbox_num_iscr_sito(): void
    {
        $this->seed(\Database\Seeders\RentriSettingSeeder::class);

        $settings = RentriSetting::instance();

        $this->assertSame('SANDBOX-DEMO-001', $settings->num_iscr_sito);
        $this->assertSame('sandbox', $settings->ambiente);
    }

    public function test_wizard_completes_full_onboarding_flow(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        RentriSetting::instance()->update([
            'onboarding_step_completed' => 0,
            'cert_path_encrypted'       => null,
            'cert_password_encrypted'   => null,
            'last_health_status'        => null,
        ]);

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->assertSet('step', 1)
            ->set('ambiente', 'sandbox')
            ->set('cf', '12345678901')
            ->set('cf_operatore', 'RSSMRA80A01H501Z')
            ->set('piva', '12345678901')
            ->set('ragione_sociale', 'Test Srl')
            ->set('num_iscr_sito', 'SITE-WIZ-001')
            ->call('saveOperatorData')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        $this->assertSame(1, RentriSetting::instance()->fresh()->onboarding_step_completed);

        $cert = UploadedFile::fake()->create('operatore.p12', 50);

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->set('certificato', $cert)
            ->set('cert_password', 'secret123')
            ->call('uploadCertificato')
            ->assertHasNoErrors()
            ->assertSet('step', 3);

        $settings = RentriSetting::instance()->fresh();
        $this->assertSame(2, $settings->onboarding_step_completed);
        $this->assertNotNull($settings->cert_path_encrypted);
        $this->assertNotNull($settings->cert_scadenza);

        Livewire::actingAs($user)
            ->test(RentriSettings::class)
            ->call('runHealthCheck')
            ->assertHasNoErrors()
            ->assertSet('onboardingComplete', true);

        $settings = RentriSetting::instance()->fresh();
        $this->assertSame(3, $settings->onboarding_step_completed);
        $this->assertSame('ok', $settings->last_health_status['status'] ?? null);
        $this->assertNotNull($settings->last_health_check_at);
    }
}
