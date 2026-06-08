<?php

namespace App\Http\Livewire\Operatore;

use Illuminate\Contracts\View\View;

abstract class OperatorePlaceholderPage extends OperatorePage
{
    public string $heading = 'Modulo';

    protected string $navKey = 'dashboard';

    public function render(): View
    {
        return $this->operatoreView(
            'livewire.partials.under-construction-operatore',
            ['heading' => $this->heading],
            $this->navKey,
            $this->heading,
        );
    }
}
