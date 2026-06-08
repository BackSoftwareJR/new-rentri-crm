<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Bonifica\BonificaService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;

#[Title('Dashboard Operatore')]
class Dashboard extends OperatorePage
{
  public function render(BonificaService $bonifica): View
  {
    $veicoli = $bonifica->queryVeicoliDaBonificare()
      ->limit(6)
      ->get()
      ->map(fn ($vfu) => $bonifica->enrichVeicolo($vfu));

    $totale = $bonifica->queryVeicoliDaBonificare()->count();

    return $this->operatoreView(
      'livewire.operatore.dashboard',
      compact('veicoli', 'totale'),
      'dashboard',
      'Dashboard'
    );
  }
}
