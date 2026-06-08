<?php

namespace Tests\Feature\Sprint30;

use App\Models\User;
use Tests\TestCase;

class MvpClosureSmokeTest extends TestCase
{
    public function test_segreteria_dashboard_mvp_sections_post_seed(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('VFU & Bonifica')
            ->assertSee('RENTRI')
            ->assertSee('Migrazione legacy')
            ->assertSee('Record legacy tracciati');
    }

    public function test_admin_audit_mvp_page_post_seed(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('Audit & activity log')
            ->assertSee('Migrazione legacy');
    }
}
