<?php

namespace App\Http\Livewire\Shop;

use App\Domain\Ecommerce\EcommerceCheckoutService;
use App\Domain\Ecommerce\EcommercePaymentRuntimeModeService;
use App\Domain\Ecommerce\ShopOrderService;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Checkout')]
class ShopCheckout extends Component
{
    public int $step = 1;

    public string $nome = '';

    public string $email = '';

    public string $telefono = '';

    public ?int $ordineId = null;

    public string $checkoutToken = '';

    public bool $paymentComplete = false;

    #[Url]
    public ?string $stripe = null;

    public function mount(): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            $this->nome = $user->name;
            $this->email = $user->email;
        }

        if ($this->stripe === 'success' && request()->has('ordine')) {
            $ordine = EcommerceOrdine::query()->find((int) request('ordine'));
            if ($ordine && $ordine->stato === OrdineEcommerceStato::Confermato) {
                $this->ordineId = $ordine->id;
                $this->step = 3;
                $this->paymentComplete = true;
            }
        }
    }

    public function proceedToPayment(
        ShopOrderService $shopOrder,
        EcommerceCheckoutService $checkout,
        EcommercePaymentRuntimeModeService $runtime,
    ): void {
        $this->validate([
            'nome' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200'],
            'telefono' => ['required', 'string', 'max:30'],
        ]);

        try {
            $ordine = $shopOrder->createFromCart(
                auth()->id(),
                [
                    'nome' => $this->nome,
                    'email' => $this->email,
                    'telefono' => $this->telefono,
                ],
            );
        } catch (\InvalidArgumentException $e) {
            session()->flash('checkout_error', $e->getMessage());

            return;
        }

        $successUrl = route('shop.checkout', ['stripe' => 'success', 'ordine' => $ordine->id]);
        $cancelUrl = route('shop.checkout', ['stripe' => 'cancel', 'ordine' => $ordine->id]);

        try {
            $ordine = $checkout->avviaCheckout(
                $ordine,
                $runtime->isStub() ? 'card_stub' : 'stripe',
                $ordine->note_checkout,
                $successUrl,
                $cancelUrl,
            );
        } catch (\Throwable $e) {
            session()->flash('checkout_error', $e->getMessage());

            return;
        }

        $this->ordineId = $ordine->id;
        $this->checkoutToken = (string) ($ordine->checkout_token ?? '');
        $this->step = 2;

        if (! $runtime->isStub() && filled($ordine->payment_checkout_url)) {
            $this->redirect($ordine->payment_checkout_url);
        }
    }

    public function confirmStubPayment(EcommerceCheckoutService $checkout): void
    {
        if ($this->ordineId === null) {
            return;
        }

        $ordine = EcommerceOrdine::query()->findOrFail($this->ordineId);

        if ($ordine->checkout_token) {
            $this->checkoutToken = $ordine->checkout_token;
        }

        $this->validate([
            'checkoutToken' => ['required', 'string', 'size:32'],
        ]);

        try {
            $checkout->confermaPagamentoStub($ordine, $this->checkoutToken, (int) (auth()->id() ?? 0));
        } catch (\InvalidArgumentException $e) {
            session()->flash('checkout_error', $e->getMessage());

            return;
        }

        $this->paymentComplete = true;
        $this->step = 3;
    }

    public function render(EcommercePaymentRuntimeModeService $runtime): View
    {
        $ordine = $this->ordineId
            ? EcommerceOrdine::query()->find($this->ordineId)
            : null;

        return view('livewire.shop.shop-checkout', [
            'runtime' => $runtime,
            'ordine' => $ordine,
            'stripeKey' => config('services.stripe.key'),
        ])->layout('layouts.shop', [
            'title' => 'Checkout',
        ]);
    }
}
