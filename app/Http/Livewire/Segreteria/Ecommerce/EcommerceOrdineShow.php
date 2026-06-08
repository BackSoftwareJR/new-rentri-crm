<?php

namespace App\Http\Livewire\Segreteria\Ecommerce;

use App\Domain\Ecommerce\EcommerceCheckoutService;
use App\Domain\Ecommerce\EcommercePaymentRuntimeModeService;
use App\Domain\Ecommerce\EcommerceService;
use App\Enums\OrdineEcommerceStato;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\EcommerceOrdine;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;

#[Title('Dettaglio ordine')]
class EcommerceOrdineShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public EcommerceOrdine $ordine;

    public string $pagamentoMetodo = 'bonifico';

    public string $noteCheckout = '';

    public string $checkoutToken = '';

    public function mount(EcommerceOrdine $ordine): void
    {
        $this->authorize('view', $ordine);
        $this->ordine = $ordine->load('user:id,name');
    }

    public function avviaCheckout(EcommerceCheckoutService $checkout): void
    {
        $this->authorize('checkout', $this->ordine);

        $this->validate([
            'pagamentoMetodo' => ['required', 'in:bonifico,contanti,pos_stub,stripe'],
            'noteCheckout'    => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->ordine = $checkout->avviaCheckout(
                $this->ordine->fresh(),
                $this->pagamentoMetodo,
                $this->noteCheckout !== '' ? $this->noteCheckout : null,
            );
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (ValidationException $e) {
            session()->flash('error', $e->validator->errors()->first());

            return;
        }

        $runtime = app(EcommercePaymentRuntimeModeService::class);

        if ($runtime->isStub()) {
            session()->flash('success', 'Checkout avviato — conferma il pagamento con il token generato.');
        } else {
            session()->flash('success', 'Checkout Stripe avviato — completa il pagamento dal link sicuro.');
        }
    }

    public function confermaPagamento(EcommerceCheckoutService $checkout): void
    {
        $this->authorize('checkout', $this->ordine);

        $this->validate([
            'checkoutToken' => ['required', 'string', 'size:32'],
        ]);

        try {
            $this->ordine = $checkout->confermaPagamentoStub(
                $this->ordine->fresh(),
                $this->checkoutToken,
                (int) auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->checkoutToken = '';
        session()->flash('success', 'Pagamento confermato (stub sicuro) — ordine completato.');
    }

    public function annullaOrdine(EcommerceCheckoutService $checkout): void
    {
        $this->authorize('annulla', $this->ordine);

        try {
            $this->ordine = $checkout->annullaOrdine($this->ordine->fresh(), (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Ordine annullato — giacenza ripristinata.');
    }

    public function render(EcommerceService $ecommerce): View
    {
        $paymentRuntime = app(EcommercePaymentRuntimeModeService::class);

        return $this->segreteriaView(
            'livewire.segreteria.ecommerce.ordine-show',
            [
                'service'              => $ecommerce,
                'stati'                => OrdineEcommerceStato::cases(),
                'paymentRuntime'       => $paymentRuntime,
                'paymentPreflight'     => $paymentRuntime->preflightChecklist(),
                'paymentPreflightOk'   => $paymentRuntime->preflightReady(),
                'stripeDashboardUrl'   => $paymentRuntime->stripeDashboardUrl(),
            ],
            'ecommerce',
            'Ordine #'.$this->ordine->id,
        );
    }
}
