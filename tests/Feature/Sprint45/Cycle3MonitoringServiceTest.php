<?php

namespace Tests\Feature\Sprint45;

use App\Domain\Deploy\Cycle3MonitoringService;
use App\Models\RentriTransazione;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class Cycle3MonitoringServiceTest extends TestCase
{
    public function test_snapshot_reports_ok_health_and_no_alerts_when_clean(): void
    {
        $snapshot = app(Cycle3MonitoringService::class)->snapshot();

        $this->assertSame('ok', $snapshot['framework_health']['status']);
        $this->assertSame(200, $snapshot['framework_health']['http_code']);
        $this->assertSame([], $snapshot['alerts']);
        $this->assertArrayHasKey('dead_letter', $snapshot['rentri']);
    }

    public function test_snapshot_alerts_on_dead_letter_transactions(): void
    {
        RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => 'fir',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/fir'],
            'response_json'  => ['error' => true],
            'dead_letter_at' => now(),
            'completed_at'   => now(),
        ]);

        $snapshot = app(Cycle3MonitoringService::class)->snapshot();

        $this->assertGreaterThanOrEqual(1, $snapshot['rentri']['dead_letter']);
        $this->assertTrue(collect($snapshot['alerts'])->contains(
            fn ($a) => $a['code'] === 'rentri_dead_letter' && $a['level'] === 'critical',
        ));
    }

    public function test_snapshot_alerts_when_demo_mode_on_production_env(): void
    {
        Config::set('demo.enabled', true);
        Config::set('app.env', 'production');

        $snapshot = app(Cycle3MonitoringService::class)->snapshot();

        $this->assertTrue(collect($snapshot['alerts'])->contains(
            fn ($a) => $a['code'] === 'demo_on_production_env' && $a['level'] === 'critical',
        ));
    }

    public function test_health_endpoint_constant_matches_laravel_route(): void
    {
        $this->assertSame('/up', Cycle3MonitoringService::HEALTH_ENDPOINT);
    }
}
