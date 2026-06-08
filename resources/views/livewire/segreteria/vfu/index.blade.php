<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Veicoli fuori uso</h1>
            <p>Accettazione e gestione pratiche VFU
                <span class="seg-muted-inline">({{ $registrations->total() }} veicoli)</span>
            </p>
        </div>
        <div class="seg-header-actions">
            <x-contextual-help title="Pratiche VFU">
                Elenco pratiche demolizione: accettazione, bonifica e certificato rottamazione.
            </x-contextual-help>
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportStoricoCsv">
                Export storico CSV
            </button>
            <a href="{{ route('segreteria.vfu.create') }}" class="seg-btn seg-btn-primary" wire:navigate>+ Nuova accettazione</a>
        </div>
    </div>

    <div class="seg-kpi-grid">
        <div class="seg-kpi-card">
            <span class="seg-kpi-label">Bozze</span>
            <strong class="seg-kpi-value">{{ $kpi['bozza'] }}</strong>
        </div>
        <div class="seg-kpi-card">
            <span class="seg-kpi-label">In accettazione</span>
            <strong class="seg-kpi-value">{{ $kpi['in_accettazione'] }}</strong>
        </div>
        <div class="seg-kpi-card">
            <span class="seg-kpi-label">Accettati / attesa bonifica</span>
            <strong class="seg-kpi-value">{{ $kpi['accettato'] }}</strong>
        </div>
        <div class="seg-kpi-card">
            <span class="seg-kpi-label">In bonifica</span>
            <strong class="seg-kpi-value">{{ $kpi['in_bonifica'] }}</strong>
        </div>
    </div>

    <div class="seg-card seg-card-padding-sm seg-filters">
        <div class="seg-filters-row">
            <div class="seg-search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cerca targa, telaio, marca, proprietario…" />
            </div>
            <select wire:model.live="stato" class="seg-select">
                <option value="">Tutti gli stati</option>
                @foreach ($stati as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        @if ($registrations->isEmpty())
            <x-empty-state
                :title="$search !== '' || $stato !== '' ? 'Nessun veicolo trovato' : 'Nessun veicolo registrato'"
                :description="$search !== '' || $stato !== '' ? 'Prova a modificare i filtri di ricerca.' : 'Registra una nuova accettazione per iniziare a gestire le pratiche VFU.'"
                action-label="+ Nuova accettazione"
                :action-href="route('segreteria.vfu.create')"
            />
        @else
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Targa</th>
                        <th>Telaio</th>
                        <th>Veicolo</th>
                        <th>Proprietario</th>
                        <th>Peso (kg)</th>
                        <th>Stato</th>
                        <th>Consegna</th>
                        <th class="seg-table-actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $v)
                        <tr wire:key="vfu-{{ $v->id }}">
                            <td class="seg-cell-strong">
                                <a href="{{ route('segreteria.vfu.show', $v) }}" class="seg-link" wire:navigate>{{ $v->targa }}</a>
                            </td>
                            <td><code>{{ $v->telaio }}</code></td>
                            <td>{{ $v->veicoloLabel() }}</td>
                            <td>{{ $v->proprietario ?: '—' }}</td>
                            <td>{{ number_format((float) $v->peso_kg, 0, ',', '.') }}</td>
                            <td>
                                <x-badge-stato :stato="$v->stato->badgeStato()" :label="$v->stato->label()" />
                            </td>
                            <td>{{ $v->data_consegna?->format('d/m/Y') ?? '—' }}</td>
                            <td class="seg-table-actions">
                                <a href="{{ route('segreteria.vfu.show', $v) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Dettaglio</a>
                                @if (in_array($v->stato, [\App\Enums\VfuStato::Bozza, \App\Enums\VfuStato::InAccettazione], true))
                                    <a href="{{ route('segreteria.vfu.edit', $v) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Modifica</a>
                                @endif
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm seg-btn-danger"
                                    wire:click="delete({{ $v->id }})"
                                    wire:confirm="Eliminare il veicolo {{ $v->targa }}?">
                                    Elimina
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @if ($registrations->hasPages())
            <div class="seg-pagination">{{ $registrations->links() }}</div>
        @endif
    </div>
</div>
