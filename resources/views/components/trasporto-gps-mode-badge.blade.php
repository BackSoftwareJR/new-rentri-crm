@props([
    'label' => null,
    'variant' => null,
])

@php
    $runtime = app(\App\Domain\Trasporti\TrasportoGpsRuntimeModeService::class);
    $displayLabel = $label ?? $runtime->modeDisplayLabel();
    $displayVariant = $variant ?? $runtime->modeDisplayVariant();
@endphp

<x-badge-stato :stato="$displayVariant" :label="$displayLabel" {{ $attributes }} />
