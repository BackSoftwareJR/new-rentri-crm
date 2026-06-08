@props([
    'items' => [],
    'current' => null,
    'variant' => 'seg',
])

@php
    $wrapClass = match ($variant) {
        'shop' => 'shop-breadcrumb',
        'op' => 'op-breadcrumb',
        default => 'seg-page-breadcrumb',
    };
@endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => $wrapClass]) }}>
    @foreach ($items as $label => $url)
        <a href="{{ $url }}" wire:navigate>{{ $label }}</a>
        <span aria-hidden="true"> / </span>
    @endforeach
    @if ($current)
        <span @class(['seg-breadcrumb-current' => $variant === 'seg'])>{{ $current }}</span>
    @endif
</nav>
