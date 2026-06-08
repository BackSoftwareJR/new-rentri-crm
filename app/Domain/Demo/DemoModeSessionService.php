<?php

namespace App\Domain\Demo;

use App\Domain\Audit\ActivityLogService;
use App\Support\Demo\DemoContext;
use App\Models\User;
use RuntimeException;

class DemoModeSessionService
{
    public function isSessionActive(): bool
    {
        return DemoContext::isSessionDemoActive();
    }

    public function canToggle(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'segreteria']);
    }

    public function canActivate(?User $user): bool
    {
        if (! $this->canToggle($user)) {
            return false;
        }

        if (DemoContext::isDeployDemo()) {
            return true;
        }

        if (config('app.env') === 'production' && ! config('demo.allow_session_toggle', false)) {
            return false;
        }

        return true;
    }

    public function activate(User $user): void
    {
        if (! $this->canActivate($user)) {
            throw new RuntimeException(
                'Impossibile attivare la palestra operativa: permessi insufficienti o ALLOW_SESSION_DEMO non abilitato in production.',
            );
        }

        session()->put(config('demo.session.key', 'demo_mode_active'), true);

        app(ActivityLogService::class)->record(
            'rentri',
            'Palestra operativa (demo) attivata — scope is_demo=true',
            properties: [
                'demo_source' => DemoContext::isDeployDemo() ? 'deploy_and_session' : 'session',
                'user_email'  => $user->email,
            ],
            userId: $user->id,
        );
    }

    public function deactivate(User $user): void
    {
        if (! $this->canToggle($user)) {
            throw new RuntimeException('Permessi insufficienti per disattivare la palestra operativa.');
        }

        session()->forget(config('demo.session.key', 'demo_mode_active'));

        app(ActivityLogService::class)->record(
            'rentri',
            'Palestra operativa (demo) disattivata — ripristinato scope produzione',
            properties: ['user_email' => $user->email],
            userId: $user->id,
        );
    }
}
