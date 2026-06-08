<?php

namespace Tests\Feature\Sprint48;

use App\Domain\Demo\DemoSeedService;
use App\Http\Livewire\Segreteria\Dashboard;
use App\Models\RentriSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class DemoWalkthroughUxTest extends TestCase
{
    public function test_dashboard_shows_progress_bar_and_cert_warning(): void
    {
        Config::set('demo.enabled', true);

        $settings = RentriSetting::instance();
        $settings->update([
            'cert_path_encrypted' => 'rentri/demo-cert.p12',
            'cert_scadenza'       => Carbon::now()->addDays(10),
        ]);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.dashboard'))
            ->assertOk()
            ->assertSee('Progresso walkthrough')
            ->assertSee('Certificato mTLS in scadenza')
            ->assertSee('Impostazioni certificato')
            ->assertSee('?step=2');
    }

    public function test_walkthrough_progress_counts_completed_steps(): void
    {
        Config::set('demo.enabled', true);

        $progress = app(DemoSeedService::class)->walkthroughProgress();

        $this->assertSame(6, $progress['total']);
        $this->assertGreaterThanOrEqual(0, $progress['completed']);
        $this->assertLessThanOrEqual(100, $progress['percent']);
    }

    public function test_walkthrough_steps_include_deep_links(): void
    {
        Config::set('demo.enabled', true);

        $steps = app(DemoSeedService::class)->walkthroughSteps();

        $this->assertStringContainsString('step=1', $steps[0]['href']);
        $this->assertStringContainsString('step=2', $steps[1]['href']);
        $this->assertArrayHasKey('mobile_href', $steps[3]);
        $this->assertSame(route('operatore.vetrina'), $steps[3]['mobile_href']);
    }

    public function test_livewire_dashboard_passes_progress_to_component(): void
    {
        Config::set('demo.enabled', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Progresso walkthrough');
    }
}
