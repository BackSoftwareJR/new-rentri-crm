<?php

namespace App\Http\Livewire\Segreteria\Fir;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Domain\Fir\FirBulkExportService;
use App\Domain\Fir\FirService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Fir;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Formulari FIR')]
class FirIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $stato = '';

    #[Url]
    public string $data_da = '';

    #[Url]
    public string $data_a = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Fir::class);
    }

    public function updatedStato(): void
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function exportBulkCsv(FirBulkExportService $export): StreamedResponse
    {
        $this->authorize('exportAny', Fir::class);

        return $export->exportCsv($this->currentFilters());
    }

    /**
     * @return array<string, mixed>
     */
    private function currentFilters(): array
    {
        return array_filter([
            'stato'    => $this->stato !== '' ? $this->stato : null,
            'data_da'  => $this->data_da !== '' ? $this->data_da : null,
            'data_a'   => $this->data_a !== '' ? $this->data_a : null,
            'q'        => $this->search !== '' ? $this->search : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function render(FirService $fir, RentriRuntimeModeService $runtimeMode): View
    {
        $filters = $this->currentFilters();

        return $this->segreteriaView(
            'livewire.segreteria.fir.index',
            [
                'firs'               => $fir->list($filters),
                'contatori'          => $fir->contatori($filters),
                'firService'         => $fir,
                'rentriApiModeLabel' => $runtimeMode->apiModeDisplayLabel(),
            ],
            'fir',
            'Formulari FIR',
        );
    }
}
