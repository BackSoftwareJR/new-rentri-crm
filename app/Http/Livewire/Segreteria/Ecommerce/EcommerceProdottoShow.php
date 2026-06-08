<?php

namespace App\Http\Livewire\Segreteria\Ecommerce;

use App\Domain\Ecommerce\EcommerceProdottoImmagineService;
use App\Domain\Ecommerce\EcommerceService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\EcommerceProdotto;
use App\Support\UploadValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Dettaglio ricambio')]
class EcommerceProdottoShow extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public EcommerceProdotto $prodotto;

    public int $qty = 1;

    public $immagineUpload;

    public function mount(EcommerceProdotto $prodotto): void
    {
        $this->authorize('view', $prodotto);
        $this->prodotto = $prodotto->load('vfuRegistration:id,targa,marca,modello,stato');
    }

    public function aggiungiAlCarrello(EcommerceService $ecommerce): void
    {
        $this->authorize('view', $this->prodotto);

        $this->validate(['qty' => ['required', 'integer', 'min:1', 'max:99']]);

        try {
            $ecommerce->addToCart($this->prodotto->id, $this->qty);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', $this->prodotto->nome.' aggiunto al carrello.');
        $this->qty = 1;
    }

    public function salvaImmagine(EcommerceProdottoImmagineService $immagini): void
    {
        $this->authorize('uploadImage', $this->prodotto);

        $this->validate(['immagineUpload' => UploadValidation::productImageRules()]);

        $this->prodotto = $immagini->upload($this->prodotto, $this->immagineUpload);
        $this->reset('immagineUpload');
        session()->flash('success', 'Immagine prodotto aggiornata.');
    }

    public function rimuoviImmagine(EcommerceProdottoImmagineService $immagini): void
    {
        $this->authorize('uploadImage', $this->prodotto);

        $this->prodotto = $immagini->remove($this->prodotto);
        session()->flash('success', 'Immagine rimossa.');
    }

    public function render(EcommerceService $ecommerce, EcommerceProdottoImmagineService $immagini): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.ecommerce.show',
            [
                'service'      => $ecommerce,
                'cartCount'    => $ecommerce->cartCount(),
                'immagineUrl'  => $immagini->publicUrl($this->prodotto),
            ],
            'ecommerce',
            $this->prodotto->nome,
        );
    }
}
