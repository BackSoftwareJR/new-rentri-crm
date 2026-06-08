<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Ecommerce\EcommerceService;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Vetrina ricambi')]
class VetrinaIndex extends OperatorePage
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', EcommerceProdotto::class);
    }

    public function render(EcommerceService $ecommerce): View
    {
        return $this->operatoreView(
            'livewire.operatore.vetrina',
            [
                'prodotti'  => $ecommerce->listProdottiInEvidenza(),
                'contatori' => $ecommerce->contatoriCatalogo(),
                'service'   => $ecommerce,
            ],
            'vetrina',
            'Vetrina',
        );
    }
}
