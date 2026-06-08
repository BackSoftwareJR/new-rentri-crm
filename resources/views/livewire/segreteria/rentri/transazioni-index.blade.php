<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.rentri') }}" wire:navigate>← RENTRI</a>
            </p>
            <h1>Storico transazioni API</h1>
            <p>Log delle chiamate verso l'integrazione RENTRI (request/response).</p>
        </div>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Totale" :value="(string) $contatori['totale']" />
        <x-kpi-card title="Completate" :value="(string) $contatori['completate']" valueColor="#16a34a" />
        <x-kpi-card title="Errori" :value="(string) $contatori['errori']" valueColor="#dc2626" />
        <x-kpi-card title="Retry pianificati" :value="(string) $contatori['retry_pianificati']" valueColor="#ca8a04" />
        <x-kpi-card title="Dead-letter" :value="(string) $contatori['dead_letter']" valueColor="#dc2626" />
        <x-kpi-card title="In corso" :value="(string) $contatori['in_corso']" valueColor="#ca8a04" />
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Tipo API</label>
                <select wire:model.live="tipo_api" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach (App\Domain\Rentri\RentriTransazioneService::TIPI_API as $tipo)
                        <option value="{{ $tipo }}">{{ $service->tipoApiLabel($tipo) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Stato</label>
                <select wire:model.live="stato" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach (App\Domain\Rentri\RentriTransazioneService::STATI as $s)
                        <option value="{{ $s }}">{{ $service->statoLabel($s) }}</option>
                    @endforeach
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
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Endpoint</th>
                        <th>Metodo</th>
                        <th>Stato</th>
                        <th>Retry</th>
                        <th>Completata</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transazioni as $tx)
                        <tr wire:key="tx-{{ $tx->id }}">
                            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $service->tipoApiLabel($tx->tipo_api) }}</td>
                            <td><code>{{ $service->endpointDisplay($tx) }}</code></td>
                            <td>{{ $service->methodDisplay($tx) }}</td>
                            <td>
                                <x-badge-stato
                                    :stato="$service->statoBadgeVariant($tx->stato)"
                                    :label="$service->statoLabel($tx->stato)"
                                />
                            </td>
                            <td>
                                @if ($retryLabel = $service->retryStatusLabel($tx))
                                    <x-badge-stato
                                        :stato="$service->retryBadgeVariant($tx) ?? 'warning'"
                                        :label="$retryLabel"
                                    />
                                @else
                                    <span class="seg-list-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $tx->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('segreteria.rentri.transazioni.show', $tx) }}" class="seg-btn seg-btn-secondary seg-btn-sm" wire:navigate>Dettaglio</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="seg-table-empty">Nessuna transazione registrata. Le chiamate API RENTRI verranno tracciate qui.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transazioni->hasPages())
            <div class="seg-pagination">{{ $transazioni->links() }}</div>
        @endif
    </div>
</div>
