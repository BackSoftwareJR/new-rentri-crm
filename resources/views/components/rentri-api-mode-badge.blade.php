@props([
    'label' => null,
    'variant' => null,
    'settings' => null,
])

@php
    $runtime = app(\App\Domain\Rentri\RentriRuntimeModeService::class);
    $displayLabel = $label ?? $runtime->apiModeDisplayLabel($settings);
    $displayVariant = $variant ?? $runtime->apiModeDisplayVariant($settings);
@endphp

<x-badge-stato :stato="$displayVariant" :label="$displayLabel" {{ $attributes }} />
