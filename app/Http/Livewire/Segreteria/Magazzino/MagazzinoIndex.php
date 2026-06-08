<?php

namespace App\Http\Livewire\Segreteria\Magazzino;

use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Magazzino rifiuti')]
class MagazzinoIndex extends SegreteriaPage
{
    use AuthorizesRequests;

    #[Url(as: 'q')]
    public string $search = '';

    public string $viewMode = 'grid';

    public function mount(): void
    {
        $this->authorize('magazzino.viewAny');
    }

    public function updatedSearch(): void
    {
        // Ricerca reattiva via #[Url]
    }

    public function exportCsv(MagazzinoService $magazzino): StreamedResponse
    {
        $this->authorize('magazzino.viewAny');

        $rows = $magazzino->listSerbatoi($this->search !== '' ? $this->search : null);
        $filename = 'magazzino-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'codice_cer',
                'descrizione',
                'giacenza_kg',
                'unita_misura',
                'ultima_variazione',
            ], ';');

            foreach ($rows as $s) {
                fputcsv($handle, [
                    $s['codice'],
                    $s['descrizione'],
                    number_format((float) $s['quantita_attuale_kg'], 4, '.', ''),
                    $s['um'],
                    $s['data_ultimo_aggiornamento']?->format('d/m/Y H:i') ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(MagazzinoService $magazzino, SerbatoioAlertService $serbatoioAlerts): View
    {
        $rows = $magazzino->listSerbatoi($this->search !== '' ? $this->search : null);
        $summary = $magazzino->summary($rows);
        $sottoMinimo = $serbatoioAlerts->giacenzeSottoMinimo();

        return $this->segreteriaView(
            'livewire.segreteria.magazzino.index',
            [
                'serbatoi'      => $rows,
                'summary'       => $summary,
                'magazzino'     => $magazzino,
                'sottoMinimo'   => $sottoMinimo,
            ],
            'magazzino',
            'Magazzino rifiuti',
        );
    }
}
