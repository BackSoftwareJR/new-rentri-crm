<?php

namespace App\Http\Livewire\Segreteria;

use Illuminate\Contracts\View\View;

abstract class SegreteriaPlaceholderPage extends SegreteriaPage
{
    public string $heading = 'Modulo';

    /** Chiave voce menu (allineata a sidebar-nav). */
    protected string $navKey = 'dashboard';

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.placeholder',
            [],
            $this->navKey,
            $this->heading,
        );
    }
}
