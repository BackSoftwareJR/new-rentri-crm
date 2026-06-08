<div
    x-data="{
        open: @entangle('open'),
        selected: @entangle('selectedIndex'),
        handleKeydown(event) {
            if (!this.open) return;
            if (event.key === 'ArrowDown') { event.preventDefault(); $wire.moveSelection(1); }
            if (event.key === 'ArrowUp') { event.preventDefault(); $wire.moveSelection(-1); }
            if (event.key === 'Enter') { event.preventDefault(); $wire.selectResult(this.selected); }
            if (event.key === 'Escape') { event.preventDefault(); $wire.close(); }
        }
    }"
    x-on:keydown.window="handleKeydown($event)"
    x-on:open-global-search.window="$wire.openSearch()"
    x-on:global-search-opened.window="$nextTick(() => $refs.searchInput?.focus())"
>
    @if ($open)
        <div class="gs-overlay" wire:click="close" aria-hidden="true"></div>

        <div
            class="gs-modal"
            role="dialog"
            aria-modal="true"
            aria-label="Ricerca globale"
            wire:click.stop
        >
            <div class="gs-search-bar">
                <svg class="gs-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    x-ref="searchInput"
                    type="search"
                    wire:model.live.debounce.300ms="query"
                    class="gs-search-input"
                    placeholder="Cerca VFU, anagrafiche, fatture, trasporti, FIR…"
                    autocomplete="off"
                    aria-label="Query ricerca globale"
                />
                <kbd class="gs-kbd" aria-hidden="true">Esc</kbd>
            </div>

            <div class="gs-results" role="listbox" aria-label="Risultati ricerca">
                @if (mb_strlen(trim($query)) < 2)
                    <p class="gs-empty">Digita almeno 2 caratteri per cercare.</p>
                @elseif ($results === [])
                    <p class="gs-empty">Nessun risultato per «{{ $query }}».</p>
                @else
                    @php $offset = 0; @endphp
                    @foreach ($results as $group)
                        <div class="gs-group" wire:key="gs-group-{{ $group['type'] }}">
                            <div class="gs-group-label">
                                @include('livewire.partials.global-search-icon', ['icon' => $group['icon']])
                                {{ $group['label'] }}
                            </div>
                            @foreach ($group['items'] as $item)
                                @php $index = $offset++; @endphp
                                <button
                                    type="button"
                                    class="gs-result {{ $selectedIndex === $index ? 'gs-result--active' : '' }}"
                                    wire:click="selectResult({{ $index }})"
                                    wire:key="gs-item-{{ $group['type'] }}-{{ $item['id'] }}"
                                    role="option"
                                    aria-selected="{{ $selectedIndex === $index ? 'true' : 'false' }}"
                                >
                                    <span class="gs-result-label">{{ $item['label'] }}</span>
                                    @if (! empty($item['subtitle']))
                                        <span class="gs-result-sub">{{ $item['subtitle'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="gs-footer">
                <span><kbd>↑</kbd><kbd>↓</kbd> naviga</span>
                <span><kbd>↵</kbd> apri</span>
                <span><kbd>Esc</kbd> chiudi</span>
            </div>
        </div>
    @endif
</div>
