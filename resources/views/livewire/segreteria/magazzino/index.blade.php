<div>
    @include('livewire.partials.flash-messages')

    <x-page-header title="Magazzino rifiuti" lead="Giacenze per codice CER, soglie serbatoio e carichi manuali.">
        <x-slot name="actions">
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportCsv">
                Esporta CSV
            </button>
            <x-btn variant="secondary" :href="route('segreteria.registro-movimenti')" wire:navigate>Registro movimenti</x-btn>
        </x-slot>
    </x-page-header>

    @if ($sottoMinimo->isNotEmpty())
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="alert" style="border-color:#dc2626;background:#fef2f2;margin-bottom:16px;">
            <strong style="color:#dc2626;">Alert giacenza minima:</strong>
            <x-badge-stato stato="danger" label="{{ $sottoMinimo->count() }} serbatoio/i sotto soglia" />
            <span class="seg-muted-inline">
                @foreach ($sottoMinimo->take(4) as $alert)
                    {{ $alert['codice'] }} ({{ number_format($alert['quantita_attuale_kg'], 2, ',', '.') }} / min {{ number_format($alert['soglia_minima_kg'], 2, ',', '.') }} kg)@if (! $loop->last), @endif
                @endforeach
                @if ($sottoMinimo->count() > 4)
                    … +{{ $sottoMinimo->count() - 4 }}
                @endif
            </span>
        </div>
    @endif

    <div class="seg-kpi-grid">
        <x-kpi-card title="Totale giacenza" :value="number_format($summary['totale_kg'], 2, ',', '.') . ' kg'" subtitle="{{ $summary['codici_attivi'] }} serbatoi attivi" />
        <x-kpi-card title="In attenzione (≥70%)" :value="(string) $summary['in_attenzione']" valueColor="#ca8a04" subtitle="Soglia di attenzione raggiunta" />
        <x-kpi-card title="Soglia superata" :value="(string) $summary['soglia_superata']" valueColor="#dc2626" subtitle="Capacità massima superata" />
    </div>

    <div class="mag-toolbar seg-card seg-card-padding">
        <div class="mag-toolbar-search">
            <label class="seg-label" for="mag-search">Cerca serbatoio</label>
            <input id="mag-search" type="search" wire:model.live.debounce.300ms="search" class="seg-input" placeholder="Codice, descrizione, categoria…" />
        </div>
        <div class="mag-toolbar-views" role="group" aria-label="Vista elenco">
            <button type="button" class="seg-btn seg-btn-sm {{ $viewMode === 'grid' ? 'seg-btn-primary' : 'seg-btn-secondary' }}" wire:click="$set('viewMode', 'grid')">Griglia</button>
            <button type="button" class="seg-btn seg-btn-sm {{ $viewMode === 'list' ? 'seg-btn-primary' : 'seg-btn-secondary' }}" wire:click="$set('viewMode', 'list')">Lista</button>
        </div>
    </div>

    @if ($viewMode === 'grid')
        <div class="mag-grid">
            @forelse ($serbatoi as $s)
                <a href="{{ route('segreteria.magazzino.show', $s['id']) }}" class="mag-tank-card" wire:navigate wire:key="tank-{{ $s['id'] }}">
                    <div class="mag-tank-card-head">
                        <span class="mag-tank-code">{{ $s['codice'] }}</span>
                        <x-badge-stato :stato="$magazzino->statoBadgeVariant($s['stato'])" :label="$magazzino->statoBadgeLabel($s['stato'])" />
                    </div>
                    <p class="mag-tank-desc">{{ $s['descrizione'] }}</p>
                    <div class="mag-tank-visual" aria-hidden="true">
                        @php $fill = $s['percentuale'] !== null ? min($s['percentuale'], 100) : 0; @endphp
                        <div class="mag-tank-fill mag-tank-fill--{{ $s['stato'] }}" style="height: {{ $fill }}%"></div>
                    </div>
                    <div class="mag-tank-stats">
                        <span><strong>{{ number_format($s['quantita_attuale_kg'], 2, ',', '.') }}</strong> {{ $s['um'] }}</span>
                        @if ($s['limite_kg'])
                            <span class="mag-tank-limit">/ {{ number_format($s['limite_kg'], 0, ',', '.') }} {{ $s['um'] }}</span>
                            @if ($s['percentuale'] !== null)
                                <span class="mag-tank-pct">{{ number_format($s['percentuale'], 1, ',', '.') }}%</span>
                            @endif
                        @else
                            <span class="mag-tank-limit">— limite non impostato</span>
                        @endif
                    </div>
                </a>
            @empty
                <p class="mag-empty">Nessun serbatoio trovato.</p>
            @endforelse
        </div>
    @else
        <div class="seg-card seg-card-padding-none">
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Codice CER</th>
                            <th>Descrizione</th>
                            <th>Categoria</th>
                            <th>Giacenza</th>
                            <th>Limite</th>
                            <th>%</th>
                            <th>Stato</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($serbatoi as $s)
                            <tr wire:key="row-{{ $s['id'] }}">
                                <td class="seg-cell-strong">{{ $s['codice'] }}</td>
                                <td>{{ $s['descrizione'] }}</td>
                                <td>
                                    <x-badge-stato :stato="$s['categoria'] === 'pericoloso' ? 'danger' : 'muted'" :label="$s['categoria'] === 'pericoloso' ? 'Pericoloso' : 'Altro'" />
                                </td>
                                <td>{{ number_format($s['quantita_attuale_kg'], 2, ',', '.') }} {{ $s['um'] }}</td>
                                <td>{{ $s['limite_kg'] ? number_format($s['limite_kg'], 0, ',', '.') . ' ' . $s['um'] : '—' }}</td>
                                <td>{{ $s['percentuale'] !== null ? number_format($s['percentuale'], 1, ',', '.') . '%' : '—' }}</td>
                                <td>
                                    <x-badge-stato :stato="$magazzino->statoBadgeVariant($s['stato'])" :label="$magazzino->statoBadgeLabel($s['stato'])" />
                                </td>
                                <td class="seg-table-actions">
                                    <a href="{{ route('segreteria.magazzino.show', $s['id']) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Dettaglio</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="seg-table-empty">Nessun serbatoio trovato.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
