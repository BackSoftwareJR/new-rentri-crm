<?php

namespace App\Http\Livewire\Admin;

use App\Domain\Infrastructure\HaBackupPreflightService;
use App\Domain\Infrastructure\HaFailoverDrillService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;

#[Title('HA — backup e failover')]
class HaStatusPage extends AdminPage
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }

    public function render(HaBackupPreflightService $ha, HaFailoverDrillService $drill): View
    {
        $summary = $ha->summary();
        $drillSummary = $drill->summary();

        return $this->adminView(
            'livewire.admin.ha-status',
            [
                'summary'            => $summary,
                'checklist'          => $ha->checklist(),
                'rpoRto'             => $ha->rpoRtoTargets(),
                'failoverSteps'      => $ha->failoverSteps(),
                'documents'          => $ha->documentPaths(),
                'readyBadge'         => $summary['ready']
                    ? 'seg-badge seg-badge-success'
                    : 'seg-badge seg-badge-warning',
                'drillSummary'       => $drillSummary,
                'drillChecklist'     => $drill->unifiedChecklist(),
                'drillHealthSteps'   => $drill->healthPhaseSteps(),
                'drillTrafficSteps'  => $drill->trafficSwitchSteps(),
                'drillRecovery'      => $drill->recoveryChecklist(),
                'drillRollback'      => $drill->rollbackSteps(),
            ],
            'HA backup',
            'Admin',
            'HA — backup e failover',
        );
    }
}
