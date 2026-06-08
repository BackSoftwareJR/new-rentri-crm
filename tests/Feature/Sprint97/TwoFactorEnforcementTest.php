<?php

namespace Tests\Feature\Sprint97;

use App\Domain\Auth\TwoFactorEnforcementService;
use App\Domain\Auth\TwoFactorService;
use App\Http\Livewire\Settings\SecuritySettingsPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorEnforcementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('two-factor.enforce_admin_segreteria', false);
        Config::set('two-factor.enforce_grace_until', null);
    }

    public function test_enforcement_disabled_allows_segreteria_without_two_factor(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk();
    }

    public function test_enforcement_enabled_redirects_segreteria_without_two_factor(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertRedirect(route('segreteria.impostazioni.sicurezza'))
            ->assertSessionHas('warning');
    }

    public function test_enforcement_allows_security_settings_without_two_factor(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.sicurezza'))
            ->assertOk();
    }

    public function test_enforcement_allows_segreteria_when_two_factor_enabled(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(TwoFactorService::class)->enable($user, app(TwoFactorService::class)->generateSecret());

        $this->actingAs($user->fresh())
            ->get(route('segreteria.dashboard'))
            ->assertOk();
    }

    public function test_grace_period_allows_access_with_banner(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);
        Config::set('two-factor.enforce_grace_until', now()->addDays(14)->toIso8601String());

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('2FA obbligatoria in arrivo', false);
    }

    public function test_operatore_excluded_from_enforcement(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->assertFalse(app(TwoFactorEnforcementService::class)->appliesTo($user));

        $this->actingAs($user)
            ->get(route('operatore.dashboard'))
            ->assertOk();
    }

    public function test_editor_excluded_from_enforcement_on_segreteria(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        Role::findOrCreate('editor');
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->assertFalse(app(TwoFactorEnforcementService::class)->appliesTo($editor));

        $this->actingAs($editor)
            ->get(route('segreteria.dashboard'))
            ->assertOk();
    }

    public function test_admin_routes_blocked_without_two_factor_when_enforced(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.audit'))
            ->assertRedirect(route('segreteria.impostazioni.sicurezza'));
    }

    public function test_security_settings_shows_enforced_copy(): void
    {
        Config::set('two-factor.enforce_admin_segreteria', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SecuritySettingsPage::class)
            ->assertSee('obbligatoria');
    }

    public function test_sprint_97_runbook_documents_enforcement(): void
    {
        $path = base_path('docs/2FA-PREP-RUNBOOK.md');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA', $content);
        $this->assertStringContainsString('EnsureTwoFactorEnabled', $content);
        $this->assertStringContainsString('TWO_FACTOR_ENFORCE_GRACE_UNTIL', $content);
    }
}
