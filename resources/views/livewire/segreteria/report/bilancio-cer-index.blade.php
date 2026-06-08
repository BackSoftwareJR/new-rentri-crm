<div>
    @include('livewire.partials.flash-messages')

    <x-page-header title="Bilancio CER" lead="Totale carichi, scarichi e saldo per codice CER nel periodo selezionato.">
        <x-slot name="actions">
            <button
                type="button"
                wire:click="exportCsv"
                class="seg-btn seg-btn-secondary seg-btn-sm seg-no-print"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="exportCsv">Esporta CSV</span>
                <span wire:loading wire:target="exportCsv">Esportazione…</span>
            </button>
            <button
                type="button"
                wire:click="exportExcel"
                class="seg-btn seg-btn-secondary seg-btn-sm seg-no-print"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="exportExcel">Esporta Excel</span>
                <span wire:loading wire:target="exportExcel">Esportazione…</span>
            </button>
            <a href="{{ route('segreteria.registro-movimenti') }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Registro movimenti</a>
        </x-slot>
    </x-page-header>

    {{-- KPI cards --}}
    @php
        $bilancio = $this->bilancio();
        $totals = $bilancio['totals'];
        $rows   = $bilancio['rows'];
    @endphp

    <div class="seg-kpi-grid" wire:loading.class="seg-loading-dim">
        <x-kpi-card
            title="Carichi totali"
            :value="number_format($totals['carichi_kg'], 2, ',', '.') . ' kg'"
            subtitle="{{ count($rows) }} codici CER"
            valueColor="#16a34a"
        />
        <x-kpi-card
            title="Scarichi totali"
            :value="number_format($totals['scarichi_kg'], 2, ',', '.') . ' kg'"
            valueColor="#dc2626"
        />
        <x-kpi-card
            title="Saldo netto"
            :value="number_format($totals['saldo_kg'], 2, ',', '.') . ' kg'"
            :valueColor="$totals['saldo_kg'] >= 0 ? '#0369a1' : '#dc2626'"
        />
        <x-kpi-card
            title="N. movimenti"
            :value="(string) $totals['n_movimenti']"
        />
    </div>

    {{-- Filters --}}
    <div class="seg-card seg-card-padding seg-no-print" style="margin-bottom: 1rem;">
        <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;">

            {{-- Preset buttons --}}
            <div>
                <p class="seg-label" style="margin: 0 0 0.375rem;">Periodo</p>
                <div style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                    @foreach ([
                        'year'  => 'Anno corrente',
                        'q1'    => 'T1',
                        'q2'    => 'T2',
                        'q3'    => 'T3',
                        'q4'    => 'T4',
                        'month' => 'Mese corrente',
                        'custom'=> 'Personalizzato',
                    ] as $key => $label)
                        <button
                            type="button"
                            wire:click="applyPreset('{{ $key }}')"
                            @class(['seg-btn', 'seg-btn-sm', $preset === $key ? 'seg-btn-primary' : 'seg-btn-secondary'])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Date range --}}
            <div style="display: flex; gap: 0.75rem; align-items: flex-end;">
                <div class="seg-form-group" style="margin: 0;">
                    <label class="seg-label" for="data-da">Da</label>
                    <input
                        id="data-da"
                        type="date"
                        wire:model.live="data_da"
                        wire:change="$set('preset', 'custom')"
                        class="seg-input"
                        style="width: 10rem;"
                    />
                </div>
                <div class="seg-form-group" style="margin: 0;">
                    <label class="seg-label" for="data-a">A</label>
                    <input
                        id="data-a"
                        type="date"
                        wire:model.live="data_a"
                        wire:change="$set('preset', 'custom')"
                        class="seg-input"
                        style="width: 10rem;"
                    />
                </div>
            </div>
        </div>

        @if ($data_da !== '' || $data_a !== '')
            <p class="seg-text-muted" style="margin-top: 0.5rem; font-size: 0.8125rem;">
                Periodo: {{ $data_da ? \Carbon\Carbon::parse($data_da)->format('d/m/Y') : '—' }}
                →
                {{ $data_a ? \Carbon\Carbon::parse($data_a)->format('d/m/Y') : '—' }}
            </p>
        @endif
    </div>

    {{-- Table --}}
    <div class="seg-card seg-card-padding-none">
        <div wire:loading.flex wire:target="data_da,data_a,preset,applyPreset" style="display:none; padding: 1rem; justify-content: center; align-items: center; gap: 0.5rem; color: #64748b; font-size: 0.875rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="animation: spin 1s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>
            Aggiornamento…
        </div>

        @if (count($rows) === 0)
            <x-empty-state
                title="Nessun movimento nel periodo"
                description="Modifica l'intervallo di date per visualizzare i dati di bilancio."
            />
        @else
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Codice CER</th>
                            <th>Descrizione</th>
                            <th>UM</th>
                            <th style="text-align: right;">Carichi</th>
                            <th style="text-align: right;">Scarichi</th>
                            <th style="text-align: right;">Saldo</th>
                            <th style="text-align: right;">N. mov.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="cer-{{ $row['id'] }}">
                                <td class="seg-cell-strong">
                                    <a
                                        href="{{ route('segreteria.magazzino.show', $row['id']) }}"
                                        class="seg-link"
                                        wire:navigate
                                    >{{ $row['codice'] }}</a>
                                </td>
                                <td style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $row['descrizione'] }}">
                                    {{ $row['descrizione'] }}
                                </td>
                                <td>{{ $row['um'] }}</td>
                                <td style="text-align: right; color: #16a34a; font-variant-numeric: tabular-nums;">
                                    {{ number_format($row['carichi_kg'], 2, ',', '.') }}
                                </td>
                                <td style="text-align: right; color: #dc2626; font-variant-numeric: tabular-nums;">
                                    {{ number_format($row['scarichi_kg'], 2, ',', '.') }}
                                </td>
                                <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; color: {{ $row['saldo_kg'] >= 0 ? '#0369a1' : '#dc2626' }};">
                                    {{ number_format($row['saldo_kg'], 2, ',', '.') }}
                                </td>
                                <td style="text-align: right; color: #64748b;">
                                    {{ $row['n_movimenti'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; font-weight: 700;">
                            <td colspan="3" style="padding: 0.75rem 1rem; font-size: 0.875rem; color: #374151;">TOTALE</td>
                            <td style="text-align: right; padding: 0.75rem 1rem; color: #16a34a; font-variant-numeric: tabular-nums;">
                                {{ number_format($totals['carichi_kg'], 2, ',', '.') }}
                            </td>
                            <td style="text-align: right; padding: 0.75rem 1rem; color: #dc2626; font-variant-numeric: tabular-nums;">
                                {{ number_format($totals['scarichi_kg'], 2, ',', '.') }}
                            </td>
                            <td style="text-align: right; padding: 0.75rem 1rem; font-variant-numeric: tabular-nums; color: {{ $totals['saldo_kg'] >= 0 ? '#0369a1' : '#dc2626' }};">
                                {{ number_format($totals['saldo_kg'], 2, ',', '.') }}
                            </td>
                            <td style="text-align: right; padding: 0.75rem 1rem; color: #64748b;">
                                {{ $totals['n_movimenti'] }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</div>
