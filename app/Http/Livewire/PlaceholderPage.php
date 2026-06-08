<?php

namespace App\Http\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('CRM')]
class PlaceholderPage extends Component
{
    public string $heading = 'Modulo';

    public function mount(string $heading = 'Modulo'): void
    {
        $this->heading = $heading;
    }

    public function render()
    {
        return <<<'BLADE'
        <div class="p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $heading }}</h1>
            <p class="mt-2 text-slate-600">Modulo in costruzione — Sprint 0 placeholder.</p>
        </div>
        BLADE;
    }
}
