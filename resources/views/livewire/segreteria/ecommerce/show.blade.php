<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.ecommerce') }}" wire:navigate>← Catalogo</a>
            </p>
            <h1>{{ $prodotto->nome }}</h1>
            <p><code>{{ $prodotto->codice }}</code> — {{ ucfirst($prodotto->categoria) }}</p>
        </div>
        <a href="{{ route('segreteria.ecommerce.carrello') }}" class="seg-btn seg-btn-secondary" wire:navigate>
            Carrello @if ($cartCount > 0)({{ $cartCount }})@endif
        </a>
    </div>

    <div class="mag-detail-grid">
        <section class="seg-card seg-card-padding">
            @if ($immagineUrl)
                <img src="{{ $immagineUrl }}" alt="Foto {{ $prodotto->nome }}" class="seg-eco-product-image" />
            @else
                <div class="seg-eco-product-image seg-eco-product-image--empty">Nessuna foto</div>
            @endif

            <h2 class="mag-section-title">Dettaglio prodotto</h2>
            <dl class="seg-dl">
                <dt>Prezzo</dt>
                <dd class="seg-cell-strong">{{ $service->prezzoDisplay($prodotto) }}</dd>
                <dt>Giacenza</dt>
                <dd>{{ $prodotto->giacenza }} pz.</dd>
                @if ($prodotto->descrizione)
                    <dt>Descrizione</dt>
                    <dd>{{ $prodotto->descrizione }}</dd>
                @endif
                @if ($prodotto->vfuRegistration)
                    <dt>Provenienza VFU</dt>
                    <dd>
                        <a href="{{ route('segreteria.vfu.show', $prodotto->vfu_registration_id) }}" wire:navigate>
                            {{ $prodotto->vfuRegistration->targa }} — {{ $prodotto->vfuRegistration->marca }} {{ $prodotto->vfuRegistration->modello }}
                        </a>
                    </dd>
                @endif
            </dl>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Immagine catalogo</h2>
            @can('uploadImage', $prodotto)
                <div class="seg-form-group">
                    <label class="seg-label" for="immagine-upload">Carica foto (JPG/PNG/WebP, max 2 MB)</label>
                    <input id="immagine-upload" type="file" wire:model="immagineUpload" accept="image/jpeg,image/png,image/webp" class="seg-input" />
                    @error('immagineUpload') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-header-actions" style="flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" class="seg-btn seg-btn-primary seg-btn-sm" wire:click="salvaImmagine" wire:loading.attr="disabled">Salva immagine</button>
                    @if ($prodotto->immagine_path)
                        <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="rimuoviImmagine" wire:confirm="Rimuovere l'immagine?">Rimuovi</button>
                    @endif
                </div>
            @endcan

            <h2 class="mag-section-title" style="margin-top: 1.5rem;">Aggiungi al carrello</h2>
            @if ($prodotto->giacenza > 0)
                <div class="seg-form-grid">
                    <div class="seg-form-group">
                        <label class="seg-label" for="qty-prodotto">Quantità</label>
                        <input id="qty-prodotto" type="number" wire:model="qty" min="1" max="{{ min(99, $prodotto->giacenza) }}" class="seg-input" />
                    </div>
                    <div class="seg-form-group seg-form-group--span2">
                        <button type="button" class="seg-btn seg-btn-primary" wire:click="aggiungiAlCarrello" wire:loading.attr="disabled">
                            Aggiungi al carrello
                        </button>
                    </div>
                </div>
            @else
                <p class="mag-empty">Prodotto esaurito.</p>
            @endif
        </section>
    </div>
</div>
