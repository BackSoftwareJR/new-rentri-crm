<?php

namespace App\Http\Livewire\Shop;

use App\Domain\Ecommerce\EcommerceProdottoImmagineService;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Ricambi')]
class ShopIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $categoria = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedCategoria(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(EcommerceProdottoImmagineService $immagini): View
    {
        $query = EcommerceProdotto::query()
            ->where('attivo', true)
            ->orderBy('nome');

        if ($this->categoria !== '') {
            $query->where('categoria', $this->categoria);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nome', 'like', $term)
                    ->orWhere('codice', 'like', $term)
                    ->orWhere('descrizione', 'like', $term);
            });
        }

        $categorie = EcommerceProdotto::query()
            ->where('attivo', true)
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return view('livewire.shop.shop-index', [
            'prodotti' => $query->paginate(12),
            'categorie' => $categorie,
            'immagini' => $immagini,
        ])->layout('layouts.shop', [
            'title' => 'Ricambi usati',
        ]);
    }
}
