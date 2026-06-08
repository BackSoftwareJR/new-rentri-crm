<div>
    <x-breadcrumb
        variant="shop"
        :items="['Shop' => route('shop.index'), 'Catalogo' => route('shop.index')]"
        :current="$prodotto->nome"
    />

    <div class="shop-detail">
        <div class="shop-detail-img">
            @if ($immagineUrl)
                <img src="{{ $immagineUrl }}" alt="{{ $prodotto->nome }}" />
            @else
                <span class="shop-placeholder">Nessuna immagine disponibile</span>
            @endif
        </div>

        <div class="shop-detail-panel">
            <span class="shop-badge">{{ ucfirst($prodotto->categoria) }}</span>
            <h1 class="shop-detail-title">{{ $prodotto->nome }}</h1>
            <p class="text-[#86868b] m-0 text-sm">Codice: {{ $prodotto->codice }}</p>

            <div class="shop-detail-price">€ {{ number_format((float) $prodotto->prezzo, 2, ',', '.') }}</div>

            <div class="flex gap-2 flex-wrap my-3">
                <span class="shop-badge">Condizione: Usato</span>
                @if ($prodotto->giacenza > 0)
                    <span class="shop-badge shop-badge--ok">Disponibile ({{ $prodotto->giacenza }} pz.)</span>
                @else
                    <span class="shop-badge shop-badge--out">Esaurito</span>
                @endif
            </div>

            @if ($prodotto->descrizione)
                <p class="text-[15px] leading-relaxed text-[#3a3a3c] mt-4 mb-0">{{ $prodotto->descrizione }}</p>
            @endif

            <div class="shop-cta">
                @if ($prodotto->giacenza > 0)
                    <button
                        type="button"
                        class="shop-btn shop-btn-primary"
                        wire:click="$dispatch('add-to-cart', { prodottoId: {{ $prodotto->id }} })"
                    >
                        Aggiungi al carrello
                    </button>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}?subject={{ urlencode('Richiesta acquisto: '.$prodotto->nome) }}" class="shop-btn shop-btn-secondary">
                        Contatta per acquistare
                    </a>
                @endif
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="shop-btn shop-btn-secondary">
                        WhatsApp
                    </a>
                @endif
                @if ($telefono && ! $whatsappUrl)
                    <a href="tel:{{ preg_replace('/\s+/', '', $telefono) }}" class="shop-btn shop-btn-secondary">
                        {{ $telefono }}
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>
