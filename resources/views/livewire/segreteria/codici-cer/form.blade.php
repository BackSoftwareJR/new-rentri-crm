<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Definizione codice CER e parametri magazzino.</p>
        </div>
        <a href="{{ route('segreteria.codici-cer.index') }}" class="seg-btn seg-btn-secondary" wire:navigate>Indietro</a>
    </div>

    <form wire:submit="save" class="seg-card seg-card-padding seg-form-stack">
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
        <div class="seg-form-actions">
            <button type="submit" class="seg-btn seg-btn-primary">Salva</button>
        </div>
    </form>
</div>
