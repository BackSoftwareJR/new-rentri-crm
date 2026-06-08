<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <x-breadcrumb :items="['Segreteria' => route('segreteria.dashboard')]" current="Fatturazione" />
            <h1>Fatturazione</h1>
            <p>Fatture, note di credito e preventivi.</p>
        </div>
        <div class="seg-header-actions">
            <button wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                    class="seg-btn seg-btn-secondary" type="button">
                <span wire:loading.remove wire:target="exportCsv">Esporta CSV</span>
                <span wire:loading wire:target="exportCsv">Esportazione…</span>
            </button>
            <a href="{{ route('segreteria.fatture.create') }}" class="seg-btn seg-btn-primary" wire:navigate>
                + Nuova fattura
            </a>
        </div>
    </div>

    <div class="seg-kpi-grid mb-6">
        <x-kpi-card title="Emesse" :value="'€ '.number_format($riepilogo['emesse'], 2, ',', '.')" valueColor="#2563eb" />
        <x-kpi-card title="Pagate" :value="'€ '.number_format($riepilogo['pagate'], 2, ',', '.')" valueColor="#16a34a" />
        <x-kpi-card title="Scadute" :value="'€ '.number_format($riepilogo['scadute'], 2, ',', '.')" valueColor="#dc2626" />
    </div>

    <div class="seg-card seg-card-padding-sm seg-filters mb-4">
        <div class="seg-filters-row">
            <div class="seg-search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search"
                       placeholder="Numero o cliente…"
                       aria-label="Cerca fatture" />
            </div>
            <select wire:model.live="stato" class="seg-select" aria-label="Filtra per stato">
                <option value="">Tutti</option>
                <option value="bozza">Bozza</option>
                <option value="emessa">Emessa</option>
                <option value="pagata">Pagata</option>
                <option value="scaduta">Scaduta</option>
                <option value="annullata">Annullata</option>
            </select>
            <select wire:model.live="tipo" class="seg-select" aria-label="Filtra per tipo">
                <option value="">Tutti</option>
                <option value="fattura">Fattura</option>
                <option value="nota_credito">Nota credito</option>
                <option value="preventivo">Preventivo</option>
            </select>
            <input wire:model.live="dataDa" type="date" class="seg-input" aria-label="Data da">
            <input wire:model.live="dataA" type="date" class="seg-input" aria-label="Data a">
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        @if ($fatture->isEmpty())
            <x-empty-state
                :title="$search !== '' || $stato !== '' || $tipo !== '' || $dataDa !== '' || $dataA !== '' ? 'Nessuna fattura trovata' : 'Nessuna fattura registrata'"
                :description="$search !== '' || $stato !== '' || $tipo !== '' || $dataDa !== '' || $dataA !== '' ? 'Prova a modificare i filtri di ricerca.' : 'Crea la prima fattura per iniziare.'"
                action-label="+ Nuova fattura"
                :action-href="route('segreteria.fatture.create')"
            />
        @else
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                            <th class="text-right">Totale</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fatture as $fattura)
                            <tr wire:key="fattura-{{ $fattura->id }}">
                                <td class="font-semibold font-mono text-[13px]">
                                    {{ $fattura->numero_fattura }}
                                </td>
                                <td>
                                    <span class="seg-badge text-[11px]">
                                        {{ $fattura->tipoLabel() }}
                                    </span>
                                </td>
                                <td>{{ $fattura->anagrafica?->ragione_sociale ?? '—' }}</td>
                                <td class="text-[13px]">{{ $fattura->data_emissione?->format('d/m/Y') }}</td>
                                <td class="text-[13px]">{{ $fattura->data_scadenza?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <span class="seg-badge text-[11px]"
                                          style="background:{{ $fattura->statoColor() }}1a;color:{{ $fattura->statoColor() }};">
                                        {{ $fattura->statoLabel() }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold text-[13px]">
                                    € {{ number_format((float) $fattura->totale, 2, ',', '.') }}
                                </td>
                                <td class="seg-table-actions">
                                    <a href="{{ route('segreteria.fatture.show', $fattura) }}"
                                       class="seg-btn seg-btn-sm seg-btn-secondary"
                                       wire:navigate>
                                        Apri
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($fatture->hasPages())
                <div class="seg-pagination">
                    {{ $fatture->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
