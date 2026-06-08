@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'seg-btn seg-btn-' . $variant;
    if ($size === 'sm') {
        $classes .= ' seg-btn-sm';
    }
@endphp

@if ($href)
    <a {{ $attributes->merge(['class' => $classes, 'href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => $type]) }}>{{ $slot }}</button>
@endif
