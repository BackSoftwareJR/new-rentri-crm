@props([
    'stato' => 'muted',
    'label' => null,
])

@php
    $variants = ['success', 'info', 'warning', 'danger', 'muted', 'primary'];
    $variant = in_array($stato, $variants, true) ? $stato : 'muted';
    $text = $label ?? $slot;
@endphp

<span {{ $attributes->merge(['class' => "badge-stato badge-stato--{$variant}"]) }}>
    {{ $text }}
</span>
