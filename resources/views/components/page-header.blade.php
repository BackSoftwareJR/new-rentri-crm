@props([
    'title',
    'lead' => null,
    'backHref' => null,
    'backLabel' => null,
])

<div @class(['seg-page-header', 'seg-page-header--actions' => isset($actions)])>
    <div>
        @if ($backHref)
            <p class="mag-back">
                <a href="{{ $backHref }}" wire:navigate>{{ $backLabel ?? '← Indietro' }}</a>
            </p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($lead)
            <p>{{ $lead }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="seg-header-actions">{{ $actions }}</div>
    @endisset
</div>
