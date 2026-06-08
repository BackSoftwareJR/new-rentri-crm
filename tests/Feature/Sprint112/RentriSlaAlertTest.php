<?php

namespace Tests\Feature\Sprint112;

use App\Domain\Rentri\RentriSlaAlertService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Segreteria\Rentri;
use App\Mail\RentriSlaBreachMail;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RentriSlaAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(RentriSlaAlertService::CACHE_KEY_LAST_CHECK);
        Cache::forget(RentriSlaAlertService::CACHE_KEY_LAST_RUN_AT);
    }

    public function test_alert_service_detects_p95_latency_breach(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 10,
            'dead_letter_rate_percent' => 99.0,
            'max_avg_retry_count'      => 99.0,
        ]);

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(60)])->save();

        $result = app(RentriSlaAlertService::class)->check(7, notify: false);

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['breaches'])->contains(
            fn (array $b) => $b['key'] === 'p95_latency' && $b['status'] === 'fail',
        ));
        $this->assertNotNull(app(RentriSlaAlertService::class)->lastCheck());
    }

    public function test_alert_service_detects_dead_letter_rate_breach(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 999,
            'dead_letter_rate_percent' => 1.0,
            'max_avg_retry_count'      => 99.0,
        ]);

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'registro',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/registro'],
            'response_json'  => ['error' => true],
            'dead_letter_at' => now(),
            'completed_at'   => now(),
        ]);

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'registro',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/registro'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);

        $result = app(RentriSlaAlertService::class)->check(7, notify: false);

        $this->assertSame('fail', $result['overall']);
        $this->assertTrue(collect($result['breaches'])->contains(
            fn (array $b) => $b['key'] === 'dead_letter_rate' && $b['status'] === 'fail',
        ));
    }

    public function test_sla_check_command_notifies_on_fail_breach(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 5,
            'dead_letter_rate_percent' => 99.0,
            'max_avg_retry_count'      => 99.0,
        ]);
        Config::set('notifications.live', true);

        Mail::fake();

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(30)])->save();

        $exit = Artisan::call('rentri:sla-check', ['--notify' => true]);

        $this->assertSame(1, $exit);
        Mail::assertSent(RentriSlaBreachMail::class);
    }

    public function test_sla_check_command_json_output_includes_breaches(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 5,
            'dead_letter_rate_percent' => 99.0,
            'max_avg_retry_count'      => 99.0,
        ]);

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(30)])->save();

        Artisan::call('rentri:sla-check', ['--json' => true]);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertIsArray($decoded);
        $this->assertSame('fail', $decoded['overall']);
        $this->assertArrayHasKey('breaches', $decoded);
        $this->assertArrayHasKey('metrics', $decoded);
        $this->assertArrayHasKey('checked_at', $decoded);
    }

    public function test_sla_check_records_breach_in_activity_log(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 5,
            'dead_letter_rate_percent' => 99.0,
            'max_avg_retry_count'      => 99.0,
        ]);

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(30)])->save();

        app(RentriSlaAlertService::class)->check(7, notify: false);

        $breaches = app(RentriSlaAlertService::class)->recentBreaches(5);

        $this->assertNotEmpty($breaches);
        $this->assertStringContainsString('SLA breach:', $breaches[0]['description']);
        $this->assertSame('sla_breach', $breaches[0]['properties']['event'] ?? null);
    }

    public function test_rentri_hub_shows_sla_automation_last_check_and_breaches(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 5,
            'dead_letter_rate_percent' => 99.0,
            'max_avg_retry_count'      => 99.0,
        ]);

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(30)])->save();

        app(RentriSlaAlertService::class)->check(7, notify: false);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('Automazione SLA (cron)')
            ->assertSee('Ultimo check:')
            ->assertSee('FAIL')
            ->assertSee('Ultimi breach (activity log)')
            ->assertSee('SLA breach:');
    }

    public function test_monitoring_doc_documents_rentri_sla_check_command(): void
    {
        $content = file_get_contents(base_path('docs/MONITORING-CICLO-3.md'));

        $this->assertStringContainsString('rentri:sla-check', $content);
        $this->assertStringContainsString('--notify', $content);
        $this->assertStringContainsString('RENTRI_SLA_', $content);
    }

    public function test_notification_event_rentri_sla_breach_is_registered(): void
    {
        $this->assertSame('rentri.sla_breach', NotificationEvent::RentriSlaBreach->value);
        $this->assertSame('RENTRI SLA fuori soglia', NotificationEvent::RentriSlaBreach->label());
        $events = config('notifications.events');

        $this->assertTrue($events[NotificationEvent::RentriSlaBreach->value]['enabled'] ?? false);
    }

    public function test_console_schedule_includes_hourly_sla_check(): void
    {
        $content = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString("Schedule::command('rentri:sla-check --notify')", $content);
        $this->assertStringContainsString('->hourly()', $content);
    }
}
