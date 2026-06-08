<?php

namespace App\Http\Middleware;

use App\Domain\Demo\DemoModeSessionService;
use App\Support\Demo\DemoContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoModeScope
{
    public function __construct(
        private readonly DemoModeSessionService $demoSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (DemoContext::isSessionDemoActive() && ! $this->demoSession->canToggle($request->user())) {
            session()->forget(config('demo.session.key', 'demo_mode_active'));
        }

        return $next($request);
    }
}
