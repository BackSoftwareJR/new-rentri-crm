<?php

namespace Tests\Feature\Sprint67;

use App\Domain\Auth\TwoFactorService;
use App\Http\Livewire\Settings\SecuritySettingsPage;
use App\Models\User;
use App\Support\TwoFactorSettings;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorTotpTest extends TestCase
{
    public function test_login_with_two_factor_redirects_to_challenge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $secret = $this->enableTwoFactor($user);

        $response = $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session('login.two_factor.id'));

        $this->assertNotEmpty($secret);
    }

    public function test_two_factor_challenge_with_valid_code_completes_login(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $secret = $this->enableTwoFactor($user);
        $twoFactor = app(TwoFactorService::class);

        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge.store'), [
            'code' => $twoFactor->currentOtp($secret),
        ])->assertRedirect(route('segreteria.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_challenge_rejects_invalid_code(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->enableTwoFactor($user);

        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ]);

        $this->post(route('two-factor.challenge.store'), [
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_security_settings_livewire_enables_two_factor(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);

        $component = Livewire::actingAs($user)
            ->test(SecuritySettingsPage::class)
            ->call('startSetup')
            ->assertSet('setupMode', true);

        $secret = $component->get('setupSecret');
        $this->assertNotEmpty($secret);

        $component
            ->set('confirmCode', $twoFactor->currentOtp($secret))
            ->call('confirmSetup')
            ->assertHasNoErrors()
            ->assertSet('enabled', true);

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_operatore_cannot_access_security_settings(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.sicurezza'))
            ->assertForbidden();
    }

    public function test_editor_cannot_manage_two_factor_settings(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->assertFalse(
            Gate::forUser($editor)->allows('manage', TwoFactorSettings::instance()),
        );
    }

    public function test_user_without_two_factor_logs_in_directly(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ])->assertRedirect(route('segreteria.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    private function enableTwoFactor(User $user): string
    {
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $twoFactor->enable($user, $secret);

        return $secret;
    }
}
