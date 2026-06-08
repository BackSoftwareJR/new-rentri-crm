<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions seg-registro-header">
        <div>
            <h1>Registro movimenti</h1>
            <p>Elenco cronologico carichi e scarichi per codice CER.</p>
        </div>
        <div class="seg-header-actions">
            <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm seg-no-print" wire:click="exportCsv">
                Esporta CSV
            </button>
            <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm seg-no-print" wire:click="exportExcel" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="exportExcel">Esporta Excel</span>
                <span wire:loading wire:target="exportExcel">Esportazione…</span>
            </button>
            <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm seg-no-print" onclick="window.print()">Stampa registro</button>
            <a href="{{ route('segreteria.magazzino') }}" class="seg-btn seg-btn-secondary" wire:navigate>← Magazzino</a>
        </div>
    </div>

    <div class="seg-kpi-grid seg-no-print">
        <x-kpi-card title="Movimenti" :value="(string) $aggregazioni['totale_movimenti']" />
        <x-kpi-card title="Totale carichi" :value="number_format($aggregazioni['totale_carichi_kg'], 2, ',', '.') . ' kg'" />
        <x-kpi-card title="Totale scarichi" :value="number_format($aggregazioni['totale_scarichi_kg'], 2, ',', '.') . ' kg'" />
        <x-kpi-card title="Saldo" :value="number_format($aggregazioni['saldo_kg'], 2, ',', '.') . ' kg'" />
    </div>

    <div class="seg-card seg-card-padding mag-filters seg-no-print">
        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Codice CER</label>
                <select wire:model.live="codice_cer_id" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach ($codiciCer as $c)
                        <option value="{{ $c->id }}">{{ $c->codice }} — {{ Str::limit($c->descrizione, 40) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Tipo</label>
                <select wire:model.live="tipo" class="seg-select">
                    <option value="">Tutti</option>
                    <option value="carico">Carico</option>
                    <option value="scarico">Scarico</option>
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Data da</label>
                <input type="date" wire:model.live="data_da" class="seg-input" />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Data a</label>
                <input type="date" wire:model.live="data_a" class="seg-input" />
            </div>
            <div class="seg-form-group seg-form-group--span2">
                <label class="seg-label">Cerca</label>
                <input type="search" wire:model.live.debounce.300ms="search" class="seg-input" placeholder="Note, codice CER…" />
            </div>
        </div>
        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="resetFilters">Azzera filtri</button>
    </div>

    <div class="seg-card seg-card-padding-none seg-registro-print" id="seg-registro-print">
        @if ($movimenti->isEmpty())
            <x-empty-state
                :title="$search !== '' || $tipo !== '' || $codice_cer_id || $data_da !== '' || $data_a !== '' ? 'Nessun movimento trovato' : 'Nessun movimento registrato'"
                :description="$search !== '' || $tipo !== '' || $codice_cer_id || $data_da !== '' || $data_a !== '' ? 'Modifica i filtri o azzerali per vedere più risultati.' : 'I carichi e scarichi compaiono qui dopo operazioni su magazzino, VFU o trasporti.'"
                action-label="Vai al magazzino"
                :action-href="route('segreteria.magazzino')"
            />
        @else
        <div class="seg-registro-print-title" aria-hidden="true">
            <h1>Registro movimenti</h1>
            <p>Stampa {{ now()->format('d/m/Y H:i') }} — {{ $movimenti->total() }} movimenti totali</p>
        </div>
        <div class="seg-table-wrap seg-registro-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>CER</th>
                        <th>Tipo</th>
                        <th>Peso (kg)</th>
                        <th>Note</th>
                        <th>Conformità</th>
                        <th>RENTRI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimenti as $m)
                        <tr wire:key="reg-{{ $m->id }}">
                            <td>
                                <a href="{{ route('segreteria.registro.show', $m) }}" wire:navigate>
                                    {{ $m->data_movimento->format('d/m/Y H:i') }}
                                </a>
                            </td>
                            <td class="seg-cell-strong">
                                <a href="{{ route('segreteria.magazzino.show', $m->codice_cer_id) }}" wire:navigate>{{ $m->codiceCer?->codice }}</a>
                            </td>
                            <td>
                                <x-badge-stato :stato="$m->tipo->value === 'carico' ? 'success' : 'info'" :label="ucfirst($m->tipo->value)" />
                            </td>
                            <td>{{ number_format((float) $m->peso_kg, 2, ',', '.') }}</td>
                            <td>{{ Str::limit($m->note ?? '—', 60) }}</td>
                            <td>
                                @php
                                    $conf = $conformita[$m->id] ?? ['ok' => true, 'errors' => []];
                                @endphp
                                @if ($conf['ok'])
                                    <span title="Conforme RENTRI" aria-label="Conforme RENTRI">✅</span>
                                @else
                                    <span title="{{ implode(' · ', $conf['errors']) }}" aria-label="Errori conformità RENTRI">❌</span>
                                @endif
                            </td>
                            <td>
                                @if ($m->isLocked())
                                    <x-badge-stato stato="warning" label="Bloccato" />
                                @elseif ($m->rentri_trasmesso)
                                    <x-badge-stato stato="primary" label="Trasmesso" />
                                @else
                                    <x-badge-stato stato="muted" label="Da trasmettere" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @if ($movimenti->hasPages())
            <div class="seg-pagination-wrap seg-no-print">
                {{ $movimenti->links() }}
            </div>
        @endif
    </div>
</div>
