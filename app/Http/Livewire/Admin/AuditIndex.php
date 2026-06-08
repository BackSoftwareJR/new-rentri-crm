<?php

namespace App\Http\Livewire\Admin;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Audit\AuditExportDownloadService;
use App\Domain\Audit\AuditExportLiveService;
use App\Domain\Dashboard\BusinessKpiAlertService;
use App\Models\AuditExportRun;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Title('Audit & activity log')]
class AuditIndex extends AdminPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $modulo = '';

    #[Url]
    public string $user_id = '';

    #[Url(as: 'da')]
    public string $data_da = '';

    #[Url(as: 'a')]
    public string $data_a = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Activity::class);
    }

    public function updatedModulo(): void
    {
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function updatedDataDa(): void
    {
        $this->resetPage();
    }

    public function updatedDataA(): void
    {
        $this->resetPage();
    }

    public function downloadExport(int $runId, AuditExportDownloadService $downloads)
    {
        $this->authorize('downloadExport', Activity::class);

        $run = AuditExportRun::query()->findOrFail($runId);
        $user = auth()->user();
        abort_unless($user !== null, 403);

        try {
            $url = $downloads->createDownloadUrl($run, $user);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return null;
        }

        return redirect()->away($url);
    }

    public function render(ActivityLogService $audit, AuditExportLiveService $exportLive, BusinessKpiAlertService $kpiAlerts): View
    {
        $filters = array_filter([
            'modulo'  => $this->modulo !== '' ? $this->modulo : null,
            'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
            'data_da' => $this->data_da !== '' ? $this->data_da : null,
            'data_a'  => $this->data_a !== '' ? $this->data_a : null,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->adminView(
            'livewire.admin.audit-index',
            [
                'activities'       => $audit->list($filters),
                'contatori'        => $audit->contatori($filters),
                'utenti'           => $audit->utentiConAttivita(),
                'service'          => $audit,
                'exportRuns'       => $exportLive->recentRuns(10),
                'exportDisk'       => $exportLive->diskName(),
                'businessKpiAlert' => $kpiAlerts->lastCheck(),
            ],
            'Audit & activity log',
            'Admin',
            'Audit & activity log',
        );
    }
}
