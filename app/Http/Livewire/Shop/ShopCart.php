<?php

namespace App\Http\Livewire\Shop;

use App\Domain\Ecommerce\EcommerceProdottoImmagineService;
use App\Models\EcommerceProdotto;
use App\Services\Ecommerce\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

class ShopCart extends Component
{
    public bool $open = false;

    public bool $fullPage = false;

    public function mount(): void
    {
        $this->fullPage = request()->routeIs('shop.carrello');

        if ($this->fullPage) {
            $this->open = true;
        }
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        //
    }

    #[On('add-to-cart')]
    public function handleAddToCart(int $prodottoId, int $qty = 1): void
    {
        $this->addToCart($prodottoId, $qty);
    }

    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        if ($this->fullPage) {
            $this->redirect(route('shop.index'), navigate: true);

            return;
        }

        $this->open = false;
    }

    public function addToCart(int $prodottoId, int $qty = 1): void
    {
        $cart = app(CartService::class);

        try {
            $prodotto = EcommerceProdotto::query()->where('attivo', true)->findOrFail($prodottoId);
            $cart->add($prodotto, $qty);
            $this->dispatch('cart-updated');
            session()->flash('cart_success', 'Aggiunto al carrello.');
            $this->open = true;
        } catch (\InvalidArgumentException $e) {
            session()->flash('cart_error', $e->getMessage());
        }
    }

    public function updateQty(int $prodottoId, int $qty, CartService $cart): void
    {
        try {
            $cart->updateQty($prodottoId, $qty);
            $this->dispatch('cart-updated');
        } catch (\InvalidArgumentException $e) {
            session()->flash('cart_error', $e->getMessage());
        }
    }

    public function remove(int $prodottoId, CartService $cart): void
    {
        $cart->remove($prodottoId);
        $this->dispatch('cart-updated');
    }

    #[Title('Carrello')]
    public function render(CartService $cart, EcommerceProdottoImmagineService $immagini): View
    {
        $view = view('livewire.shop.shop-cart', [
            'lines' => $cart->items(),
            'totale' => $cart->total(),
            'count' => $cart->count(),
            'immagini' => $immagini,
        ]);

        if ($this->fullPage) {
            return $view->layout('layouts.shop', [
                'title' => 'Carrello',
            ]);
        }

        return $view;
    }
}
