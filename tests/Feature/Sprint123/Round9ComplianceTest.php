<?php

namespace Tests\Feature\Sprint123;

use App\Domain\Gdpr\GdprService;
use App\Http\Livewire\Admin\TrashIndex;
use App\Http\Livewire\Admin\UsersIndex;
use App\Http\Livewire\Settings\SecuritySettingsPage;
use App\Models\Anagrafica;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class Round9ComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_strong_password_rule_rejects_weak_passwords(): void
    {
        $rule = new StrongPassword;

        $this->assertFalse(Validator::make(['password' => 'short'], ['password' => $rule])->passes());
        $this->assertFalse(Validator::make(['password' => 'alllowercase1!'], ['password' => $rule])->passes());
        $this->assertFalse(Validator::make(['password' => 'NoDigits!@'], ['password' => $rule])->passes());
        $this->assertFalse(Validator::make(['password' => 'NoSpecial1'], ['password' => $rule])->passes());
        $this->assertTrue(Validator::make(['password' => 'Secure1!pass'], ['password' => $rule])->passes());
    }

    public function test_gdpr_export_includes_profile_and_activity(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $export = app(GdprService::class)->exportUserData($user);

        $this->assertSame($user->email, $export['profile']['email']);
        $this->assertArrayHasKey('notifications', $export);
        $this->assertArrayHasKey('activity_logs', $export);
        $this->assertArrayHasKey('vfu_assignments', $export);
    }

    public function test_gdpr_deletion_request_deactivates_and_schedules_user(): void
    {
        $user = User::factory()->create([
            'email'    => 'gdpr-test@example.com',
            'password' => Hash::make('OldPass1!'),
            'active'   => true,
        ]);
        $user->assignRole('operatore');

        app(GdprService::class)->requestDeletion($user, 'Non utilizzo più il servizio');

        $user->refresh();
        $this->assertFalse($user->active);
        $this->assertNotNull($user->deletion_requested_at);
        $this->assertNotNull($user->deletion_scheduled_at);
        $this->assertTrue($user->deletion_scheduled_at->isFuture());
    }

    public function test_gdpr_process_deletions_soft_deletes_due_accounts(): void
    {
        $user = User::factory()->create([
            'email'                  => 'due-delete@example.com',
            'password'               => Hash::make('OldPass1!'),
            'active'                 => false,
            'deletion_requested_at'  => now()->subDays(31),
            'deletion_scheduled_at'  => now()->subDay(),
            'deletion_reason'        => 'Test',
        ]);

        Artisan::call('gdpr:process-deletions');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_security_settings_password_change_requires_strong_password(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SecuritySettingsPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'weak')
            ->set('newPasswordConfirmation', 'weak')
            ->call('changePassword')
            ->assertHasErrors(['newPassword']);
    }

    public function test_admin_create_user_requires_strong_password(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->set('formName', 'Test User')
            ->set('formEmail', 'newuser@example.com')
            ->set('formRole', 'operatore')
            ->set('formPassword', 'weakpass')
            ->call('save')
            ->assertHasErrors(['formPassword']);
    }

    public function test_soft_deleted_vfu_can_be_restored_from_trash(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create();
        $vfu->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('restoreVfu', $vfu->id);

        $this->assertDatabaseHas('vfu_registrations', [
            'id'         => $vfu->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_anagrafica_can_be_restored_from_trash(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $anagrafica = Anagrafica::factory()->create();
        $anagrafica->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->set('tab', 'anagrafiche')
            ->call('restoreAnagrafica', $anagrafica->id);

        $this->assertDatabaseHas('anagrafiche', [
            'id'         => $anagrafica->id,
            'deleted_at' => null,
        ]);
    }

    public function test_version_endpoint_returns_json(): void
    {
        $this->getJson(route('api.version'))
            ->assertOk()
            ->assertJsonStructure(['version', 'build', 'env'])
            ->assertJsonFragment(['version' => config('app_version.version')]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $user->update(['active' => false]);

        $this->post(route('login.store'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }
}
