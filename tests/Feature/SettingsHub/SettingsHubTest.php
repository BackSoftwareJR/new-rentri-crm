<?php

namespace Tests\Feature\SettingsHub;

use App\Http\Livewire\Settings\SettingsHub;
use App\Models\CompanySetting;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsHubTest extends TestCase
{
    // ── Authorization ──────────────────────────────────────────────────────

    public function test_admin_can_access_settings_hub(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->assertOk()
            ->assertSee('Dati azienda');
    }

    public function test_segreteria_can_access_settings_hub(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->assertOk();
    }

    public function test_operatore_cannot_access_settings_hub(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/segreteria/impostazioni')
            ->assertForbidden();
    }

    // ── Tab switching ──────────────────────────────────────────────────────

    public function test_default_tab_is_azienda(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->assertSet('activeTab', 'azienda');
    }

    public function test_can_switch_tabs(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->call('switchTab', 'pagamenti')
            ->assertSet('activeTab', 'pagamenti')
            ->assertSee('Stripe')
            ->call('switchTab', 'email')
            ->assertSet('activeTab', 'email')
            ->assertSee('SMTP')
            ->call('switchTab', 'integrazioni')
            ->assertSet('activeTab', 'integrazioni')
            ->assertSee('GPS')
            ->call('switchTab', 'sistema')
            ->assertSet('activeTab', 'sistema')
            ->assertSee('Ambiente')
            ->call('switchTab', 'rentri')
            ->assertSet('activeTab', 'rentri')
            ->assertSee('RENTRI');
    }

    public function test_invalid_tab_is_ignored(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'azienda')
            ->call('switchTab', 'invalid-tab')
            ->assertSet('activeTab', 'azienda');
    }

    // ── Azienda save ──────────────────────────────────────────────────────

    public function test_admin_can_save_company_data(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('company_ragione_sociale', 'Rottami S.r.l.')
            ->set('company_piva', 'IT12345678901')
            ->set('company_cf', 'RSSMRA80A01H501U')
            ->set('company_email', 'info@rottami.it')
            ->set('company_pec', 'rottami@pec.it')
            ->set('company_telefono', '+39 02 1234567')
            ->set('company_num_albo', 'AB/123456')
            ->call('saveAzienda')
            ->assertHasNoErrors();

        $this->assertSame('Rottami S.r.l.', CompanySetting::get('company_ragione_sociale'));
        $this->assertSame('IT12345678901',   CompanySetting::get('company_piva'));
    }

    public function test_segreteria_cannot_save_company_data(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('company_ragione_sociale', 'Unauthorized Co.')
            ->call('saveAzienda')
            ->assertForbidden();
    }

    public function test_company_data_validation(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('company_pec', 'not-an-email')
            ->set('company_email', 'also-not-valid')
            ->call('saveAzienda')
            ->assertHasErrors(['company_pec', 'company_email']);
    }

    // ── Pagamenti save ────────────────────────────────────────────────────

    public function test_admin_can_save_stripe_toggles(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'pagamenti')
            ->set('stripe_live_mode', false)
            ->set('stripe_dispute_stub', true)
            ->set('stripe_payment_card', true)
            ->set('stripe_payment_sepa', false)
            ->call('savePagamenti')
            ->assertHasNoErrors();

        $this->assertFalse(CompanySetting::get('stripe_live_mode'));
        $this->assertTrue(CompanySetting::get('stripe_dispute_stub'));
    }

    public function test_sensitive_stripe_keys_are_stored_encrypted(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'pagamenti')
            ->set('stripe_secret', 'sk_test_abc123')
            ->call('savePagamenti')
            ->assertHasNoErrors();

        // The raw DB value should be encrypted (not plaintext)
        $raw = \App\Models\CompanySetting::query()->where('key', 'stripe_secret')->value('value');
        $this->assertNotSame('sk_test_abc123', $raw, 'Secret key should be encrypted in DB');

        // But CompanySetting::get() should decrypt it correctly
        $this->assertSame('sk_test_abc123', CompanySetting::get('stripe_secret'));
    }

    // ── Email save ────────────────────────────────────────────────────────

    public function test_admin_can_save_email_config(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'email')
            ->set('mail_host', 'smtp.mailgun.org')
            ->set('mail_port', '587')
            ->set('mail_encryption', 'tls')
            ->set('mail_from_name', 'ERP VFU Test')
            ->set('mail_from_address', 'noreply@test.it')
            ->set('notifications_live', false)
            ->call('saveEmail')
            ->assertHasNoErrors();

        $this->assertSame('smtp.mailgun.org', CompanySetting::get('mail_host'));
        $this->assertSame('ERP VFU Test',      CompanySetting::get('mail_from_name'));
    }

    public function test_email_validation_catches_bad_from_address(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'email')
            ->set('mail_from_address', 'not-an-email')
            ->call('saveEmail')
            ->assertHasErrors(['mail_from_address']);
    }

    // ── Integrazioni save ─────────────────────────────────────────────────

    public function test_admin_can_save_integrations(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'integrazioni')
            ->set('gps_stub_mode', true)
            ->set('gps_provider_url', 'https://gps.example.com/api/v1')
            ->set('mud_stub_mode', true)
            ->set('shop_enabled', true)
            ->call('saveIntegrazioni')
            ->assertHasNoErrors();

        $this->assertTrue(CompanySetting::get('gps_stub_mode'));
        $this->assertSame('https://gps.example.com/api/v1', CompanySetting::get('gps_provider_url'));
        $this->assertTrue(CompanySetting::get('shop_enabled'));
    }

    public function test_gps_url_must_be_valid_url(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'integrazioni')
            ->set('gps_provider_url', 'not-a-url')
            ->call('saveIntegrazioni')
            ->assertHasErrors(['gps_provider_url']);
    }

    // ── Sistema save ──────────────────────────────────────────────────────

    public function test_admin_can_save_system_settings(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'sistema')
            ->set('log_level', 'warning')
            ->set('demo_mode', false)
            ->set('app_debug', false)
            ->call('saveSistema')
            ->assertHasNoErrors();

        $this->assertSame('warning', CompanySetting::get('log_level'));
        $this->assertFalse(CompanySetting::get('app_debug'));
    }

    public function test_invalid_log_level_fails_validation(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->set('activeTab', 'sistema')
            ->set('log_level', 'verbose')
            ->call('saveSistema')
            ->assertHasErrors(['log_level']);
    }

    // ── Cache & Artisan ───────────────────────────────────────────────────

    public function test_admin_can_clear_app_cache(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->call('clearAppCache')
            ->assertHasNoErrors();
    }

    public function test_admin_can_clear_config_cache(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SettingsHub::class)
            ->call('clearConfigCache')
            ->assertHasNoErrors();
    }

    // ── Route ─────────────────────────────────────────────────────────────

    public function test_settings_hub_route_accessible_to_segreteria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/segreteria/impostazioni')
            ->assertOk();
    }

    public function test_old_rentri_settings_route_still_accessible(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/segreteria/impostazioni/rentri')
            ->assertOk();
    }

    // ── CompanySetting model ──────────────────────────────────────────────

    public function test_company_setting_get_set_roundtrip(): void
    {
        CompanySetting::set('company_ragione_sociale', 'Test SpA');
        $this->assertSame('Test SpA', CompanySetting::get('company_ragione_sociale'));
    }

    public function test_company_setting_sensitive_key_is_encrypted(): void
    {
        CompanySetting::set('stripe_secret', 'sk_test_xyz');

        $raw = \App\Models\CompanySetting::query()->where('key', 'stripe_secret')->value('value');
        $this->assertNotSame('sk_test_xyz', $raw);
        $this->assertSame('sk_test_xyz', CompanySetting::get('stripe_secret'));
    }

    public function test_company_setting_bool_type_roundtrip(): void
    {
        CompanySetting::set('stripe_live_mode', true);
        $this->assertTrue(CompanySetting::get('stripe_live_mode'));

        CompanySetting::set('stripe_live_mode', false);
        $this->assertFalse(CompanySetting::get('stripe_live_mode'));
    }

    public function test_company_setting_returns_default_when_not_set(): void
    {
        $this->assertSame('fallback', CompanySetting::get('non_existent_key', 'fallback'));
    }
}
