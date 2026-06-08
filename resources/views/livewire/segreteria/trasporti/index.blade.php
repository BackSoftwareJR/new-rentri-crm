<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Trasporti rifiuti</h1>
            <p>Gestione trasporti collegati alle richieste di svuotamento serbatoio.</p>
        </div>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Totale" :value="(string) $contatori['totale']" />
        <x-kpi-card title="In preparazione" :value="(string) $contatori['in_preparazione']" valueColor="#ca8a04" />
        <x-kpi-card title="In transito" :value="(string) $contatori['in_transito']" valueColor="#2563eb" />
        <x-kpi-card title="Completati" :value="(string) $contatori['completati']" valueColor="#16a34a" />
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Provider GPS</h2>
            <x-trasporto-gps-mode-badge />
            <span class="seg-text-muted" style="font-size: 13px;">
                Switch live: {{ $gpsSwitch['ready'] ? 'pronto' : 'non pronto' }}
                ({{ $gpsSwitch['ok'] }}/{{ $gpsSwitch['total'] }})
            </span>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
            CLI: <code>php artisan trasporto:gps-switch-check --dry-run</code>
            · Probe: <code>--probe</code>
            · Runbook: <code>docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md</code>
        </p>
        @if ($gpsSwitch['field_map_preset'])
            <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
                Preset field map attivo: <strong>{{ $gpsSwitch['field_map_preset'] }}</strong>
            </p>
        @endif
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0 0 12px;">
            @foreach ($gpsChecklist as $item)
                <li style="margin-bottom: 6px; font-size: 13px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    @if ($item['optional'])
                        <span class="seg-text-muted">(opz.)</span>
                    @endif
                    @if (! $item['ok'] && $item['hint'])
                        <span class="seg-text-muted"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <details style="font-size: 13px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Preset field map produzione</summary>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($gpsPresets as $key => $preset)
                    <li><code>{{ $key }}</code> — {{ $preset['label'] }}</li>
                @endforeach
            </ul>
        </details>
        <details style="font-size: 13px; margin-top: 8px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Rollback stub</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($gpsRollback as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
    </div>

    <div class="seg-card seg-card-padding mag-filters">
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
                <label class="seg-label">Stato</label>
                <select wire:model.live="stato" class="seg-select">
                    <option value="">Tutti</option>
                    <option value="in_preparazione">In preparazione</option>
                    <option value="in_transito">In transito</option>
                    <option value="completato">Completato</option>
                    <option value="annullato">Annullato</option>
                </select>
            </div>
            <div class="seg-form-group seg-form-group--span2">
                <label class="seg-label">Cerca</label>
                <input type="search" wire:model.live.debounce.300ms="search" class="seg-input" placeholder="CER, destinatario, note…" />
            </div>
        </div>
        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="resetFilters">Azzera filtri</button>
    </div>

    <div class="seg-card seg-card-padding-none">
        @if ($trasporti->isEmpty())
            <x-empty-state
                :title="$search !== '' || $stato !== '' || $codice_cer_id ? 'Nessun trasporto trovato' : 'Nessun trasporto'"
                :description="$search !== '' || $stato !== '' || $codice_cer_id ? 'Prova a modificare i filtri o azzerali.' : 'Le richieste di svuotamento dal magazzino generano automaticamente un trasporto.'"
                action-label="Vai al magazzino"
                :action-href="route('segreteria.magazzino')"
            />
        @else
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>CER</th>
                        <th>Destinatario</th>
                        <th>Quantità</th>
                        <th>Stato</th>
                        <th>Creato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trasporti as $t)
                        <tr wire:key="tr-{{ $t->id }}">
                            <td class="seg-cell-strong">{{ $t->id }}</td>
                            <td>{{ $t->codiceCer?->codice }}</td>
                            <td>{{ $t->destinatario?->ragione_sociale ?? '—' }}</td>
                            <td>{{ number_format((float) $t->quantita_kg, 2, ',', '.') }} {{ $t->codiceCer?->um ?? 'kg' }}</td>
                            <td>
                                <x-badge-stato :stato="$service->statoBadgeVariant($t->stato)" :label="$service->statoLabel($t->stato)" />
                            </td>
                            <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="seg-table-actions">
                                <a href="{{ route('segreteria.trasporti.show', $t) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Dettaglio</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @if ($trasporti->hasPages())
            <div class="seg-pagination">{{ $trasporti->links() }}</div>
        @endif
    </div>
</div>
