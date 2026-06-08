<?php

namespace App\Http\Livewire\Segreteria\Rentri;

use App\Domain\Rentri\RentriTransazioneService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\RentriTransazione;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Storico transazioni RENTRI')]
class RentriTransazioniIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $tipo_api = '';

    #[Url]
    public string $stato = '';

    #[Url(as: 'da')]
    public string $data_da = '';

    #[Url(as: 'a')]
    public string $data_a = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RentriTransazione::class);
    }

    public function updatedTipoApi(): void
    {
        $this->resetPage();
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

    public function render(RentriTransazioneService $service): View
    {
        $filters = array_filter([
            'tipo_api' => $this->tipo_api !== '' ? $this->tipo_api : null,
            'stato'    => $this->stato !== '' ? $this->stato : null,
            'data_da'  => $this->data_da !== '' ? $this->data_da : null,
            'data_a'   => $this->data_a !== '' ? $this->data_a : null,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->segreteriaView(
            'livewire.segreteria.rentri.transazioni-index',
            [
                'transazioni' => $service->list($filters),
                'contatori'   => $service->contatori($filters),
                'service'     => $service,
            ],
            'rentri',
            'Transazioni API RENTRI',
        );
    }
}
