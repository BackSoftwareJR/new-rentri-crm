<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>MUD — Dichiarazioni ambientali</h1>
        <p>Elenco dichiarazioni MUD con righe aggregate dal registro cronologico.</p>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Totale" :value="(string) $contatori['totale']" />
        <x-kpi-card title="Bozze" :value="(string) $contatori['bozze']" valueColor="#ca8a04" />
        <x-kpi-card title="Completate" :value="(string) $contatori['completate']" valueColor="#2563eb" />
        <x-kpi-card title="Inviate" :value="(string) $contatori['inviate']" valueColor="#16a34a" />
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="mag-section-title">Nuova bozza</h2>
        <p class="mag-section-lead">Crea una bozza MUD per l'anno selezionato aggregando i movimenti registro.</p>
        <form wire:submit="creaBozza" class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Anno di riferimento *</label>
                <input type="number" wire:model="anno_riferimento" class="seg-input @error('anno_riferimento') is-invalid @enderror" min="2000" max="{{ (int) now()->format('Y') + 1 }}" />
                @error('anno_riferimento') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
            <div class="seg-form-group seg-form-group--span2">
                <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="creaBozza">Genera bozza da registro</span>
                    <span wire:loading wire:target="creaBozza">Generazione…</span>
                </button>
            </div>
        </form>
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-filters-row">
            <div class="seg-form-group">
                <label class="seg-label">Anno</label>
                <input type="number" wire:model.live="filtro_anno" class="seg-input" min="2000" max="{{ (int) now()->format('Y') + 1 }}" placeholder="Tutti" />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Stato</label>
                <select wire:model.live="stato" class="seg-select">
                    <option value="">Tutti</option>
                    <option value="bozza">Bozza</option>
                    <option value="completata">Completata</option>
                    <option value="inviata">Inviata</option>
                </select>
            </div>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-page-header seg-page-header--compact" style="padding: 1rem 1.25rem;">
            <h2 class="mag-section-title" style="margin: 0;">Storico invii e dichiarazioni</h2>
        </div>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Anno</th>
                        <th>Stato</th>
                        <th>Righe CER</th>
                        <th>Completata il</th>
                        <th>Inviata il</th>
                        <th>Protocollo</th>
                        <th>Autore</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dichiarazioni as $d)
                        <tr wire:key="mud-{{ $d->id }}">
                            <td class="seg-cell-strong">{{ $d->anno_riferimento }}</td>
                            <td>
                                <x-badge-stato :stato="$service->statoBadgeVariant($d->stato)" :label="$service->statoLabel($d->stato)" />
                            </td>
                            <td>{{ count($d->righe ?? []) }}</td>
                            <td>{{ $d->completata_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $d->inviata_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $d->invio_protocollo ?? '—' }}</td>
                            <td>{{ $d->user?->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('segreteria.mud.show', $d) }}" class="seg-btn seg-btn-secondary seg-btn-sm" wire:navigate>Dettaglio</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="seg-table-empty">Nessuna dichiarazione MUD. Genera una bozza per l'anno corrente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($dichiarazioni->hasPages())
            <div class="seg-pagination">{{ $dichiarazioni->links() }}</div>
        @endif
    </div>
</div>
