<?php

namespace App\Http\Livewire\Segreteria\Fatturazione;

use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Fattura;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Fatturazione')]
class FattureIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $stato = '';

    #[Url]
    public string $tipo = '';

    #[Url]
    public string $dataDa = '';

    #[Url]
    public string $dataA = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Fattura::class);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStato(): void  { $this->resetPage(); }
    public function updatedTipo(): void   { $this->resetPage(); }
    public function updatedDataDa(): void { $this->resetPage(); }
    public function updatedDataA(): void  { $this->resetPage(); }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Fattura::class);

        $fatture = $this->queryBase()->with('anagrafica')->get();

        $filename = 'fatture-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($fatture) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['numero_fattura', 'data_emissione', 'data_scadenza', 'anagrafica', 'totale', 'stato'], ';');
            foreach ($fatture as $f) {
                fputcsv($handle, [
                    $f->numero_fattura,
                    $f->data_emissione?->format('d/m/Y'),
                    $f->data_scadenza?->format('d/m/Y') ?? '',
                    $f->anagrafica?->ragione_sociale ?? '-',
                    number_format((float) $f->totale, 2, ',', '.'),
                    $f->statoLabel(),
                ], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryBase()
    {
        $query = Fattura::query()->forActiveSito()->with('anagrafica');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('numero_fattura', 'like', "%{$this->search}%")
                  ->orWhereHas('anagrafica', fn ($a) => $a->where('ragione_sociale', 'like', "%{$this->search}%"));
            });
        }

        if ($this->stato !== '') {
            $query->where('stato', $this->stato);
        }

        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }

        if ($this->dataDa !== '') {
            $query->where('data_emissione', '>=', $this->dataDa);
        }

        if ($this->dataA !== '') {
            $query->where('data_emissione', '<=', $this->dataA);
        }

        return $query->orderByDesc('data_emissione')->orderByDesc('id');
    }

    #[Computed(persist: true, seconds: 300)]
    public function riepilogo(): array
    {
        $row = Fattura::query()
            ->forActiveSito()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN stato = 'emessa' THEN totale ELSE 0 END), 0) as emesse,
                COALESCE(SUM(CASE WHEN stato = 'pagata' THEN totale ELSE 0 END), 0) as pagate,
                COALESCE(SUM(CASE WHEN stato = 'scaduta' THEN totale ELSE 0 END), 0) as scadute
            ")
            ->first();

        return [
            'emesse'  => (float) ($row->emesse ?? 0),
            'pagate'  => (float) ($row->pagate ?? 0),
            'scadute' => (float) ($row->scadute ?? 0),
        ];
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.fatturazione.fatture-index',
            [
                'fatture'   => $this->queryBase()->paginate(20),
                'riepilogo' => $this->riepilogo,
            ],
            'fatturazione',
            'Fatturazione',
        );
    }
}
