<div class="op-bn-cer-card {{ ($quantita[$cer->id] ?? 0) > 0 ? 'op-bn-cer-card--filled' : '' }}">
    <div class="op-bn-cer-info">
        <p class="op-bn-cer-desc">{{ $cer->descrizione }}</p>
        <p class="op-bn-cer-code">{{ $cer->codice }} · {{ $cer->um }}</p>
    </div>
    <div class="op-bn-qty-row">
        <button type="button" class="op-bn-stepper" wire:click="decrementQty({{ $cer->id }})" aria-label="Diminuisci">−</button>
        <input type="number"
            min="0"
            step="0.1"
            class="op-bn-qty-input"
            wire:model.blur="quantita.{{ $cer->id }}"
            placeholder="0" />
        <button type="button" class="op-bn-stepper" wire:click="incrementQty({{ $cer->id }})" aria-label="Aumenta">+</button>
    </div>
</div>
