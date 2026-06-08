<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Bonifica\BonificaService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Bonifica VFU')]
class Bonifica extends OperatorePage
{
  use AuthorizesRequests;

  #[Url(as: 'q')]
  public string $search = '';

  #[Url]
  public string $filtro = 'tutti';

  public function mount(): void
  {
    $this->authorize('bonifica.viewAny');
  }

  public function updatedSearch(): void {}

  public function updatedFiltro(): void {}

  public function render(BonificaService $bonifica): View
  {
    $veicoli = $bonifica->queryVeicoliDaBonificare([
      'search' => $this->search,
      'filtro' => $this->filtro,
    ])->get()->map(fn ($vfu) => $bonifica->enrichVeicolo($vfu));

    return $this->operatoreView(
      'livewire.operatore.bonifica',
      compact('veicoli'),
      'bonifica',
      'Bonifica'
    );
  }
}
