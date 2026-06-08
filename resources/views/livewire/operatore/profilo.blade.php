<div>
    @include('livewire.partials.flash-messages')

    <p class="op-section-lead">Dati account operatore (email non modificabile).</p>

    <div class="op-card" style="padding: 20px;">
        <form wire:submit="salva" class="op-profilo-form">
            <div style="margin-bottom: 16px;">
                <label for="profilo-name" style="display: block; font-size: 14px; font-weight: 600; color: #3c3c43; margin-bottom: 6px;">Nome</label>
                <input id="profilo-name" type="text" wire:model="name" class="op-bn-search" autocomplete="name" />
                @error('name')
                    <p style="color: #dc2626; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="profilo-email" style="display: block; font-size: 14px; font-weight: 600; color: #3c3c43; margin-bottom: 6px;">Email</label>
                <input id="profilo-email" type="email" value="{{ $email }}" class="op-bn-search" readonly disabled style="background: #f2f2f7; color: #8e8e93;" />
            </div>

            <button type="submit" class="op-btn op-btn-primary op-btn-full" wire:loading.attr="disabled">
                Salva modifiche
            </button>
        </form>
    </div>
</div>
