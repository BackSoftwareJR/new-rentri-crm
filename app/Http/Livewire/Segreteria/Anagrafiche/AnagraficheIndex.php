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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportCsv(AnagraficaService $service): StreamedResponse
    {
        $this->authorize('viewAny', Anagrafica::class);

        $anagrafiche = $service->query([
            'search' => $this->search,
            'tipo'   => $this->tipo,
        ])->get();

        $filename = 'anagrafiche-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($anagrafiche): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'ragione_sociale',
                'tipo',
                'CF',
                'PIVA',
                'email',
                'telefono',
                'rentri_verificato',
            ], ';');

            foreach ($anagrafiche as $a) {
                fputcsv($handle, [
                    $a->ragione_sociale,
                    $a->tipoLabel(),
                    $a->codice_fiscale ?? '',
                    $a->piva ?? '',
                    $a->email ?? '',
                    $a->telefono ?? '',
                    $a->rentri_verificato_label(),
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
