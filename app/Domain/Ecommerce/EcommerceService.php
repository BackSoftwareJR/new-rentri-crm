<?php

namespace App\Domain\Ecommerce;

use App\Domain\Audit\ActivityLogService;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EcommerceService
{
    public const CART_SESSION_KEY = 'ecommerce_cart';

    /**
     * @param  array{categoria?: string|null, q?: string|null, per_page?: int}  $filters
     */
    public function listProdotti(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(12, min(100, (int) ($filters['per_page'] ?? 24)));

        return $this->prodottiQuery($filters)
            ->with('vfuRegistration:id,targa,marca,modello')
            ->paginate($perPage);
    }

    /**
     * @return array{totale: int, disponibili: int, esauriti: int}
     */
    public function contatoriCatalogo(): array
    {
        $base = EcommerceProdotto::query()->where('attivo', true);

        return [
            'totale'      => (clone $base)->count(),
            'disponibili' => (clone $base)->where('giacenza', '>', 0)->count(),
            'esauriti'    => (clone $base)->where('giacenza', '<=', 0)->count(),
        ];
    }

    /**
     * Ultimi ricambi disponibili in evidenza (vetrina operatore stub).
     *
     * @return Collection<int, EcommerceProdotto>
     */
    public function listProdottiInEvidenza(int $limit = 12): Collection
    {
        $limit = max(1, min(24, $limit));

        return EcommerceProdotto::query()
            ->where('attivo', true)
            ->where('giacenza', '>', 0)
            ->with('vfuRegistration:id,targa,marca,modello')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, int> product_id => qty
     */
    public function getCart(): array
    {
        /** @var array<int, int>|null $cart */
        $cart = session(self::CART_SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }

    public function cartCount(): int
    {
        return (int) array_sum($this->getCart());
    }

    public function addToCart(int $prodottoId, int $qty = 1): void
    {
        $prodotto = EcommerceProdotto::query()->where('attivo', true)->findOrFail($prodottoId);

        if ($qty < 1) {
            throw new \InvalidArgumentException('Quantità non valida.');
        }

        $cart = $this->getCart();
        $current = (int) ($cart[$prodottoId] ?? 0);
        $newQty = $current + $qty;

        if ($newQty > $prodotto->giacenza) {
            throw new \InvalidArgumentException('Giacenza insufficiente per '.$prodotto->nome.'.');
        }

        $cart[$prodottoId] = $newQty;
        session([self::CART_SESSION_KEY => $cart]);
    }

    public function updateCartQty(int $prodottoId, int $qty): void
    {
        $cart = $this->getCart();

        if ($qty <= 0) {
            unset($cart[$prodottoId]);
            session([self::CART_SESSION_KEY => $cart]);

            return;
        }

        $prodotto = EcommerceProdotto::query()->where('attivo', true)->findOrFail($prodottoId);

        if ($qty > $prodotto->giacenza) {
            throw new \InvalidArgumentException('Giacenza insufficiente.');
        }

        $cart[$prodottoId] = $qty;
        session([self::CART_SESSION_KEY => $cart]);
    }

    public function clearCart(): void
    {
        session()->forget(self::CART_SESSION_KEY);
    }

    /**
     * @return Collection<int, array{prodotto: EcommerceProdotto, qty: int, subtotale: float}>
     */
    public function resolveCartLines(): Collection
    {
        $cart = $this->getCart();

        if ($cart === []) {
            return collect();
        }

        $prodotti = EcommerceProdotto::query()
            ->whereIn('id', array_keys($cart))
            ->where('attivo', true)
            ->get()
            ->keyBy('id');

        return collect($cart)
            ->map(function (int $qty, int $id) use ($prodotti) {
                $prodotto = $prodotti->get($id);
                if ($prodotto === null) {
                    return null;
                }

                return [
                    'prodotto'  => $prodotto,
                    'qty'       => $qty,
                    'subtotale' => round((float) $prodotto->prezzo * $qty, 2),
                ];
            })
            ->filter()
            ->values();
    }

    public function cartTotale(): float
    {
        return round($this->resolveCartLines()->sum('subtotale'), 2);
    }

    public function createOrdineBozza(int $userId): EcommerceOrdine
    {
        $lines = $this->resolveCartLines();

        if ($lines->isEmpty()) {
            throw new \InvalidArgumentException('Il carrello è vuoto.');
        }

        return DB::transaction(function () use ($userId, $lines) {
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
                    'prodotto_id'     => $prodotto->id,
                    'codice'          => $prodotto->codice,
                    'nome'            => $prodotto->nome,
                    'qty'             => $line['qty'],
                    'prezzo_unitario' => (float) $prodotto->prezzo,
                    'subtotale'       => $subtotale,
                ];

                $prodotto->update(['giacenza' => $prodotto->giacenza - $line['qty']]);
            }

            $ordine = EcommerceOrdine::create([
                'user_id' => $userId,
                'stato'   => OrdineEcommerceStato::Bozza,
                'totale'  => round($totale, 2),
                'righe'   => $righe,
            ]);

            $this->clearCart();

            app(ActivityLogService::class)->record(
                'ecommerce',
                'Ordine e-commerce bozza creato',
                $ordine,
                [
                    'ordine_id' => $ordine->id,
                    'totale'    => (float) $ordine->totale,
                    'righe'     => count($righe),
                ],
                $userId,
            );

            return $ordine;
        });
    }

    /**
     * @param  array{stato?: string|null, per_page?: int}  $filters
     */
    public function listOrdini(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(50, (int) ($filters['per_page'] ?? 15)));

        $query = EcommerceOrdine::query()->with('user:id,name');

        if (! empty($filters['stato']) && in_array($filters['stato'], [
            'bozza',
            'pagamento_in_attesa',
            'confermato',
            'annullato',
        ], true)) {
            $query->where('stato', $filters['stato']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * @param  array{stato?: string|null}  $filters
     * @return \Illuminate\Support\Collection<int, EcommerceOrdine>
     */
    public function recentOrdini(array $filters = [], int $limit = 10): \Illuminate\Support\Collection
    {
        $query = EcommerceOrdine::query()->with('user:id,name');

        if (! empty($filters['stato']) && in_array($filters['stato'], [
            'bozza',
            'pagamento_in_attesa',
            'confermato',
            'annullato',
        ], true)) {
            $query->where('stato', $filters['stato']);
        }

        return $query->orderByDesc('created_at')->limit(max(1, min(25, $limit)))->get();
    }

    public function prezzoDisplay(EcommerceProdotto $prodotto): string
    {
        return number_format((float) $prodotto->prezzo, 2, ',', '.').' €';
    }

    public function statoOrdineBadge(OrdineEcommerceStato $stato): string
    {
        return match ($stato) {
            OrdineEcommerceStato::Confermato          => 'success',
            OrdineEcommerceStato::PagamentoInAttesa   => 'info',
            OrdineEcommerceStato::Annullato           => 'muted',
            OrdineEcommerceStato::Bozza               => 'warning',
        };
    }

    public function statoOrdineLabel(OrdineEcommerceStato $stato): string
    {
        return match ($stato) {
            OrdineEcommerceStato::Confermato          => 'Confermato',
            OrdineEcommerceStato::PagamentoInAttesa   => 'Pagamento in attesa',
            OrdineEcommerceStato::Annullato           => 'Annullato',
            OrdineEcommerceStato::Bozza               => 'Bozza',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function prodottiQuery(array $filters): Builder
    {
        $query = EcommerceProdotto::query()->where('attivo', true);

        if (! empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('codice', 'like', $term)
                    ->orWhere('nome', 'like', $term)
                    ->orWhere('descrizione', 'like', $term);
            });
        }

        if (! empty($filters['solo_disponibili'])) {
            $query->where('giacenza', '>', 0);
        }

        return $query->orderBy('nome');
    }
}
