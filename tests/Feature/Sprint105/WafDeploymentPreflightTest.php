<?php

namespace Tests\Feature\Sprint105;

use App\Domain\Security\WafDeploymentPreflightService;
use App\Http\Livewire\Admin\PenTestPrepPage;
use App\Http\Livewire\Admin\WafStatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WafDeploymentPreflightTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('segreteria');
    }

    public function test_waf_mode_off_by_default(): void
    {
        Config::set('waf.mode', 'off');

        $waf = app(WafDeploymentPreflightService::class);

        $this->assertSame('off', $waf->mode());
        $this->assertSame('Disattivato', $waf->modeLabel());
        $this->assertFalse($waf->isActive());
    }

    public function test_waf_monitor_and_block_modes(): void
    {
        Config::set('waf.mode', 'monitor');
        $waf = app(WafDeploymentPreflightService::class);
        $this->assertTrue($waf->isMonitorOnly());
        $this->assertFalse($waf->isBlockMode());

        Config::set('waf.mode', 'block');
        $waf = app(WafDeploymentPreflightService::class);
        $this->assertTrue($waf->isBlockMode());
        $this->assertFalse($waf->isMonitorOnly());
    }

    public function test_waf_protected_paths_include_stripe_livewire_admin(): void
    {
        $paths = app(WafDeploymentPreflightService::class)->protectedPaths();
        $keys = array_column($paths, 'key');

        $this->assertContains('stripe_webhook', $keys);
        $this->assertContains('livewire', $keys);
        $this->assertContains('admin_waf', $keys);
        $this->assertContains('admin_pen_test', $keys);

        $stripe = collect($paths)->firstWhere('key', 'stripe_webhook');
        $this->assertTrue($stripe['monitor']);
        $this->assertFalse($stripe['block']);
    }

    public function test_waf_rules_prep_documents_post_cycle_9_paths(): void
    {
        $content = file_get_contents(base_path('docs/WAF-RULES-PREP.md'));

        $this->assertStringContainsString('/webhooks/stripe/ecommerce', $content);
        $this->assertStringContainsString('/livewire/update', $content);
        $this->assertStringContainsString('/admin/waf-status', $content);
        $this->assertStringContainsString('WAF_MODE', $content);
        $this->assertStringContainsString('WAF non attivo', $content);
    }

    public function test_waf_staging_rollout_documents_48h_monitor_and_rollback(): void
    {
        $content = file_get_contents(base_path('docs/WAF-STAGING-ROLLOUT.md'));

        $this->assertStringContainsString('48h', $content);
        $this->assertStringContainsString('Rollback', $content);
        $this->assertStringContainsString('SIEM', $content);
        $this->assertStringContainsString('WAF_MODE=monitor', $content);
        $this->assertStringContainsString('WAF_MODE=block', $content);
    }

    public function test_admin_waf_status_page_shows_mode_badge(): void
    {
        Config::set('waf.mode', 'monitor');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(WafStatusPage::class)
            ->assertSee('WAF: Monitor-only')
            ->assertSee('waf-mode-badge')
            ->assertSee('/webhooks/stripe/ecommerce')
            ->assertSee('Pen-test OWASP prep');
    }

    public function test_admin_waf_status_denied_for_segreteria(): void
    {
        $user = User::factory()->create();
        $user->assignRole('segreteria');

        $this->actingAs($user)
            ->get(route('admin.waf-status'))
            ->assertForbidden();
    }

    public function test_pen_test_prep_links_to_waf_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(PenTestPrepPage::class)
            ->assertSee('WAF deploy status');
    }

    public function test_waf_ready_for_monitor_when_mode_and_docs_set(): void
    {
        Config::set('waf.mode', 'monitor');

        $waf = app(WafDeploymentPreflightService::class);

        $this->assertTrue($waf->isReadyForMonitorMode());
        $this->assertGreaterThanOrEqual(9, $waf->summary()['paths_count']);
    }

    public function test_waf_block_requires_siem_when_mode_block(): void
    {
        Config::set('waf.mode', 'block');
        Config::set('waf.siem_log_group', '');

        $waf = app(WafDeploymentPreflightService::class);

        $this->assertFalse($waf->isReadyForBlockMode());

        Config::set('waf.siem_log_group', '/aws/waf/rentri-crm-prod');
        $waf = app(WafDeploymentPreflightService::class);

        $this->assertTrue($waf->isReadyForBlockMode());
    }

    public function test_fixture_documents_waf_deployment_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/security/waf-deployment.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(105, $fixture['sprint']);
        $this->assertContains('monitor', $fixture['modes']);
        $this->assertContains('/livewire/update', $fixture['protected_paths']);
    }

    public function test_sprint_105_audit_notes_document_waf_service(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-105-AUDIT-NOTES.md'));

        $this->assertStringContainsString('WafDeploymentPreflightService', $content);
        $this->assertStringContainsString('WafStatusPage', $content);
    }
}
