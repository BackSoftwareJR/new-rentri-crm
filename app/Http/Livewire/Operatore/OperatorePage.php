<?php

namespace App\Http\Livewire\Operatore;

use Illuminate\Contracts\View\View;
use Livewire\Component;

abstract class OperatorePage extends Component
{
  protected function operatoreView(string $view, array $data = [], ?string $active = null, ?string $headerTitle = null): View
  {
    return view($view, $data)->layout('layouts.operatore', [
      'active'      => $active,
      'headerTitle' => $headerTitle ?? 'Operatore',
      'title'       => $headerTitle ?? 'Operatore',
    ]);
  }
}
