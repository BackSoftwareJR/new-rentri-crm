<?php

namespace App\Http\Livewire\Segreteria\Mud;

use App\Domain\Mud\MudService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\MudDichiarazione;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('MUD')]
class MudIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $stato = '';

    #[Url(as: 'anno')]
    public string $filtro_anno = '';

    public string $anno_riferimento = '';

    public function mount(): void
    {
        $this->authorize('viewAny', MudDichiarazione::class);
        $this->anno_riferimento = (string) (int) now()->format('Y');
    }

    public function updatedStato(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroAnno(): void
    {
        $this->resetPage();
    }

    public function creaBozza(MudService $mud): void
    {
        $this->authorize('create', MudDichiarazione::class);

        $validated = $this->validate([
            'anno_riferimento' => ['required', 'integer', 'min:2000', 'max:'.((int) now()->format('Y') + 1)],
        ]);

        try {
            $dichiarazione = $mud->createBozza((int) $validated['anno_riferimento'], (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            $this->addError('anno_riferimento', $e->getMessage());

            return;
        }

        session()->flash('success', 'Bozza MUD '.$dichiarazione->anno_riferimento.' creata da registro movimenti.');
        $this->redirect(route('segreteria.mud.show', $dichiarazione), navigate: true);
    }

    public function render(MudService $mud): View
    {
        $filters = array_filter([
            'stato'             => $this->stato !== '' ? $this->stato : null,
            'anno_riferimento'  => $this->filtro_anno !== '' ? (int) $this->filtro_anno : null,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->segreteriaView(
            'livewire.segreteria.mud.index',
            [
                'dichiarazioni' => $mud->list($filters),
                'contatori'     => $mud->contatori($filters),
                'service'       => $mud,
            ],
            'mud',
            'MUD',
        );
    }
}
