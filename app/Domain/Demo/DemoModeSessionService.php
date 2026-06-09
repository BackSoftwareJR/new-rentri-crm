<?php

namespace App\Domain\Demo;

use App\Domain\Audit\ActivityLogService;
use App\Jobs\RentriInitialSyncJob;
use App\Models\RentriSetting;
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

        $this->prepareRentriSandboxScope();

        app(ActivityLogService::class)->record(
            'rentri',
            'Palestra operativa (demo) attivata — scope is_demo=true, API demoapi.rentri.gov.it',
            properties: [
                'demo_source'      => DemoContext::isDeployDemo() ? 'deploy_and_session' : 'session',
                'live_sandbox'     => DemoContext::usesLiveSandboxApi(),
                'user_email'       => $user->email,
            ],
            userId: $user->id,
        );
    }

    private function prepareRentriSandboxScope(): void
    {
        $settings = RentriSetting::instance();

        $settings->update([
            'ambiente'              => 'sandbox',
            'live_mode_enabled_at'  => null,
            'firma_live_enabled_at' => null,
        ]);

        if (! DemoContext::usesLiveSandboxApi()) {
            return;
        }

        if (blank($settings->fresh()->cert_path_encrypted)) {
            session()->flash(
                'warning',
                'Palestra attiva: collegamento a demoapi.rentri.gov.it. Carica il certificato sandbox in Impostazioni RENTRI per sincronizzare CER, blocchi FIR e vidima.',
            );

            return;
        }

        RentriInitialSyncJob::dispatch();

        session()->flash(
            'success',
            'Palestra attiva: sincronizzazione CER e blocchi FIR da demoapi.rentri.gov.it avviata in background.',
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
