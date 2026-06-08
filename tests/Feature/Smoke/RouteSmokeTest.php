<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    public function test_guest_login_page_is_accessible(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Accedi');
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        $this->get(route('segreteria.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_segreteria_can_access_critical_routes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('segreteria.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('segreteria.vfu.index'))->assertOk();
        $this->actingAs($user)->get(route('segreteria.rentri'))->assertOk();
        $this->actingAs($user)->get(route('segreteria.magazzino'))->assertOk();
        $this->actingAs($user)->get(route('segreteria.ecommerce'))->assertOk();
    }

    public function test_operatore_can_access_critical_routes(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('operatore.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('operatore.bonifica'))->assertOk();
        $this->actingAs($user)->get(route('operatore.vetrina'))->assertOk();
        $this->actingAs($user)->get(route('operatore.ricambi'))->assertOk();
    }

    public function test_admin_can_access_audit(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.audit'))->assertOk();
    }

    public function test_rbac_cross_area_returns_forbidden(): void
    {
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->actingAs($operatore)->get(route('segreteria.dashboard'))->assertForbidden();
        $this->actingAs($operatore)->get(route('segreteria.magazzino'))->assertForbidden();
        $this->actingAs($segreteria)->get(route('operatore.dashboard'))->assertForbidden();
        $this->actingAs($segreteria)->get(route('admin.audit'))->assertForbidden();
    }

    public function test_login_post_redirects_segreteria_to_dashboard(): void
    {
        $this->post(route('login.store'), [
            'email'    => 'segreteria@example.com',
            'password' => 'password',
        ])->assertRedirect(route('segreteria.dashboard'));
    }
}
