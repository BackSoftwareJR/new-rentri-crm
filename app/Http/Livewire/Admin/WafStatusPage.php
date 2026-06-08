<?php

namespace App\Http\Livewire\Admin;

use App\Domain\Security\WafDeploymentPreflightService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;

#[Title('WAF — stato deploy')]
class WafStatusPage extends AdminPage
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }

    public function render(WafDeploymentPreflightService $waf): View
    {
        if (! $waf->isReadyForProductionBlockMode()) {
            $waf->logProductionBlockFailuresOnce();
        }

        return $this->adminView(
            'livewire.admin.waf-status',
            [
                'summary'              => $waf->summary(),
                'checklist'            => $waf->deploymentChecklist(),
                'productionBlock'      => $waf->productionBlockChecklist(),
                'modeGuide'            => $waf->modeToggleGuide(),
                'tuningSteps'          => $waf->tuningRunbookSteps(),
                'pathsWithFindings'    => $waf->pathsWithFindingsCrossRef(),
                'paths'                => $waf->protectedPaths(),
                'siem'                 => $waf->siemChecklist(),
                'documents'            => $waf->documentPaths(),
                'modeBadge'            => $this->modeBadgeClass($waf->mode()),
                'currentMode'          => $waf->mode(),
            ],
            'WAF deploy',
            'Admin',
            'WAF — stato deploy',
        );
    }

    private function modeBadgeClass(string $mode): string
    {
        return match ($mode) {
            'monitor' => 'seg-badge seg-badge-warning',
            'block'   => 'seg-badge seg-badge-success',
            default   => 'seg-badge seg-badge-muted',
        };
    }
}
