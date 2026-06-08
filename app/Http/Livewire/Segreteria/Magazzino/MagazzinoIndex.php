<?php

namespace App\Http\Livewire\Segreteria\Magazzino;

use App\Domain\Magazzino\MagazzinoService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

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

    public function render(MagazzinoService $magazzino): View
    {
        $rows = $magazzino->listSerbatoi($this->search !== '' ? $this->search : null);
        $summary = $magazzino->summary($rows);

        return $this->segreteriaView(
            'livewire.segreteria.magazzino.index',
            [
                'serbatoi' => $rows,
                'summary'  => $summary,
                'magazzino'=> $magazzino,
            ],
            'magazzino',
            'Magazzino rifiuti',
        );
    }
}
