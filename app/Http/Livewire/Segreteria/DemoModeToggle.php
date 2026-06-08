<?php

namespace App\Http\Livewire\Segreteria;

use App\Domain\Demo\DemoModeSessionService;
use App\Support\Demo\DemoContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class DemoModeToggle extends Component
{
    use AuthorizesRequests;
    public bool $showConfirmActivate = false;

    public function requestActivate(): void
    {
        $this->showConfirmActivate = true;
    }

    public function cancelActivate(): void
    {
        $this->showConfirmActivate = false;
    }

    public function confirmActivate(DemoModeSessionService $demoSession): void
    {
        $this->authorize('demo.toggle');

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $demoSession->activate($user);
        $this->showConfirmActivate = false;

        $this->redirect(request()->header('Referer', route('segreteria.dashboard')), navigate: true);
    }

    public function deactivate(DemoModeSessionService $demoSession): void
    {
        $this->authorize('demo.toggle');

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $demoSession->deactivate($user);

        $this->redirect(request()->header('Referer', route('segreteria.dashboard')), navigate: true);
    }

    public function render(DemoModeSessionService $demoSession): View
    {
        $user = auth()->user();

        return view('livewire.segreteria.demo-mode-toggle', [
            'canToggle'    => $demoSession->canToggle($user),
            'canActivate'  => $demoSession->canActivate($user),
            'sessionDemo'  => DemoContext::isSessionDemoActive(),
            'deployDemo'   => DemoContext::isDeployDemo(),
            'demoActive'   => DemoContext::isActive(),
        ]);
    }
}
