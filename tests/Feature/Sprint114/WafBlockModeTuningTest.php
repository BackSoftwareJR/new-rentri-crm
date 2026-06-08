<?php

namespace Tests\Feature\Sprint114;

use App\Domain\Security\PenTestRemediationService;
use App\Domain\Security\WafDeploymentPreflightService;
use App\Enums\PenTestFindingSeverity;
use App\Http\Livewire\Admin\WafStatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WafBlockModeTuningTest extends TestCase
{
    private string $findingsPath;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');

        $this->findingsPath = storage_path('framework/testing/pen_test_findings_'.uniqid('', true).'.json');
        Config::set('security.pen_test_findings_path', $this->findingsPath);
        app(PenTestRemediationService::class)->reset();
    }

    protected function tearDown(): void
    {
        if (is_file($this->findingsPath)) {
            unlink($this->findingsPath);
        }

        parent::tearDown();
    }

    public function test_production_block_checklist_includes_remediation_gates(): void
    {
        Config::set('waf.mode', 'block');
        Config::set('waf.siem_log_group', '/aws/waf/rentri-crm-prod');

        $checklist = app(WafDeploymentPreflightService::class)->productionBlockChecklist();
        $keys = array_column($checklist, 'key');

        $this->assertContains('zero_p0_remediation', $keys);
        $this->assertContains('waf_findings_tuned', $keys);
        $this->assertContains('waf_mode_block', $keys);
        $this->assertContains('stripe_webhook_exclusion', $keys);
    }

    public function test_paths_with_findings_cross_ref_maps_stripe_webhook_p1(): void
    {
        app(PenTestRemediationService::class)->create([
            'title'     => 'Webhook signature bypass attempt',
            'severity'  => PenTestFindingSeverity::P1->value,
            'asset_key' => 'stripe_webhook',
        ]);

        $paths = app(WafDeploymentPreflightService::class)->pathsWithFindingsCrossRef();
        $stripe = collect($paths)->firstWhere('key', 'stripe_webhook');

        $this->assertNotNull($stripe);
        $this->assertTrue($stripe['needs_tune']);
        $this->assertSame(1, $stripe['open_p1']);
        $this->assertSame('PT-001', $stripe['findings'][0]['id']);
    }

    public function test_production_block_not_ready_when_p0_open_on_waf_path(): void
    {
        Config::set('waf.mode', 'block');
        Config::set('waf.siem_log_group', '/aws/waf/rentri-crm-prod');

        app(PenTestRemediationService::class)->create([
            'title'     => 'Brute force login',
            'severity'  => PenTestFindingSeverity::P0->value,
            'asset_key' => 'login',
        ]);

        $waf = app(WafDeploymentPreflightService::class);

        $this->assertFalse($waf->isReadyForProductionBlockMode());
        $this->assertGreaterThan(0, $waf->summary()['open_p0_p1_on_waf_paths']);
    }

    public function test_production_block_ready_when_findings_closed_and_block_configured(): void
    {
        Config::set('waf.mode', 'block');
        Config::set('waf.siem_log_group', '/aws/waf/rentri-crm-prod');

        $waf = app(WafDeploymentPreflightService::class);

        $this->assertTrue($waf->isReadyForProductionBlockMode());
        $this->assertTrue($waf->summary()['ready_production_block']);
    }

    public function test_mode_toggle_guide_documents_monitor_and_block(): void
    {
        $guide = app(WafDeploymentPreflightService::class)->modeToggleGuide();
        $modes = array_column($guide, 'mode');

        $this->assertContains('monitor', $modes);
        $this->assertContains('block', $modes);
        $this->assertStringContainsString('WAF_MODE=block', collect($guide)->firstWhere('mode', 'block')['env']);
    }

    public function test_tuning_runbook_has_six_steps(): void
    {
        $steps = app(WafDeploymentPreflightService::class)->tuningRunbookSteps();

        $this->assertCount(6, $steps);
        $this->assertStringContainsString('finding', strtolower($steps[1]['action']));
    }

    public function test_waf_status_page_shows_tuning_and_findings_cross_ref(): void
    {
        Config::set('waf.mode', 'monitor');

        app(PenTestRemediationService::class)->create([
            'title'     => 'Livewire XSS stored',
            'severity'  => PenTestFindingSeverity::P1->value,
            'asset_key' => 'segreteria',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(WafStatusPage::class)
            ->assertSee('Toggle modalità WAF')
            ->assertSee('Runbook tuning block post-deploy')
            ->assertSee('Checklist block mode produzione')
            ->assertSee('Path protetti × findings P0/P1')
            ->assertSee('Livewire XSS stored')
            ->assertSee('Pen-test findings correlati');
    }

    public function test_waf_docs_document_sprint_114_findings_cross_ref(): void
    {
        $rules = file_get_contents(base_path('docs/WAF-RULES-PREP.md'));
        $rollout = file_get_contents(base_path('docs/WAF-STAGING-ROLLOUT.md'));

        $this->assertStringContainsString('Sprint 114', $rules);
        $this->assertStringContainsString('asset_key', $rules);
        $this->assertStringContainsString('findings P0/P1', $rollout);
    }

    public function test_deployment_checklist_includes_waf_path_findings_gate_in_block_mode(): void
    {
        Config::set('waf.mode', 'block');
        Config::set('waf.siem_log_group', '/aws/waf/rentri-crm-prod');

        app(PenTestRemediationService::class)->create([
            'title'     => 'Admin IDOR',
            'severity'  => PenTestFindingSeverity::P1->value,
            'asset_key' => 'admin_audit',
        ]);

        $checklist = app(WafDeploymentPreflightService::class)->deploymentChecklist();
        $gate = collect($checklist)->firstWhere('key', 'waf_paths_p0_p1_clear');

        $this->assertNotNull($gate);
        $this->assertFalse($gate['ok']);
    }
}
