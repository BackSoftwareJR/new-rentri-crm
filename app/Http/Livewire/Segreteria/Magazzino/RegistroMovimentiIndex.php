<?php

namespace App\Http\Livewire\Segreteria\Magazzino;

use App\Domain\Registro\RegistroMovimentiExportService;
use App\Domain\Registro\RegistroService;
use App\Domain\Rentri\RentriRegistroConformitaValidator;
use App\Exports\RegistroMovimentiExport;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use App\Models\RegistroMovimento;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Cache;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Registro movimenti')]
class RegistroMovimentiIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public ?int $codice_cer_id = null;

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $data_da = '';

    #[Url]
    public string $data_a = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RegistroMovimento::class);
    }

    public function updatedCodiceCerId(): void
    {
        $this->resetPage();
    }

    public function updatedTipo(): void
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

    public function resetFilters(): void
    {
        $this->reset(['codice_cer_id', 'tipo', 'data_da', 'data_a', 'search']);
        $this->resetPage();
    }

    public function exportCsv(RegistroMovimentiExportService $export): StreamedResponse
    {
        $this->authorize('exportAny', RegistroMovimento::class);

        return $export->exportCsv($this->currentFilters());
    }

    public function exportExcel(RegistroMovimentiExportService $export): BinaryFileResponse
    {
        $this->authorize('exportAny', RegistroMovimento::class);

        $filename = 'registro-movimenti-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(
            new RegistroMovimentiExport($export, $this->currentFilters()),
            $filename,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function currentFilters(): array
    {
        return array_filter([
            'codice_cer_id' => $this->codice_cer_id,
            'tipo' => $this->tipo !== '' ? $this->tipo : null,
            'data_da' => $this->data_da !== '' ? $this->data_da : null,
            'data_a' => $this->data_a !== '' ? $this->data_a : null,
            'q' => $this->search !== '' ? $this->search : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function render(RegistroService $registro, RentriRegistroConformitaValidator $conformitaValidator): View
    {
        $filters = $this->currentFilters();

        $movimenti = $registro->list($filters);
        $aggregazioni = $registro->aggregations($filters);

        $cacheKey = 'registro_mov_conformita_'.md5(json_encode([
            'filters' => $filters,
            'page'    => $this->getPage(),
            'ids'     => $movimenti->getCollection()->pluck('id')->all(),
        ]));

        $conformita = Cache::remember(
            $cacheKey,
            300,
            fn () => $conformitaValidator->batchMovimentoConformita($movimenti->getCollection()),
        );

        $codiciCer = CodiceCer::query()
            ->where('attivo', true)
            ->orderBy('codice')
            ->get(['id', 'codice', 'descrizione']);

        return $this->segreteriaView(
            'livewire.segreteria.magazzino.registro',
            [
                'movimenti' => $movimenti,
                'aggregazioni' => $aggregazioni,
                'codiciCer' => $codiciCer,
                'conformita' => $conformita,
            ],
            'registro-movimenti',
            'Registro movimenti',
        );
    }
}
