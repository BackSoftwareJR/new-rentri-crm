@props([
    'preview',
])

@php
    $badgeClass = match ($preview['badge'] ?? 'warning') {
        'success' => 'seg-badge seg-badge-success',
        'danger'  => 'seg-badge seg-badge-danger',
        default   => 'seg-badge seg-badge-warning',
    };
@endphp

<div {{ $attributes->merge(['class' => 'seg-cert-preview']) }}>
    <div class="seg-cert-preview-head">
        <strong>{{ $preview['label'] }}</strong>
        <span @class([$badgeClass])>{{ ucfirst($preview['state'] ?? 'unknown') }}</span>
    </div>
    <p class="seg-list-muted seg-cert-preview-msg">{{ $preview['message'] }}</p>
    {{ $slot }}
</div>
