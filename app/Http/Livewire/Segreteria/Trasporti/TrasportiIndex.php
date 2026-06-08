<?php

namespace App\Http\Livewire\Segreteria\Trasporti;

use App\Domain\Trasporti\TrasportoGpsProductionSwitchService;
use App\Domain\Trasporti\TrasportoService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use App\Models\Trasporto;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Trasporti rifiuti')]
class TrasportiIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public ?int $codice_cer_id = null;

    #[Url]
    public string $stato = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Trasporto::class);
    }

    public function updatedCodiceCerId(): void
    {
        $this->resetPage();
    }

    public function updatedStato(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['codice_cer_id', 'stato', 'search']);
        $this->resetPage();
    }

    public function render(TrasportoService $trasporti): View
    {
        $filters = array_filter([
            'codice_cer_id' => $this->codice_cer_id,
            'stato'         => $this->stato !== '' ? $this->stato : null,
            'q'             => $this->search !== '' ? $this->search : null,
        ], fn ($v) => $v !== null && $v !== '');

        $lista = $trasporti->list($filters);
        $contatori = $trasporti->contatori($filters);

        $codiciCer = CodiceCer::query()
            ->where('attivo', true)
            ->orderBy('codice')
            ->get(['id', 'codice', 'descrizione']);

        $gpsSwitch = app(TrasportoGpsProductionSwitchService::class);

        return $this->segreteriaView(
            'livewire.segreteria.trasporti.index',
            [
                'trasporti'       => $lista,
                'contatori'       => $contatori,
                'codiciCer'       => $codiciCer,
                'service'         => $trasporti,
                'gpsSwitch'       => $gpsSwitch->summary(),
                'gpsChecklist'    => $gpsSwitch->unifiedChecklist(),
                'gpsPresets'      => $gpsSwitch->productionFieldMapPresets(),
                'gpsRollback'     => $gpsSwitch->rollbackSteps(),
            ],
            'trasporti',
            'Trasporti rifiuti',
        );
    }
}
