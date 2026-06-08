<?php

namespace App\Http\Livewire\Segreteria\Vfu;

use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuStoricoExportService;
use App\Enums\VfuStato;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Veicoli VFU')]
class VfuIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $stato = '';

    public function mount(): void
    {
        $this->authorize('viewAny', VfuRegistration::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStato(): void
    {
        $this->resetPage();
    }

    public function delete(int $id, VfuAccettazioneService $service): void
    {
        $vfu = $service->find($id);
        $this->authorize('delete', $vfu);
        $service->delete($vfu);
        session()->flash('success', 'Registrazione VFU eliminata.');
    }

    public function exportStoricoCsv(VfuStoricoExportService $export): StreamedResponse
    {
        $this->authorize('viewAny', VfuRegistration::class);

        $registrations = $export->filteredQuery([
            'search' => $this->search,
            'stato'  => $this->stato,
        ])->get();

        return $export->exportCsv($registrations);
    }

    public function render(VfuAccettazioneService $service): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.vfu.index',
            [
                'registrations' => $service->paginate([
                    'search' => $this->search,
                    'stato' => $this->stato,
                ]),
                'kpi' => $service->kpi(),
                'stati' => VfuStato::cases(),
            ],
            'vfu',
            'Veicoli VFU',
        );
    }
}
