<?php

namespace Tests\Feature\Sprint118;

use App\Domain\Infrastructure\HaFailoverDrillService;
use App\Http\Livewire\Admin\HaStatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HaFailoverDrillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
    }

    private function configureStagingDrill(): void
    {
        Config::set('app.env', 'staging');
        Config::set('session.driver', 'redis');
        Config::set('queue.default', 'redis');
        Config::set('infrastructure.ha.min_app_instances', 2);
        Config::set('infrastructure.ha.primary_app_url', 'https://app1.staging.test');
        Config::set('infrastructure.ha.secondary_app_url', 'https://app2.staging.test');
        Config::set('infrastructure.ha.last_failover_drill_at', now()->subMonth()->toDateString());
        Config::set('infrastructure.backup.schedule_enabled', true);
        Config::set('infrastructure.backup.storage_path', 's3://backups/test');
        Config::set('infrastructure.backup.last_drill_at', now()->subMonth()->toDateString());
    }

    public function test_failover_drill_checklist_includes_health_and_topology(): void
    {
        $keys = array_column(app(HaFailoverDrillService::class)->unifiedChecklist(), 'key');

        $this->assertContains('health_route', $keys);
        $this->assertContains('primary_url', $keys);
        $this->assertContains('secondary_url', $keys);
        $this->assertContains('failover_runbook', $keys);
    }

    public function test_can_run_drill_when_staging_fully_configured(): void
    {
        $this->configureStagingDrill();

        $drill = app(HaFailoverDrillService::class);

        $this->assertTrue($drill->canRunDrill());
        $this->assertTrue($drill->dryRunReport()['passed']);
    }

    public function test_probe_nodes_succeeds_with_http_fake(): void
    {
        $this->configureStagingDrill();

        Http::fake([
            'app1.staging.test/*' => Http::response('', 200),
            'app2.staging.test/*' => Http::response('', 200),
        ]);

        $probe = app(HaFailoverDrillService::class)->probeNodes();

        $this->assertTrue($probe['passed']);
        $this->assertTrue($probe['primary']['ok']);
        $this->assertTrue($probe['secondary']['ok']);
    }

    public function test_traffic_switch_and_recovery_phases_defined(): void
    {
        $drill = app(HaFailoverDrillService::class);

        $this->assertGreaterThanOrEqual(3, count($drill->trafficSwitchSteps()));
        $this->assertGreaterThanOrEqual(4, count($drill->recoveryChecklist()));
        $trafficDetail = $drill->trafficSwitchSteps()[0]['detail'];
        $this->assertStringContainsString('LB', $trafficDetail);
    }

    public function test_ha_failover_drill_command_outputs_report(): void
    {
        Config::set('app.env', 'staging');
        Config::set('infrastructure.ha.primary_app_url', '');

        $exitCode = Artisan::call('ha:failover-drill', ['--dry-run' => true]);

        $output = Artisan::output();
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('HA failover drill', $output);
        $this->assertStringContainsString('HA-FAILOVER-DRILL-RUNBOOK.md', $output);
        $this->assertStringContainsString('Fase 1 — Health', $output);
    }

    public function test_admin_ha_status_shows_failover_drill_section(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(HaStatusPage::class)
            ->assertSee('Failover drill')
            ->assertSee('ha-failover-drill')
            ->assertSee('ha:failover-drill')
            ->assertSee('HA-FAILOVER-DRILL-RUNBOOK.md')
            ->assertSee('Switch traffic');
    }

    public function test_failover_runbook_documents_phases_and_rollback(): void
    {
        $content = file_get_contents(base_path('docs/HA-FAILOVER-DRILL-RUNBOOK.md'));

        $this->assertStringContainsString('Fase 1', $content);
        $this->assertStringContainsString('Switch traffic', $content);
        $this->assertStringContainsString('Rollback post-drill', $content);
        $this->assertStringContainsString('ha:failover-drill', $content);
    }

    public function test_rollback_steps_include_lb_rebalance_and_timestamp(): void
    {
        $steps = app(HaFailoverDrillService::class)->rollbackSteps();

        $actions = array_column($steps, 'action');
        $this->assertTrue(
            collect($actions)->contains(fn (string $a): bool => str_contains($a, 'HA_LAST_FAILOVER_DRILL_AT')),
        );
        $this->assertStringContainsString('LB', $actions[0]);
    }

    public function test_fixture_documents_failover_drill_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/infrastructure/ha-failover-drill.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(118, $fixture['sprint']);
        $this->assertSame('GET /up', $fixture['health_endpoint']);
        $this->assertContains('recovery', $fixture['phases']);
    }

    public function test_production_requires_recent_failover_drill(): void
    {
        Config::set('app.env', 'production');
        Config::set('session.driver', 'redis');
        Config::set('queue.default', 'redis');
        Config::set('infrastructure.ha.primary_app_url', 'https://app1.prod.test');
        Config::set('infrastructure.ha.secondary_app_url', 'https://app2.prod.test');
        Config::set('infrastructure.ha.last_failover_drill_at', null);
        Config::set('infrastructure.backup.schedule_enabled', true);
        Config::set('infrastructure.backup.storage_path', 's3://backups/prod');
        Config::set('infrastructure.backup.last_drill_at', now()->subMonth()->toDateString());

        $this->assertFalse(app(HaFailoverDrillService::class)->canRunDrill());
    }
}
