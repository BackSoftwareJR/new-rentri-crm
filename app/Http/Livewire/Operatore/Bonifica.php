<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Bonifica\BonificaService;
use App\Enums\VfuStato;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Bonifica VFU')]
class Bonifica extends OperatorePage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filtro = 'tutti';

    #[Url]
    public bool $soloAssegnati = true;

    public function mount(): void
    {
        $this->authorize('bonifica.viewAny');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedSoloAssegnati(): void
    {
        $this->resetPage();
    }

    public function selectFromScan(string $value): void
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            return;
        }

        $this->search = preg_replace('/\s+/', '', $value) ?? $value;

        $vfu = VfuRegistration::query()
            ->whereIn('stato', [VfuStato::Accettato, VfuStato::AttesaBonifica, VfuStato::InBonifica])
            ->where(function ($query) use ($value) {
                $normalized = preg_replace('/\s+/', '', $value) ?? $value;
                $query->where('targa', $normalized)
                    ->orWhere('telaio', $normalized)
                    ->orWhere('targa', 'like', '%'.$normalized.'%')
                    ->orWhere('telaio', 'like', '%'.$normalized.'%');
            })
            ->first();

        if ($vfu) {
            $this->redirect(route('operatore.bonifica.wizard', $vfu), navigate: true);
        }
    }

    public function render(BonificaService $bonifica): View
    {
        $veicoli = $bonifica->queryVeicoliDaBonificare([
            'search' => $this->search,
            'filtro' => $this->filtro,
            'solo_assegnati' => $this->soloAssegnati,
            'operatore_id' => auth()->id(),
        ])
            ->paginate(10)
            ->through(fn ($vfu) => $bonifica->enrichVeicolo($vfu));

        return $this->operatoreView(
            'livewire.operatore.bonifica',
            compact('veicoli'),
            'bonifica',
            'Bonifica'
        );
    }
}
