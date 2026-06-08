@props(['settingsUrl' => null])

@php
    $href = $settingsUrl ?? (Route::has('segreteria.impostazioni.rentri') ? route('segreteria.impostazioni.rentri') : url('/segreteria/impostazioni/rentri'));
@endphp

<div class="seg-alert seg-alert-warning" role="alert" style="margin-bottom: 1rem;">
    <strong>RENTRI produzione — API ancora in stub</strong>
    <p style="margin: 0.35rem 0 0;">
        L'ambiente è impostato su <strong>produzione</strong> ma le chiamate API ministeriali sono ancora simulate.
        Completare la checklist e il passaggio live in
        <a href="{{ $href }}" class="seg-link" wire:navigate>Impostazioni RENTRI</a>
        (step «Passaggio produzione»).
    </p>
</div>
