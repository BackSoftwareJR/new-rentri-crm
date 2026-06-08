<?php

namespace App\Http\Livewire\Segreteria\Ecommerce;

use App\Domain\Ecommerce\EcommerceService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Carrello')]
class EcommerceCarrello extends SegreteriaPage
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', EcommerceProdotto::class);
    }

    public function aggiornaQty(int $prodottoId, int $qty, EcommerceService $ecommerce): void
    {
        try {
            $ecommerce->updateCartQty($prodottoId, $qty);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rimuovi(int $prodottoId, EcommerceService $ecommerce): void
    {
        $ecommerce->updateCartQty($prodottoId, 0);
    }

    public function creaOrdineBozza(EcommerceService $ecommerce): void
    {
        $this->authorize('create', EcommerceOrdine::class);

        try {
            $ordine = $ecommerce->createOrdineBozza((int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Ordine bozza #'.$ordine->id.' creato (pagamento non gestito).');
        $this->redirect(route('segreteria.ecommerce.ordini.show', $ordine), navigate: true);
    }

    public function render(EcommerceService $ecommerce): View
    {
        $paymentRuntime = app(\App\Domain\Ecommerce\EcommercePaymentRuntimeModeService::class);

        return $this->segreteriaView(
            'livewire.segreteria.ecommerce.carrello',
            [
                'lines'              => $ecommerce->resolveCartLines(),
                'totale'             => $ecommerce->cartTotale(),
                'service'            => $ecommerce,
                'paymentRuntime'     => $paymentRuntime,
                'paymentPreflight'   => $paymentRuntime->preflightChecklist(),
                'paymentPreflightOk' => $paymentRuntime->preflightReady(),
                'stripeDashboardUrl' => $paymentRuntime->stripeDashboardUrl(),
            ],
            'ecommerce',
            'Carrello',
        );
    }
}
