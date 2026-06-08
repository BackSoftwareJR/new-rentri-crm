<?php

namespace Tests\Feature\Sprint46;

use App\Domain\Demo\DemoModeSessionService;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class DemoModeSessionServiceTest extends TestCase
{
    public function test_activate_and_deactivate_session_demo(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(DemoModeSessionService::class);

        $service->activate($user);
        $this->assertTrue($service->isSessionActive());

        $service->deactivate($user);
        $this->assertFalse($service->isSessionActive());
    }

    public function test_cannot_activate_on_production_without_allow_flag(): void
    {
        Config::set('demo.enabled', false);
        Config::set('demo.allow_session_toggle', false);
        Config::set('app.env', 'production');

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(DemoModeSessionService::class);

        $this->assertFalse($service->canActivate($user));

        $this->expectException(RuntimeException::class);
        $service->activate($user);
    }

    public function test_operatore_cannot_toggle(): void
    {
        Config::set('demo.allow_session_toggle', true);

        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $service = app(DemoModeSessionService::class);

        $this->assertFalse($service->canToggle($user));
    }

    public function test_activate_logs_activity(): void
    {
        Config::set('demo.allow_session_toggle', true);
        Config::set('activitylog.enabled', true);

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        app(DemoModeSessionService::class)->activate($user);

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'rentri',
            'description' => 'Palestra operativa (demo) attivata — scope is_demo=true',
        ]);
    }
}
