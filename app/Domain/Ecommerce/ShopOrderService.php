<?php

namespace App\Domain\Ecommerce;

use App\Domain\Audit\ActivityLogService;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Services\Ecommerce\CartService;
use Illuminate\Support\Facades\DB;

class ShopOrderService
{
    public function __construct(
        private CartService $cart,
    ) {}

    /**
     * @param  array{nome: string, email: string, telefono: string}  $customer
     */
    public function createFromCart(?int $userId, array $customer): EcommerceOrdine
    {
        $lines = $this->cart->items();

        if ($lines->isEmpty()) {
            throw new \InvalidArgumentException('Il carrello è vuoto.');
        }

        return DB::transaction(function () use ($userId, $customer, $lines) {
            $righe = [];
            $totale = 0.0;

            foreach ($lines as $line) {
                /** @var EcommerceProdotto $prodotto */
                $prodotto = EcommerceProdotto::query()->lockForUpdate()->findOrFail($line['prodotto']->id);

                if ($line['qty'] > $prodotto->giacenza) {
                    throw new \InvalidArgumentException('Giacenza insufficiente per '.$prodotto->nome.'.');
                }

                $subtotale = round((float) $prodotto->prezzo * $line['qty'], 2);
                $totale += $subtotale;

                $righe[] = [
                    'prodotto_id' => $prodotto->id,
                    'codice' => $prodotto->codice,
                    'nome' => $prodotto->nome,
                    'qty' => $line['qty'],
                    'prezzo_unitario' => (float) $prodotto->prezzo,
                    'subtotale' => $subtotale,
                ];

                $prodotto->update(['giacenza' => $prodotto->giacenza - $line['qty']]);
            }

            $ordine = EcommerceOrdine::create([
                'user_id' => $userId,
                'stato' => OrdineEcommerceStato::Bozza,
                'totale' => round($totale, 2),
                'righe' => $righe,
                'note_checkout' => json_encode([
                    'cliente' => $customer,
                    'source' => 'shop',
                ], JSON_THROW_ON_ERROR),
            ]);

            $this->cart->clear();

            app(ActivityLogService::class)->record(
                'ecommerce',
                'Ordine shop creato',
                $ordine,
                [
                    'ordine_id' => $ordine->id,
                    'totale' => (float) $ordine->totale,
                    'cliente_email' => $customer['email'],
                ],
                $userId,
            );

            return $ordine;
        });
    }
}
