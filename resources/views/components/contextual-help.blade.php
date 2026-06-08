@props([
    'title',
    'modalId' => null,
])

@php
    $id = $modalId ?? 'seg-help-' . md5($title);
@endphp

<div {{ $attributes->merge(['class' => 'seg-contextual-help']) }}>
    <details class="seg-contextual-help-details">
        <summary class="seg-contextual-help-trigger" aria-label="Aiuto: {{ $title }}">?</summary>
        <div class="seg-contextual-help-tooltip" role="note">
            <strong>{{ $title }}</strong>
            <div class="seg-contextual-help-text">{{ $slot }}</div>
            <button type="button" class="seg-contextual-help-more" data-seg-help-open="{{ $id }}">Apri guida</button>
        </div>
    </details>

    <dialog id="{{ $id }}" class="seg-contextual-help-dialog" aria-labelledby="{{ $id }}-title">
        <div class="seg-contextual-help-dialog-inner">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <div class="seg-contextual-help-dialog-body">{{ $slot }}</div>
            <form method="dialog">
                <button type="submit" class="seg-btn seg-btn-secondary">Chiudi</button>
            </form>
        </div>
    </dialog>
</div>
