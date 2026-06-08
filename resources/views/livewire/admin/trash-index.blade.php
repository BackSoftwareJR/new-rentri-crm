<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>Cestino</h1>
        <p>Record eliminati (soft-delete). Ripristina entro il periodo di retention.</p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            @foreach (['vfu' => 'VFU', 'fatture' => 'Fatture', 'anagrafiche' => 'Anagrafiche'] as $key => $label)
                <button
                    type="button"
                    wire:click="$set('tab', '{{ $key }}')"
                    @class(['seg-btn', 'seg-btn-primary' => $tab === $key, 'seg-btn-secondary' => $tab !== $key])
                >
                    {{ $label }}
                    <span class="seg-badge" style="margin-left: 6px;">{{ $counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($tab === 'vfu')
        <div class="seg-card seg-card-padding-none">
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Targa</th>
                            <th>Veicolo</th>
                            <th>Eliminato il</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vfuItems as $vfu)
                            <tr wire:key="trash-vfu-{{ $vfu->id }}">
                                <td>{{ $vfu->targa ?? '—' }}</td>
                                <td>{{ $vfu->veicoloLabel() }}</td>
                                <td class="seg-text-muted">{{ $vfu->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button type="button" wire:click="restoreVfu({{ $vfu->id }})" class="seg-btn seg-btn-secondary seg-btn-sm">
                                        Ripristina
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="seg-table-empty">Nessun VFU nel cestino.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($tab === 'fatture')
        <div class="seg-card seg-card-padding-none">
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Cliente</th>
                            <th>Eliminato il</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fatture as $fattura)
                            <tr wire:key="trash-fattura-{{ $fattura->id }}">
                                <td>{{ $fattura->numero_fattura }}</td>
                                <td>{{ $fattura->anagrafica?->ragione_sociale ?? '—' }}</td>
                                <td class="seg-text-muted">{{ $fattura->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button type="button" wire:click="restoreFattura({{ $fattura->id }})" class="seg-btn seg-btn-secondary seg-btn-sm">
                                        Ripristina
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="seg-table-empty">Nessuna fattura nel cestino.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="seg-card seg-card-padding-none">
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Ragione sociale</th>
                            <th>Tipo</th>
                            <th>Eliminato il</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($anagrafiche as $anagrafica)
                            <tr wire:key="trash-anagrafica-{{ $anagrafica->id }}">
                                <td>{{ $anagrafica->ragione_sociale }}</td>
                                <td>{{ $anagrafica->tipo }}</td>
                                <td class="seg-text-muted">{{ $anagrafica->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button type="button" wire:click="restoreAnagrafica({{ $anagrafica->id }})" class="seg-btn seg-btn-secondary seg-btn-sm">
                                        Ripristina
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="seg-table-empty">Nessuna anagrafica nel cestino.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
