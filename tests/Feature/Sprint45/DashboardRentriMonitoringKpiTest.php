<?php

namespace Tests\Feature\Sprint45;

use App\Domain\Dashboard\DashboardKpiService;
use App\Models\RentriTransazione;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardRentriMonitoringKpiTest extends TestCase
{
    public function test_dashboard_kpi_includes_dead_letter_and_retry_counters(): void
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
            'tipo_api'       => 'xfir',
            'stato'          => 'errore',
            'request_json'   => ['method' => 'POST', 'endpoint' => '/xfir'],
            'response_json'  => ['error' => true],
            'next_retry_at'  => now()->addHour(),
            'completed_at'   => now(),
        ]);

        $kpi = app(DashboardKpiService::class)->aggregate();

        $this->assertGreaterThanOrEqual(1, $kpi['rentri_dead_letter']);
        $this->assertGreaterThanOrEqual(1, $kpi['rentri_retry_pianificati']);
    }

    public function test_dashboard_ui_shows_dead_letter_kpi(): void
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

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Dead-letter RENTRI')
            ->assertSee('Retry pianificati');
    }
}
