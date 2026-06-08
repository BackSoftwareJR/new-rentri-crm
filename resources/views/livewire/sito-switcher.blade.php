<div>
@if ($showSwitcher)
<div
    class="seg-sito-switcher"
    x-data="{ open: @entangle('open') }"
    @click.outside="open = false; $wire.set('open', false)"
>
    <button
        type="button"
        class="seg-sito-switcher-btn"
        wire:click="toggle"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        aria-label="Seleziona impianto"
        title="Cambia impianto"
    >
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/>
            <path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/>
            <path d="M12 12v4"/>
            <path d="M8 12h8"/>
        </svg>
        <span class="seg-sito-switcher-label">{{ $activeSito?->nome ?? 'Impianto' }}</span>
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div
        class="seg-sito-switcher-dropdown"
        x-show="open"
        x-transition
        x-cloak
        role="listbox"
        aria-label="Seleziona impianto"
    >
        @foreach ($siti as $sito)
            <button
                type="button"
                class="seg-sito-switcher-item {{ $activeSito?->id === $sito->id ? 'is-active' : '' }}"
                wire:click="switchSito({{ $sito->id }})"
                role="option"
                @if ($activeSito?->id === $sito->id) aria-selected="true" @endif
            >
                <span class="seg-sito-switcher-item-name">{{ $sito->nome }}</span>
                @if ($sito->num_iscr_sito)
                    <span class="seg-sito-switcher-item-meta">{{ $sito->num_iscr_sito }}</span>
                @endif
            </button>
        @endforeach
    </div>
</div>
@endif
</div>
