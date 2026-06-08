<?php

namespace Tests\Feature\Sprint93;

use App\Domain\Rentri\RentriSlaMetricsService;
use App\Http\Livewire\Segreteria\Rentri;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RentriSlaMetricsTest extends TestCase
{
    public function test_sla_service_returns_empty_metrics_for_period_without_transactions(): void
    {
        $metrics = app(RentriSlaMetricsService::class)->periodMetrics(7);

        $this->assertSame(0, $metrics['totale']);
        $this->assertSame(0, $metrics['latency']['sample_size']);
        $this->assertSame(0.0, $metrics['dead_letter']['rate_percent']);
        $this->assertSame('ok', $metrics['sla']['overall']);
    }

    public function test_sla_service_computes_latency_p95_and_retry_trend(): void
    {
        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir/vidima'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
            'retry_count'    => 2,
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(100)])->save();

        $fast = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'xfir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/xfir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
            'retry_count'    => 0,
        ]);
        $fast->forceFill(['created_at' => now()->subSeconds(20)])->save();

        $metrics = app(RentriSlaMetricsService::class)->periodMetrics(7);

        $this->assertSame(2, $metrics['totale']);
        $this->assertSame(100.0, $metrics['latency']['p95_seconds']);
        $this->assertSame(1.0, $metrics['retry']['avg_count']);
        $this->assertSame(1, $metrics['retry']['with_retry']);
    }

    public function test_sla_service_reports_dead_letter_rate_by_tipo(): void
    {
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
            'created_at'     => now()->subMinute(),
            'completed_at'   => now(),
        ]);

        $metrics = app(RentriSlaMetricsService::class)->periodMetrics(7);

        $this->assertSame(50.0, $metrics['by_tipo']['registro']['dead_letter']['rate_percent']);
        $this->assertSame(50.0, $metrics['dead_letter']['rate_percent']);
    }

    public function test_sla_service_marks_fail_when_thresholds_exceeded(): void
    {
        Config::set('services.rentri.sla', [
            'p95_latency_seconds'      => 10,
            'dead_letter_rate_percent' => 1.0,
            'max_avg_retry_count'      => 0.5,
        ]);

        $slow = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
            'retry_count'    => 2,
        ]);
        $slow->forceFill(['created_at' => now()->subSeconds(60)])->save();

        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['error' => true],
            'dead_letter_at' => now(),
            'completed_at'   => now(),
        ]);

        $metrics = app(RentriSlaMetricsService::class)->periodMetrics(7);

        $this->assertSame('fail', $metrics['sla']['p95_latency']);
        $this->assertSame('fail', $metrics['sla']['dead_letter_rate']);
        $this->assertSame('fail', $metrics['sla']['avg_retry']);
        $this->assertSame('fail', $metrics['sla']['overall']);
    }

    public function test_dashboard_includes_both_7_and_30_day_periods(): void
    {
        $dashboard = app(RentriSlaMetricsService::class)->dashboard(30);

        $this->assertArrayHasKey(7, $dashboard['periods']);
        $this->assertArrayHasKey(30, $dashboard['periods']);
        $this->assertSame(30, $dashboard['selected_days']);
    }

    public function test_rentri_hub_shows_sla_section(): void
    {
        $tx = RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'completata',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['ok' => true],
            'completed_at'   => now(),
        ]);
        $tx->forceFill(['created_at' => now()->subMinute()])->save();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->assertSee('SLA transazioni API')
            ->assertSee('Ultimi 7 giorni')
            ->assertSee('p95 latency')
            ->assertSee('FIR vidima');
    }

    public function test_rentri_hub_switches_sla_period_to_30_days(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Rentri::class)
            ->set('slaPeriodDays', 30)
            ->assertSet('slaPeriodDays', 30)
            ->assertSee('Ultimi 30 giorni');
    }

    public function test_env_example_documents_rentri_sla_thresholds(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('RENTRI_SLA_P95_LATENCY_SECONDS', $env);
        $this->assertStringContainsString('RENTRI_SLA_DEAD_LETTER_RATE_PERCENT', $env);
        $this->assertStringContainsString('RENTRI_SLA_MAX_AVG_RETRY_COUNT', $env);
    }
}
