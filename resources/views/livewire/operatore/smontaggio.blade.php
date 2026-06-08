<div class="op-smontaggio-list">
    <div class="op-bn-list-head">
        <h2 class="op-bn-list-title">Smontaggio</h2>
        <input
            type="search"
            class="op-bn-search"
            placeholder="Cerca per targa, telaio, marca…"
            wire:model.live.debounce.300ms="search"
            aria-label="Cerca veicolo"
        />
    </div>

    @if ($veicoli->isEmpty())
        <div class="op-empty-state">
            <p>Nessun veicolo pronto per lo smontaggio.</p>
            <p class="op-empty-state-hint">I veicoli devono essere bonificati prima di poter essere smontati.</p>
        </div>
    @else
        <ul class="op-bn-veicoli">
            @foreach ($veicoli as $vfu)
                <li class="op-bn-veicolo-card" wire:key="vfu-{{ $vfu->id }}">
                    <div class="op-bn-veicolo-info">
                        <span class="op-bn-targa">{{ $vfu->targa }}</span>
                        <span class="op-bn-veicolo-label">{{ $vfu->veicoloLabel() }}</span>
                        <span class="op-bn-stato op-bn-stato--{{ $vfu->stato->badgeStato() }}">
                            {{ $vfu->stato->label() }}
                        </span>
                        @if ($vfu->smontaggioAttivo)
                            <span class="op-bn-pill op-bn-pill--warn">In corso</span>
                        @endif
                    </div>
                    <a
                        href="{{ route('operatore.smontaggio.wizard', $vfu) }}"
                        class="op-btn op-btn-primary op-btn-sm"
                        wire:navigate
                    >
                        {{ $vfu->smontaggioAttivo ? 'Riprendi' : 'Avvia' }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $veicoli->links() }}
    @endif
</div>
