<?php

namespace App\Http\Livewire\Admin;

use App\Domain\Logging\ApplicationLogQueryService;
use App\Models\ApplicationLog;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Log applicativi')]
class LogsIndex extends AdminPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $module = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $trace_id = '';

    #[Url]
    public string $demo = '';

    #[Url(as: 'da')]
    public string $data_da = '';

    #[Url(as: 'a')]
    public string $data_a = '';

    public ?int $selectedId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ApplicationLog::class);
    }

    public function updatedModule(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedTraceId(): void
    {
        $this->resetPage();
    }

    public function updatedDemo(): void
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

    public function showDetail(int $id): void
    {
        $this->authorize('view', ApplicationLog::query()->findOrFail($id));
        $this->selectedId = $id;
    }

    public function closeDetail(): void
    {
        $this->selectedId = null;
    }

    public function render(ApplicationLogQueryService $logs): View
    {
        $filters = $this->buildFilters();
        $selected = $this->selectedId !== null ? $logs->find($this->selectedId) : null;

        return $this->adminView(
            'livewire.admin.logs-index',
            [
                'entries'    => $logs->list($filters),
                'contatori'  => $logs->contatori($filters),
                'service'    => $logs,
                'modules'    => config('application_log.modules', []),
                'levels'     => config('application_log.levels', []),
                'selected'   => $selected,
                'exportUrl'  => route('admin.logs.export', array_filter([
                    'module'   => $this->module !== '' ? $this->module : null,
                    'level'    => $this->level !== '' ? $this->level : null,
                    'trace_id' => $this->trace_id !== '' ? $this->trace_id : null,
                    'demo'     => $this->demo !== '' ? $this->demo : null,
                    'data_da'  => $this->data_da !== '' ? $this->data_da : null,
                    'data_a'   => $this->data_a !== '' ? $this->data_a : null,
                ], fn ($v) => $v !== null && $v !== '')),
            ],
            'Log applicativi',
            'Admin',
            'Log applicativi',
            'logs',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilters(): array
    {
        return array_filter([
            'module'   => $this->module !== '' ? $this->module : null,
            'level'    => $this->level !== '' ? $this->level : null,
            'trace_id' => $this->trace_id !== '' ? $this->trace_id : null,
            'demo'     => $this->demo !== '' ? $this->demo : null,
            'data_da'  => $this->data_da !== '' ? $this->data_da : null,
            'data_a'   => $this->data_a !== '' ? $this->data_a : null,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
