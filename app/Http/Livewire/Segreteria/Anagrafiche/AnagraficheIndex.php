<?php

namespace App\Http\Livewire\Segreteria\Anagrafiche;

use App\Domain\Anagrafiche\AnagraficaService;
use App\Domain\Anagrafiche\AuthorizationAlertService;
use App\Domain\Anagrafiche\AuthorizationComplianceService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Anagrafica;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Anagrafiche')]
class AnagraficheIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $tipo = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Anagrafica::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTipo(): void
    {
        $this->resetPage();
    }

    public function delete(int $id, AnagraficaService $service): void
    {
        $anagrafica = $service->find($id);
        $this->authorize('delete', $anagrafica);
        $service->delete($anagrafica);
        session()->flash('success', 'Anagrafica eliminata.');
    }

    public function render(
        AnagraficaService $service,
        AuthorizationComplianceService $compliance,
        AuthorizationAlertService $authAlerts,
    ): View {
        $anagrafiche = $service->paginate([
            'search' => $this->search,
            'tipo' => $this->tipo,
        ]);

        return $this->segreteriaView(
            'livewire.segreteria.anagrafiche.index',
            [
                'anagrafiche' => $anagrafiche,
                'compliance' => $compliance,
                'authAlerts' => $authAlerts->summary(),
            ],
            'anagrafiche',
            'Anagrafiche',
        );
    }
}
