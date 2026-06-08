<?php

namespace Tests\Feature\Sprint100;

use App\Domain\Auth\TwoFactorService;
use App\Http\Livewire\Settings\SecuritySettingsPage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorRecoveryCodesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // TwoFactorService unit tests
    // -------------------------------------------------------------------------

    public function test_enable_generates_recovery_codes_and_stores_hashed(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $plainCodes = $twoFactor->enable($user, $secret);

        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, $plainCodes);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $storedCodes = $user->fresh()->two_factor_recovery_codes;
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, $storedCodes);

        // Stored values are hashes, not plaintext
        foreach ($plainCodes as $i => $plain) {
            $this->assertNotEquals($plain, $storedCodes[$i]);
            $this->assertTrue(password_verify($plain, $storedCodes[$i]));
        }

        $this->disableTwoFactor($user);
    }

    public function test_recovery_code_format_is_xxxx_xxxx_xxxx(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $plainCodes = $twoFactor->enable($user, $secret);

        foreach ($plainCodes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code);
        }

        $this->disableTwoFactor($user);
    }

    public function test_use_recovery_code_succeeds_and_nulls_entry(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $plainCodes = $twoFactor->enable($user, $secret);
        $codeToUse = $plainCodes[0];

        $result = $twoFactor->useRecoveryCode($user, $codeToUse);

        $this->assertTrue($result);

        $fresh = $user->fresh();
        $storedCodes = $fresh->two_factor_recovery_codes;
        $this->assertNull($storedCodes[0], 'Used code must be nulled');

        // Remaining count should be 7
        $this->assertSame(
            TwoFactorService::RECOVERY_CODE_COUNT - 1,
            $twoFactor->remainingRecoveryCodesCount($fresh),
        );

        $this->disableTwoFactor($user);
    }

    public function test_recovery_code_is_single_use(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $plainCodes = $twoFactor->enable($user, $secret);
        $codeToUse = $plainCodes[2];

        $this->assertTrue($twoFactor->useRecoveryCode($user, $codeToUse));
        $this->assertFalse($twoFactor->useRecoveryCode($user->fresh(), $codeToUse), 'Second use must fail');

        $this->disableTwoFactor($user);
    }

    public function test_invalid_recovery_code_returns_false(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $twoFactor->enable($user, $secret);

        $this->assertFalse($twoFactor->useRecoveryCode($user, 'AAAA-BBBB-CCCC'));

        $this->disableTwoFactor($user);
    }

    public function test_regenerate_recovery_codes_invalidates_old_codes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();

        $originalCodes = $twoFactor->enable($user, $secret);

        $newCodes = $twoFactor->regenerateRecoveryCodes($user);

        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, $newCodes);
        $this->assertNotEquals($originalCodes, $newCodes);

        // Old codes should no longer work
        $this->assertFalse($twoFactor->useRecoveryCode($user->fresh(), $originalCodes[0]));

        // New codes should work
        $this->assertTrue($twoFactor->useRecoveryCode($user->fresh(), $newCodes[0]));

        $this->disableTwoFactor($user);
    }

    // -------------------------------------------------------------------------
    // HTTP / controller tests
    // -------------------------------------------------------------------------

    public function test_recovery_code_login_authenticates_user(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $codes = $twoFactor->enable($user, $secret);

        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge.store'), [
            'recovery_code' => $codes[0],
        ])->assertRedirect(route('segreteria.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->disableTwoFactor($user);
    }

    public function test_invalid_recovery_code_at_challenge_fails(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $twoFactor->enable($user, $secret);

        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ]);

        $this->post(route('two-factor.challenge.store'), [
            'recovery_code' => 'XXXX-XXXX-XXXX',
        ])->assertSessionHasErrors('recovery_code');

        $this->assertGuest();

        $this->disableTwoFactor($user);
    }

    // -------------------------------------------------------------------------
    // Livewire settings page tests
    // -------------------------------------------------------------------------

    public function test_confirm_setup_shows_recovery_modal_with_codes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);

        $component = Livewire::actingAs($user)
            ->test(SecuritySettingsPage::class)
            ->call('startSetup')
            ->assertSet('setupMode', true);

        $secret = $component->get('setupSecret');

        $component
            ->set('confirmCode', $twoFactor->currentOtp($secret))
            ->call('confirmSetup')
            ->assertHasNoErrors()
            ->assertSet('enabled', true)
            ->assertSet('showRecoveryModal', true);

        $codes = $component->get('newRecoveryCodes');
        $this->assertCount(TwoFactorService::RECOVERY_CODE_COUNT, $codes);

        $this->disableTwoFactor($user);
    }

    public function test_acknowledge_recovery_codes_requires_checkbox(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $twoFactor->enable($user, $secret);

        Livewire::actingAs($user->fresh())
            ->test(SecuritySettingsPage::class)
            ->set('showRecoveryModal', true)
            ->set('newRecoveryCodes', ['CODE-AAAA-BBBB'])
            ->set('recoveryCodesAcknowledged', false)
            ->call('acknowledgeRecoveryCodes')
            ->assertHasErrors('recoveryCodesAcknowledged');

        $this->disableTwoFactor($user);
    }

    public function test_regenerate_recovery_codes_requires_correct_password(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $twoFactor->enable($user, $secret);

        Livewire::actingAs($user->fresh())
            ->test(SecuritySettingsPage::class)
            ->set('regenPassword', 'wrong-password')
            ->call('regenerateRecoveryCodes')
            ->assertHasErrors('regenPassword');

        $this->disableTwoFactor($user);
    }

    public function test_regenerate_recovery_codes_with_correct_password_shows_modal(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $twoFactor->enable($user, $secret);

        Livewire::actingAs($user->fresh())
            ->test(SecuritySettingsPage::class)
            ->set('regenPassword', 'password')
            ->call('regenerateRecoveryCodes')
            ->assertHasNoErrors()
            ->assertSet('showRecoveryModal', true)
            ->assertSet('showRegenForm', false);

        $this->disableTwoFactor($user);
    }

    // -------------------------------------------------------------------------

    private function disableTwoFactor(User $user): void
    {
        $user->fresh()->forceFill([
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }
}
