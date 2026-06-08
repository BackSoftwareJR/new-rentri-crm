<?php

namespace Tests\Feature\Sprint108;

use App\Domain\Infrastructure\HaBackupPreflightService;
use App\Http\Livewire\Admin\HaStatusPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HaBackupPreflightTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
    }

    public function test_ha_preflight_lists_backup_and_redis_items(): void
    {
        $keys = array_column(app(HaBackupPreflightService::class)->checklist(), 'key');

        $this->assertContains('backup_schedule', $keys);
        $this->assertContains('redis_session', $keys);
        $this->assertContains('restore_drill_runbook', $keys);
        $this->assertContains('rpo_rto_doc', $keys);
    }

    public function test_ha_ready_in_production_when_fully_configured(): void
    {
        Config::set('app.env', 'production');
        Config::set('session.driver', 'redis');
        Config::set('queue.default', 'redis');
        Config::set('infrastructure.backup.schedule_enabled', true);
        Config::set('infrastructure.backup.storage_path', 's3://backups/test');
        Config::set('infrastructure.backup.last_drill_at', now()->subMonth()->toDateString());

        $ha = app(HaBackupPreflightService::class);

        $this->assertTrue($ha->isReadyForHaProduction());
        $this->assertTrue($ha->summary()['redis_session']);
    }

    public function test_ha_not_ready_without_backup_in_production(): void
    {
        Config::set('app.env', 'production');
        Config::set('infrastructure.backup.schedule_enabled', false);

        $this->assertFalse(app(HaBackupPreflightService::class)->isReadyForHaProduction());
    }

    public function test_ha_backup_runbook_documents_rpo_rto_and_quarterly_drill(): void
    {
        $content = file_get_contents(base_path('docs/HA-BACKUP-DRILL-RUNBOOK.md'));

        $this->assertStringContainsString('RPO', $content);
        $this->assertStringContainsString('RTO', $content);
        $this->assertStringContainsString('trimestrale', $content);
        $this->assertStringContainsString('Failover', $content);
    }

    public function test_redis_session_prep_documents_multi_instance(): void
    {
        $content = file_get_contents(base_path('docs/REDIS-SESSION-PREP.md'));

        $this->assertStringContainsString('Multi-istanza HA', $content);
        $this->assertStringContainsString('HaBackupPreflightService', $content);
        $this->assertStringContainsString('load balancer', $content);
    }

    public function test_admin_ha_status_page_renders_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(HaStatusPage::class)
            ->assertSee('HA multi-istanza')
            ->assertSee('ha-ready-badge')
            ->assertSee('Failover rapido')
            ->assertSee('HA-BACKUP-DRILL-RUNBOOK.md');
    }

    public function test_admin_ha_status_denied_for_segreteria(): void
    {
        $user = User::factory()->create();
        $user->assignRole('segreteria');

        $this->actingAs($user)
            ->get(route('admin.ha-status'))
            ->assertForbidden();
    }

    public function test_failover_steps_defined(): void
    {
        $steps = app(HaBackupPreflightService::class)->failoverSteps();

        $this->assertGreaterThanOrEqual(3, count($steps));
        $this->assertStringContainsString('Redis', $steps[1]['action']);
    }

    public function test_fixture_documents_ha_backup_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/infrastructure/ha-backup-drill.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(108, $fixture['sprint']);
        $this->assertSame('redis', $fixture['session_driver_production']);
        $this->assertSame(60, $fixture['rpo_minutes']);
    }

    public function test_sprint_108_audit_notes_document_ha_service(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-108-AUDIT-NOTES.md'));

        $this->assertStringContainsString('HaBackupPreflightService', $content);
        $this->assertStringContainsString('HaStatusPage', $content);
    }
}
