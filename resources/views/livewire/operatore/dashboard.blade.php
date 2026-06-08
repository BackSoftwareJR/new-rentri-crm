<div>
    <p class="op-section-lead">
        {{ $totale === 0 ? 'Nessun veicolo in attesa di bonifica.' : $totale.' veicol'.($totale === 1 ? 'o' : 'i').' da bonificare.' }}
    </p>

    @if ($totale > 0)
        <div class="op-bn-list">
            @foreach ($veicoli as $item)
                @php
                    $v = $item['vfu'];
                    $fase = $item['bonifica_fase'];
                    $giorni = $item['bonifica_giorni_alla_scadenza'];
                    $inCorso = $v->stato->value === 'in_bonifica';
                @endphp
                <a href="{{ route('operatore.bonifica.wizard', $v) }}" class="op-bn-card op-bn-card--link" wire:navigate>
                    <div class="op-bn-card-head">
                        <span class="op-bn-targa">{{ $v->targa }}</span>
                        <span class="op-bn-pill op-bn-pill--{{ $fase === 'scaduto' ? 'danger' : ($inCorso ? 'warn' : 'info') }}">
                            @if ($fase === 'pericolosi_ok')
                                Pericolosi OK
                            @elseif ($inCorso)
                                In corso
                            @elseif ($fase === 'scaduto')
                                Scaduto
                            @else
                                In attesa
                            @endif
                        </span>
                    </div>
                    <p class="op-bn-sub">{{ $v->marca }} {{ $v->modello }}</p>
                    @if ($fase !== 'pericolosi_ok' && $giorni !== null)
                        <p class="op-bn-meta {{ $giorni < 0 ? 'op-bn-meta--danger' : '' }}">
                            @if ($giorni < 0)
                                Scaduto da {{ abs($giorni) }} gg (pericolosi)
                            @else
                                Entro {{ $giorni }} gg — scadenza liquidi pericolosi
                            @endif
                        </p>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($totale > 6)
            <div class="op-bn-more">
                <a href="{{ route('operatore.bonifica') }}" class="op-btn op-btn-secondary op-btn-full" wire:navigate>
                    Vedi tutti ({{ $totale }})
                </a>
            </div>
        @endif
    @else
        <div class="op-card op-bn-empty">
            <div class="op-bn-empty-icon">✅</div>
            <p class="op-bn-empty-title">Tutto bonificato</p>
            <p class="op-bn-empty-lead">Nessun veicolo in attesa.</p>
        </div>
    @endif

    <div class="op-dashboard-grid" style="margin-top: 24px;">
        <a href="{{ route('operatore.bonifica') }}" class="op-card op-dashboard-card op-bn-quick" wire:navigate>
            <div class="op-icon-circle amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <p class="op-dashboard-card-title">Bonifica</p>
            <p class="op-dashboard-card-lead">Gestisci bonifiche VFU</p>
        </a>
        <a href="{{ route('operatore.ricambi') }}" class="op-card op-dashboard-card op-bn-quick" wire:navigate>
            <div class="op-icon-circle green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <p class="op-dashboard-card-title">Ricambi</p>
            <p class="op-dashboard-card-lead">Inserisci ricambi</p>
        </a>
    </div>
</div>
