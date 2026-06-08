<?php

namespace App\Domain\Ecommerce;

use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EcommerceCheckoutService
{
    public function __construct(
        private EcommercePaymentGatewayService $gateway,
        private EcommercePaymentRuntimeModeService $runtime,
    ) {}

    /**
     * @param  non-empty-string  $pagamentoMetodo
     */
    public function avviaCheckout(EcommerceOrdine $ordine, string $pagamentoMetodo, ?string $note = null): EcommerceOrdine
    {
        if ($ordine->stato !== OrdineEcommerceStato::Bozza) {
            throw new \InvalidArgumentException('Solo ordini in bozza possono avviare il checkout.');
        }

        if (! $this->runtime->isStub() && ! $this->runtime->preflightReady()) {
            throw ValidationException::withMessages([
                'checkout' => 'Preflight Stripe non superato. Verificare STRIPE_KEY e STRIPE_WEBHOOK_SECRET.',
            ]);
        }

        $ordine->update([
            'stato'            => OrdineEcommerceStato::PagamentoInAttesa,
            'pagamento_metodo' => $pagamentoMetodo,
            'note_checkout'    => $note,
        ]);

        $this->gateway->initiatePayment($ordine->fresh(), $pagamentoMetodo);

        return $ordine->fresh();
    }

    public function confermaPagamentoStub(EcommerceOrdine $ordine, string $token, int $userId): EcommerceOrdine
    {
        return $this->gateway->confirmStubPayment($ordine, $token, $userId);
    }

    public function annullaOrdine(EcommerceOrdine $ordine, int $userId): EcommerceOrdine
    {
        if ($ordine->stato === OrdineEcommerceStato::Annullato) {
            throw new \InvalidArgumentException('Ordine già annullato.');
        }

        if ($ordine->stato === OrdineEcommerceStato::Confermato) {
            throw new \InvalidArgumentException('Ordine confermato — annullamento non consentito (stub).');
        }

        return DB::transaction(function () use ($ordine, $userId) {
            $this->ripristinaGiacenza($ordine);

            $ordine->update([
                'stato'                      => OrdineEcommerceStato::Annullato,
                'annullato_at'               => now(),
                'checkout_token'             => null,
                'stripe_checkout_session_id' => null,
                'payment_checkout_url'       => null,
                'payment_gateway'            => null,
            ]);

            app(\App\Domain\Audit\ActivityLogService::class)->record(
                'ecommerce',
                'Ordine e-commerce annullato — giacenza ripristinata',
                $ordine,
                ['ordine_id' => $ordine->id],
                $userId,
            );

            return $ordine->fresh();
        });
    }

    private function ripristinaGiacenza(EcommerceOrdine $ordine): void
    {
        foreach ($ordine->righe ?? [] as $riga) {
            $prodottoId = (int) ($riga['prodotto_id'] ?? 0);
            $qty = (int) ($riga['qty'] ?? 0);

            if ($prodottoId <= 0 || $qty <= 0) {
                continue;
            }

            $prodotto = EcommerceProdotto::query()->lockForUpdate()->find($prodottoId);

            if ($prodotto !== null) {
                $prodotto->update(['giacenza' => $prodotto->giacenza + $qty]);
            }
        }
    }
}
