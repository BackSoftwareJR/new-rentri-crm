<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Vfu\SmontaggioService;
use App\Models\SmontaggioSession;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Smontaggio')]
class Smontaggio extends OperatorePage
{
    use AuthorizesRequests;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SmontaggioSession::class);
    }

    public function updatedSearch(): void
    {
        // triggers re-render
    }

    public function render(SmontaggioService $service): View
    {
        $query = $service->queryVeicoliPerSmontaggio();

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('targa', 'like', $term)
                    ->orWhere('telaio', 'like', $term)
                    ->orWhere('marca', 'like', $term)
                    ->orWhere('modello', 'like', $term);
            });
        }

        $veicoli = $query->with('smontaggioAttivo')->paginate(20);

        return $this->operatoreView(
            'livewire.operatore.smontaggio',
            compact('veicoli'),
            'smontaggio',
            'Smontaggio',
        );
    }
}
