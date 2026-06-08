<div>
    <x-breadcrumb variant="shop" :items="['Shop' => route('shop.index')]" current="Catalogo" />
    <h1 class="text-[32px] font-bold tracking-tight m-0 mb-2">Ricambi usati</h1>
    <p class="text-[#86868b] m-0 mb-6 text-[15px]">Componenti da veicoli a fine vita, verificati e pronti per il riuso.</p>

    <div class="shop-filters">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Cerca per nome o codice…"
            class="shop-input flex-1 min-w-[220px]"
            autocomplete="off"
            aria-label="Cerca prodotti"
        />
        <select wire:model.live="categoria" class="shop-select">
            <option value="">Tutte le categorie</option>
            @foreach ($categorie as $cat)
                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
            @endforeach
        </select>
    </div>

    <div class="shop-grid">
        @forelse ($prodotti as $prodotto)
            @php $img = $immagini->publicUrl($prodotto); @endphp
            <div class="shop-card" wire:key="shop-{{ $prodotto->id }}">
                <a href="{{ route('shop.prodotto', $prodotto) }}" class="shop-card-link" wire:navigate>
                    <div class="shop-card-img">
                        @if ($img)
                            <img src="{{ $img }}" alt="{{ $prodotto->nome }}" loading="lazy" />
                        @else
                            <span class="shop-placeholder">Nessuna immagine</span>
                        @endif
                    </div>
                    <div class="shop-card-body">
                        <h2 class="shop-card-title">{{ $prodotto->nome }}</h2>
                        <div class="shop-card-price">€ {{ number_format((float) $prodotto->prezzo, 2, ',', '.') }}</div>
                        <div class="shop-card-meta">
                            {{ ucfirst($prodotto->categoria) }} · Usato
                            @if ($prodotto->giacenza > 0)
                                · Disponibile
                            @else
                                · Esaurito
                            @endif
                        </div>
                    </div>
                </a>
                @if ($prodotto->giacenza > 0)
                    <div class="shop-card-actions">
                        <button
                            type="button"
                            class="shop-btn shop-btn-primary shop-btn-add"
                            wire:click="$dispatch('add-to-cart', { prodottoId: {{ $prodotto->id }} })"
                        >
                            Aggiungi al carrello
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <p class="shop-placeholder col-span-full py-8">Nessun prodotto trovato.</p>
        @endforelse
    </div>

    @if ($prodotti->hasPages())
        <div class="mt-7">{{ $prodotti->links() }}</div>
    @endif
</div>
