@props([
    'title',
    'value' => '—',
    'subtitle' => null,
    'href' => null,
    'valueColor' => null,
])

@php
    $tag = $href ? 'a' : 'div';
    $classes = trim('seg-kpi-card ' . ($href ? 'seg-kpi-card--link' : ''));
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($href) style="text-decoration:none;color:inherit;" @endif
>
    <div>
        <p class="seg-kpi-title">{{ $title }}</p>
        <p class="seg-kpi-value" @if($valueColor) style="color:{{ $valueColor }};" @endif>{{ $value }}</p>
        @if ($subtitle)
            <p class="seg-kpi-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($icon))
        <div class="seg-kpi-icon" aria-hidden="true">
            {{ $icon }}
        </div>
    @endif
</{{ $tag }}>
