<?php

namespace App\Services\Ecommerce;

use App\Models\EcommerceProdotto;
use Illuminate\Support\Collection;

class CartService
{
    public const SESSION_KEY = 'cart';

    public function add(EcommerceProdotto $prodotto, int $qty = 1): void
    {
        if ($qty < 1) {
            throw new \InvalidArgumentException('Quantità non valida.');
        }

        if (! $prodotto->attivo) {
            throw new \InvalidArgumentException('Prodotto non disponibile.');
        }

        $cart = $this->rawCart();
        $current = (int) ($cart[$prodotto->id] ?? 0);
        $newQty = $current + $qty;

        if ($newQty > $prodotto->giacenza) {
            throw new \InvalidArgumentException('Giacenza insufficiente per '.$prodotto->nome.'.');
        }

        $cart[$prodotto->id] = $newQty;
        session([self::SESSION_KEY => $cart]);
    }

    public function remove(int $prodottoId): void
    {
        $cart = $this->rawCart();
        unset($cart[$prodottoId]);
        session([self::SESSION_KEY => $cart]);
    }

    public function updateQty(int $prodottoId, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($prodottoId);

            return;
        }

        $prodotto = EcommerceProdotto::query()->where('attivo', true)->findOrFail($prodottoId);

        if ($qty > $prodotto->giacenza) {
            throw new \InvalidArgumentException('Giacenza insufficiente.');
        }

        $cart = $this->rawCart();
        $cart[$prodottoId] = $qty;
        session([self::SESSION_KEY => $cart]);
    }

    /**
     * @return Collection<int, array{prodotto: EcommerceProdotto, qty: int, subtotale: float}>
     */
    public function items(): Collection
    {
        $cart = $this->rawCart();

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
                    'prodotto' => $prodotto,
                    'qty' => $qty,
                    'subtotale' => round((float) $prodotto->prezzo * $qty, 2),
                ];
            })
            ->filter()
            ->values();
    }

    public function total(): float
    {
        return round($this->items()->sum('subtotale'), 2);
    }

    public function count(): int
    {
        return (int) array_sum($this->rawCart());
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->rawCart() === [];
    }

    /**
     * @return array<int, int>
     */
    private function rawCart(): array
    {
        /** @var array<int, int>|null $cart */
        $cart = session(self::SESSION_KEY, []);

        return is_array($cart) ? $cart : [];
    }
}
