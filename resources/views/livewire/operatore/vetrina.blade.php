<div>
    <p class="op-section-lead">
        In evidenza — ultimi {{ $prodotti->count() }} ricambi disponibili su {{ $contatori['disponibili'] }} in catalogo.
    </p>

    @if ($prodotti->isEmpty())
        <div class="op-card op-bn-empty">
            <div class="op-bn-empty-icon">📦</div>
            <p class="op-bn-empty-title">Vetrina vuota</p>
            <p class="op-bn-empty-lead">Nessun ricambio disponibile al momento.</p>
        </div>
    @else
        <div class="op-bn-list">
            @foreach ($prodotti as $p)
                <article class="op-bn-card" wire:key="vet-{{ $p->id }}">
                    <div class="op-bn-card-head">
                        <span class="op-bn-targa">{{ $p->codice }}</span>
                        <span class="op-bn-pill op-bn-pill--info">In evidenza</span>
                    </div>
                    <p class="op-bn-sub">{{ $p->nome }}</p>
                    <p class="op-bn-detail">
                        {{ $service->prezzoDisplay($p) }} · {{ $p->giacenza }} pz. · {{ ucfirst($p->categoria) }}
                        @if ($p->vfuRegistration)
                            · VFU {{ $p->vfuRegistration->targa }}
                        @endif
                    </p>
                    @if ($p->descrizione)
                        <p class="op-bn-meta">{{ Str::limit($p->descrizione, 100) }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <div style="margin-top: 24px;">
        <a href="{{ route('operatore.ricambi') }}" class="op-btn op-btn-secondary op-btn-full" wire:navigate>
            Sfoglia tutti i ricambi
        </a>
    </div>
</div>
