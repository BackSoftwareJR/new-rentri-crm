<?php

namespace Tests\Feature\Sprint107;

use App\Domain\Infrastructure\HorizonScalingPreflightService;
use App\Domain\Notifications\SmtpVolumePreflightService;
use App\Http\Livewire\Settings\NotificationSettingsPage;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class HorizonSmtpVolumePreflightTest extends TestCase
{
    public function test_horizon_preflight_lists_queue_and_workers(): void
    {
        Config::set('queue.default', 'database');
        Config::set('notifications.queue', false);

        $preflight = app(HorizonScalingPreflightService::class);
        $keys = array_column($preflight->checklist(), 'key');

        $this->assertContains('horizon_installed', $keys);
        $this->assertContains('notifications_queue', $keys);
        $this->assertContains('worker_processes', $keys);
        $this->assertContains('failed_jobs_clear', $keys);
        $this->assertGreaterThanOrEqual(1, $preflight->maxWorkerProcesses());
    }

    public function test_horizon_ready_when_redis_and_queue_enabled_in_production(): void
    {
        Config::set('app.env', 'production');
        Config::set('queue.default', 'redis');
        Config::set('notifications.live', true);
        Config::set('notifications.queue', true);
        Config::set('horizon.environments.production.supervisor-1.maxProcesses', 10);

        $preflight = app(HorizonScalingPreflightService::class);

        $this->assertTrue($preflight->isReadyForProductionVolume());
        $this->assertSame('redis', $preflight->summary()['queue_connection']);
    }

    public function test_smtp_volume_preflight_stub_mode_single_item(): void
    {
        Config::set('notifications.live', false);

        $checklist = app(SmtpVolumePreflightService::class)->checklist();

        $this->assertCount(1, $checklist);
        $this->assertTrue($checklist[0]['ok']);
        $this->assertTrue(app(SmtpVolumePreflightService::class)->isReadyForProductionVolume());
    }

    public function test_smtp_volume_preflight_live_requires_queue_and_mail(): void
    {
        Config::set('notifications.live', true);
        Config::set('notifications.queue', false);
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.from.address', 'noreply@example.test');

        $smtp = app(SmtpVolumePreflightService::class);

        $this->assertFalse($smtp->isReadyForProductionVolume());

        Config::set('notifications.queue', true);
        $smtp = app(SmtpVolumePreflightService::class);

        $this->assertTrue($smtp->isReadyForProductionVolume());
    }

    public function test_horizon_scaling_runbook_documents_smtp_rate_limits(): void
    {
        $content = file_get_contents(base_path('docs/HORIZON-SCALING-RUNBOOK.md'));

        $this->assertStringContainsString('NOTIFICATIONS_QUEUE', $content);
        $this->assertStringContainsString('rate', $content);
        $this->assertStringContainsString('SMTP', $content);
        $this->assertStringContainsString('maxProcesses', $content);
    }

    public function test_notification_settings_shows_horizon_and_smtp_badges(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationSettingsPage::class)
            ->assertSee('horizon-scaling-badge')
            ->assertSee('smtp-volume-badge')
            ->assertSee('Horizon / queue scaling')
            ->assertSee('SMTP volume')
            ->assertSee('HORIZON-SCALING-RUNBOOK.md');
    }

    public function test_monitoring_ciclo_3_documents_smtp_volume_section(): void
    {
        $content = file_get_contents(base_path('docs/MONITORING-CICLO-3.md'));

        $this->assertStringContainsString('SMTP volume', $content);
        $this->assertStringContainsString('HorizonScalingPreflightService', $content);
        $this->assertStringContainsString('NOTIFICATIONS_SMTP_DAILY_CAP', $content);
    }

    public function test_fixture_documents_horizon_smtp_contract(): void
    {
        $fixture = json_decode(
            file_get_contents(base_path('tests/fixtures/infrastructure/horizon-smtp-volume.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(107, $fixture['sprint']);
        $this->assertSame('redis', $fixture['horizon']['queue_connection']);
        $this->assertTrue($fixture['horizon']['notifications_queue']);
    }

    public function test_smtp_daily_cap_optional_in_config(): void
    {
        Config::set('notifications.smtp_daily_cap', 500);

        $summary = app(SmtpVolumePreflightService::class)->summary();

        $this->assertSame(500, $summary['daily_cap']);
    }

    public function test_sprint_107_audit_notes_document_services(): void
    {
        $content = file_get_contents(base_path('docs/SPRINT-107-AUDIT-NOTES.md'));

        $this->assertStringContainsString('HorizonScalingPreflightService', $content);
        $this->assertStringContainsString('SmtpVolumePreflightService', $content);
    }
}
