@props([
    'show' => false,
    'title' => '',
    'closeAction' => null,
])

@if ($show)
    <div class="seg-modal-overlay" wire:keydown.escape="{{ $closeAction }}">
        <div
            class="seg-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="seg-modal-title-{{ md5($title) }}"
            data-seg-modal
            data-seg-modal-open="1"
            tabindex="-1"
        >
            <div class="seg-modal-header">
                <h2 id="seg-modal-title-{{ md5($title) }}">{{ $title }}</h2>
                @if ($closeAction)
                    <button type="button" class="seg-modal-close" wire:click="{{ $closeAction }}" aria-label="Chiudi">&times;</button>
                @endif
            </div>
            <div class="seg-modal-body">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif
