<div>
    <p class="op-section-lead">Seleziona un veicolo da bonificare.</p>

    <div class="op-bn-filters">
        <button type="button"
            wire:click="$set('soloAssegnati', true)"
            class="op-bn-filter {{ $soloAssegnati ? 'op-bn-filter--active' : '' }}">
            Assegnati a me
        </button>
        <button type="button"
            wire:click="$set('soloAssegnati', false)"
            class="op-bn-filter {{ ! $soloAssegnati ? 'op-bn-filter--active' : '' }}">
            Tutti
        </button>
        @foreach (['tutti' => 'Tutti stati', 'scaduti' => 'Scaduti', 'in_tempo' => 'In tempo', 'dopo_pericolosi' => 'Pericolosi OK'] as $key => $label)
            <button type="button"
                wire:click="$set('filtro', '{{ $key }}')"
                class="op-bn-filter {{ $filtro === $key ? 'op-bn-filter--active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="op-bn-search-wrap" style="display:flex;gap:8px;align-items:center;">
        <input type="search" wire:model.live.debounce.300ms="search" class="op-bn-search" placeholder="Cerca targa o veicolo…" autocomplete="off" aria-label="Cerca veicolo da bonificare" style="flex:1;" />
        <x-barcode-scanner
            target="bonifica"
            button-label="Scansiona targa"
            button-class="op-btn op-btn-secondary"
            x-on:scanner-result.window="if ($event.detail.target === 'bonifica') $wire.selectFromScan($event.detail.value)"
        />
    </div>

    @if ($veicoli->isEmpty())
        <x-empty-state
            :title="$search !== '' || $filtro !== 'tutti' || ! $soloAssegnati ? 'Nessun veicolo trovato' : 'Nessun veicolo in bonifica'"
            :description="$search !== '' || $filtro !== 'tutti' || ! $soloAssegnati ? 'Prova a modificare filtri o ricerca.' : 'Tutti i veicoli accettati sono stati bonificati o non sono ancora in attesa.'"
        />
    @else
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
                    <p class="op-bn-detail">
                        {{ $v->codice_motore ? 'Mot. '.$v->codice_motore.' · ' : '' }}{{ number_format((float) $v->peso_kg, 0) }} kg
                        @if ($v->data_accettazione)
                            · {{ $v->data_accettazione->format('d/m/Y') }}
                        @endif
                    </p>
                    @if ($fase !== 'pericolosi_ok' && ($item['checklist_pericolosi'] ?? null))
                        <p class="op-bn-meta">
                            Checklist pericolosi: {{ $item['checklist_pericolosi']['done'] }}/{{ $item['checklist_pericolosi']['total'] }}
                            @if ($item['checklist_pericolosi']['complete'])
                                · completa
                            @endif
                        </p>
                    @endif
                    @if ($fase !== 'pericolosi_ok' && $giorni !== null)
                        <p class="op-bn-meta {{ $giorni < 0 ? 'op-bn-meta--danger' : '' }}">
                            @if ($giorni < 0)
                                Scaduto da {{ abs($giorni) }} gg (pericolosi)
                            @else
                                Entro {{ $giorni }} gg — scadenza liquidi pericolosi
                            @endif
                        </p>
                    @endif
                    @if ($inCorso)
                        <p class="op-bn-cta">Continua bonifica →</p>
                    @endif
                </a>
            @endforeach
        </div>

        @if ($veicoli->hasPages())
            <div class="op-pagination-wrap" style="margin-top: 1rem;">
                {{ $veicoli->links() }}
            </div>
        @endif
    @endif
</div>
