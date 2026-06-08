<?php

namespace App\Http\Livewire\Shop;

use App\Domain\Ecommerce\EcommerceProdottoImmagineService;
use App\Models\CompanySetting;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShopProdotto extends Component
{
    public EcommerceProdotto $prodotto;

    public function mount(EcommerceProdotto $prodotto): void
    {
        abort_unless($prodotto->attivo, 404);

        $this->prodotto = $prodotto;
    }

    public function render(EcommerceProdottoImmagineService $immagini): View
    {
        $email = CompanySetting::get('company_email') ?? config('mail.from.address');
        $telefono = CompanySetting::get('company_telefono');
        $whatsappUrl = filled($telefono)
            ? 'https://wa.me/'.preg_replace('/\D+/', '', (string) $telefono).'?text='.urlencode('Richiesta info: '.$this->prodotto->nome)
            : null;

        return view('livewire.shop.shop-prodotto', [
            'immagineUrl' => $immagini->publicUrl($this->prodotto),
            'email' => $email,
            'telefono' => $telefono,
            'whatsappUrl' => $whatsappUrl,
        ])->layout('layouts.shop', [
            'title' => $this->prodotto->nome,
        ]);
    }
}
