<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Gestione impianti</h1>
            <p>Configura gli impianti di demolizione (multi-sito RENTRI). Fase 1: nessun filtro dati per impianto.</p>
        </div>
        @can('create', App\Models\Sito::class)
            <button type="button" wire:click="openCreateModal" class="seg-btn seg-btn-primary">
                Nuovo impianto
            </button>
        @endcan
    </div>

    <div class="seg-card seg-card-padding mag-filters" style="margin-bottom: 16px;">
        <div class="seg-form-group">
            <label class="seg-label">Cerca</label>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Nome, iscrizione o indirizzo…"
                class="seg-input"
                autocomplete="off"
            />
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>N. iscr. sito</th>
                        <th>CF operatore</th>
                        <th>Indirizzo</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siti as $sito)
                        <tr wire:key="sito-{{ $sito->id }}">
                            <td>
                                <span style="font-weight: 500;">{{ $sito->nome }}</span>
                                @if ($sito->is_default)
                                    <span class="seg-badge seg-badge-primary" style="margin-left: 6px; font-size: 10px;">Default</span>
                                @endif
                            </td>
                            <td class="seg-text-muted">{{ $sito->num_iscr_sito ?? '—' }}</td>
                            <td class="seg-text-muted">{{ $sito->cf_operatore ?? '—' }}</td>
                            <td class="seg-text-muted">{{ $sito->indirizzo ?? '—' }}</td>
                            <td>
                                @if ($sito->is_active)
                                    <span class="seg-badge seg-badge-success">Attivo</span>
                                @else
                                    <span class="seg-badge">Inattivo</span>
                                @endif
                            </td>
                            <td>
                                @can('update', $sito)
                                    <button type="button" wire:click="openEditModal({{ $sito->id }})" class="seg-btn seg-btn-secondary seg-btn-sm">
                                        Modifica
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="seg-text-muted" style="text-align: center; padding: 24px;">
                                Nessun impianto configurato.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($siti->hasPages())
            <div style="padding: 12px 16px;">{{ $siti->links() }}</div>
        @endif
    </div>

    @if ($showModal)
        <div class="seg-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="sito-modal-title" wire:keydown.escape="closeModal">
            <div class="seg-modal" style="max-width: 520px; width: 100%;">
            <div class="seg-modal-header">
                <h2 id="sito-modal-title">{{ $isEditing ? 'Modifica impianto' : 'Nuovo impianto' }}</h2>
                <button type="button" wire:click="closeModal" class="seg-modal-close" aria-label="Chiudi">×</button>
            </div>
            <form wire:submit="save" class="seg-modal-body">
                <div class="seg-form-group">
                    <label class="seg-label" for="sito-nome">Nome *</label>
                    <input id="sito-nome" type="text" wire:model="formNome" class="seg-input" />
                    @error('formNome') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label class="seg-label" for="sito-indirizzo">Indirizzo</label>
                    <input id="sito-indirizzo" type="text" wire:model="formIndirizzo" class="seg-input" />
                </div>
                <div class="seg-form-grid">
                    <div class="seg-form-group">
                        <label class="seg-label" for="sito-iscr">N. iscr. sito RENTRI</label>
                        <input id="sito-iscr" type="text" wire:model="formNumIscrSito" class="seg-input" />
                    </div>
                    <div class="seg-form-group">
                        <label class="seg-label" for="sito-cf">CF operatore</label>
                        <input id="sito-cf" type="text" wire:model="formCfOperatore" class="seg-input" maxlength="16" />
                    </div>
                </div>
                <div class="seg-form-group" style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <label class="seg-checkbox-label">
                        <input type="checkbox" wire:model="formIsActive" />
                        Attivo
                    </label>
                    <label class="seg-checkbox-label">
                        <input type="checkbox" wire:model="formIsDefault" />
                        Impianto predefinito
                    </label>
                </div>
                <div class="seg-modal-footer">
                    <button type="button" wire:click="closeModal" class="seg-btn seg-btn-secondary">Annulla</button>
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">Salva</button>
                </div>
            </form>
            </div>
        </div>
    @endif
</div>
