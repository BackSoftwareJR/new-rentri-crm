<?php

namespace App\Http\Livewire;

use App\Models\Sito;
use App\Support\Sito\SitoContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SitoSwitcher extends Component
{
    public bool $open = false;

    public function mount(): void
    {
        SitoContext::activeSitoId();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function switchSito(int $sitoId): void
    {
        $sito = Sito::query()
            ->whereKey($sitoId)
            ->where('is_active', true)
            ->first();

        if ($sito === null) {
            return;
        }

        SitoContext::setActiveSitoId($sito->id);
        $this->open = false;
        $this->dispatch('sito-switched', sitoId: $sito->id);
    }

    public function render(): View
    {
        /** @var Collection<int, Sito> $siti */
        $siti = Sito::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('nome')
            ->get();

        return view('livewire.sito-switcher', [
            'siti' => $siti,
            'activeSito' => SitoContext::activeSito(),
            'showSwitcher' => $siti->isNotEmpty(),
        ]);
    }
}
