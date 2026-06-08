@props([
    'label',
    'name',
    'hint' => null,
    'required' => false,
    'for' => null,
])

@php
    $fieldId = $for ?? $name;
@endphp

<div {{ $attributes->merge(['class' => 'seg-form-group']) }}>
    <label class="seg-label" @if ($fieldId) for="{{ $fieldId }}" @endif>
        {{ $label }}@if ($required)<span aria-hidden="true"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p class="seg-field-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="seg-field-error" role="alert">{{ $message }}</p>
    @enderror
</div>
