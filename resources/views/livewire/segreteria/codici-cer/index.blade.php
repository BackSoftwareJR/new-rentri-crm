<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Codici CER</h1>
            <p>Catalogo codici europei dei rifiuti e giacenze magazzino.</p>
        </div>
        <div class="seg-header-actions">
            <button type="button" class="seg-btn seg-btn-primary" wire:click="openCreate">+ Nuovo codice</button>
            <a href="{{ route('segreteria.codici-cer.create') }}" class="seg-btn seg-btn-secondary" wire:navigate>Pagina completa</a>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Descrizione</th>
                        <th>Categoria</th>
                        <th>UM</th>
                        <th>Giacenza (kg)</th>
                        <th>Stato</th>
                        <th class="seg-table-actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($codici as $c)
                        <tr wire:key="cer-{{ $c->id }}">
                            <td class="seg-cell-strong">{{ $c->codice }}</td>
                            <td>{{ $c->descrizione }}</td>
                            <td>
                                <x-badge-stato :stato="$c->categoria === 'pericoloso' ? 'danger' : 'muted'" :label="$c->categoria === 'pericoloso' ? 'Pericoloso' : 'Altro'" />
                            </td>
                            <td>{{ $c->um }}</td>
                            <td>{{ number_format((float) ($c->magazzino?->quantita_attuale_kg ?? 0), 2, ',', '.') }}</td>
                            <td>
                                <x-badge-stato :stato="$c->attivo ? 'success' : 'muted'" :label="$c->attivo ? 'Attivo' : 'Disattivo'" />
                            </td>
                            <td class="seg-table-actions">
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="openEdit({{ $c->id }})">Modifica</button>
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm seg-btn-danger"
                                    wire:click="delete({{ $c->id }})"
                                    wire:confirm="Eliminare o disattivare il codice {{ $c->codice }}?">
                                    Elimina
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="seg-table-empty">Nessun codice CER.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($showModal)
        <div class="seg-modal-overlay" wire:keydown.escape="closeModal">
            <div class="seg-modal" role="dialog" aria-modal="true">
                <div class="seg-modal-header">
                    <h2>{{ $editingId ? 'Modifica codice CER' : 'Nuovo codice CER' }}</h2>
                    <button type="button" class="seg-modal-close" wire:click="closeModal" aria-label="Chiudi">&times;</button>
                </div>
                <form wire:submit="save" class="seg-modal-body">
                    <div class="seg-form-grid">
                        <div class="seg-form-group">
                            <label class="seg-label">Codice *</label>
                            <input type="text" wire:model="codice" class="seg-input @error('codice') is-invalid @enderror" />
                            @error('codice') <p class="seg-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-label">Categoria *</label>
                            <select wire:model="categoria" class="seg-select">
                                <option value="pericoloso">Pericoloso</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>
                        <div class="seg-form-group seg-form-group--span2">
                            <label class="seg-label">Descrizione *</label>
                            <input type="text" wire:model="descrizione" class="seg-input @error('descrizione') is-invalid @enderror" />
                            @error('descrizione') <p class="seg-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-label">Unità di misura *</label>
                            <select wire:model="um" class="seg-select">
                                <option value="kg">kg</option>
                                <option value="litri">litri</option>
                                <option value="pezzi">pezzi</option>
                            </select>
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-label">Limite kg</label>
                            <input type="number" step="0.01" min="0" wire:model="limite_kg" class="seg-input" />
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-checkbox">
                                <input type="checkbox" wire:model="attivo" />
                                <span>Attivo</span>
                            </label>
                        </div>
                    </div>
                    <div class="seg-modal-footer">
                        <button type="button" class="seg-btn seg-btn-secondary" wire:click="closeModal">Annulla</button>
                        <button type="submit" class="seg-btn seg-btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
