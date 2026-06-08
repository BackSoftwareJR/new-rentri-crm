@props(['type' => 'success'])

@php
    $class = match ($type) {
        'error'   => 'seg-alert seg-alert-error',
        'warning' => 'seg-alert seg-alert-warning',
        default   => 'seg-alert seg-alert-success',
    };
    $role = $type === 'error' ? 'alert' : 'status';
    $live = $type === 'error' ? 'assertive' : 'polite';
@endphp

<div {{ $attributes->merge(['class' => $class, 'role' => $role, 'aria-live' => $live, 'aria-atomic' => 'true']) }}>
    {{ $slot }}
</div>
