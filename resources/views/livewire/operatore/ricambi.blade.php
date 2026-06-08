<div>
    @include('livewire.partials.flash-messages')

    <p class="op-section-lead">Vetrina read-only — ricambi disponibili dal catalogo e-commerce ({{ $contatori['disponibili'] }} pz.).</p>

    <div class="op-bn-filters">
        <button type="button" wire:click="$set('categoria', '')" class="op-bn-filter {{ $categoria === '' ? 'op-bn-filter--active' : '' }}">Tutti</button>
        @foreach (['motore' => 'Motore', 'carrozzeria' => 'Carrozzeria', 'elettronica' => 'Elettronica', 'interni' => 'Interni', 'generico' => 'Generico'] as $key => $label)
            <button type="button" wire:click="$set('categoria', '{{ $key }}')" class="op-bn-filter {{ $categoria === $key ? 'op-bn-filter--active' : '' }}">{{ $label }}</button>
        @endforeach
    </div>

    <div class="op-bn-search-wrap">
        <input type="search" wire:model.live.debounce.300ms="search" class="op-bn-search" placeholder="Cerca codice o nome…" autocomplete="off" />
    </div>

    @if ($prodotti->isEmpty())
        <div class="op-card op-bn-empty">
            <div class="op-bn-empty-icon">📦</div>
            <p class="op-bn-empty-title">Nessun ricambio disponibile</p>
            <p class="op-bn-empty-lead">Il catalogo è vuoto o tutti i prodotti sono esauriti.</p>
        </div>
    @else
        <div class="op-bn-list">
            @foreach ($prodotti as $p)
                <article class="op-bn-card" wire:key="ric-{{ $p->id }}">
                    <div class="op-bn-card-head">
                        <span class="op-bn-targa">{{ $p->codice }}</span>
                        <span class="op-bn-pill op-bn-pill--info">{{ ucfirst($p->categoria) }}</span>
                    </div>
                    <p class="op-bn-sub">{{ $p->nome }}</p>
                    <p class="op-bn-detail">
                        {{ $service->prezzoDisplay($p) }} · {{ $p->giacenza }} pz.
                        @if ($p->vfuRegistration)
                            · VFU {{ $p->vfuRegistration->targa }}
                        @endif
                    </p>
                    @if ($p->descrizione)
                        <p class="op-bn-meta">{{ Str::limit($p->descrizione, 80) }}</p>
                    @endif
                    @if (! empty($fotoPerProdotto[$p->id] ?? []))
                        <div class="op-ric-foto-preview" aria-label="Foto collegate">
                            @foreach ($fotoPerProdotto[$p->id] as $url)
                                <img src="{{ $url }}" alt="Foto ricambio {{ $p->codice }}" class="op-ric-foto-thumb" loading="lazy" />
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($prodotti->hasPages())
            <div style="margin-top: 16px;">{{ $prodotti->links() }}</div>
        @endif
    @endif

    @can('uploadPhotos', App\Models\EcommerceProdotto::class)
        <div class="op-card" style="margin-top: 24px;">
            <h3 class="op-dashboard-card-title">Foto ricambi → catalogo</h3>
            <p class="op-section-lead">Seleziona un ricambio e carica fino a 10 immagini collegate al catalogo.</p>
            <select wire:model="prodottoSelezionato" class="op-bn-search" style="margin-bottom: 12px;">
                <option value="">— Seleziona ricambio —</option>
                @foreach ($prodotti as $p)
                    <option value="{{ $p->id }}">{{ $p->codice }} — {{ $p->nome }}</option>
                @endforeach
            </select>
            @error('prodottoSelezionato') <p class="seg-field-error">{{ $message }}</p> @enderror
            <input type="file" wire:model="fotoBulk" accept="image/*" multiple class="op-bn-search" />
            @error('fotoBulk') <p class="seg-field-error">{{ $message }}</p> @enderror
            @error('fotoBulk.*') <p class="seg-field-error">{{ $message }}</p> @enderror
            @if ($prodottoSelezionato && ! empty($fotoPerProdotto[$prodottoSelezionato] ?? []))
                <div class="op-ric-foto-preview" style="margin-top: 12px;">
                    @foreach ($fotoPerProdotto[$prodottoSelezionato] as $url)
                        <img src="{{ $url }}" alt="Anteprima foto collegate" class="op-ric-foto-thumb" loading="lazy" />
                    @endforeach
                </div>
            @endif
            <button type="button" class="op-btn op-btn-secondary op-btn-full" style="margin-top: 12px;" wire:click="uploadFotoBulk" wire:loading.attr="disabled">
                Collega foto al catalogo
            </button>
        </div>
    @endcan
</div>
