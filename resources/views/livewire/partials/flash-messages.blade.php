<div id="seg-flash-region" role="status" aria-live="polite" aria-atomic="true" aria-relevant="additions">
@if (session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning">{{ session('warning') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error">{{ session('error') }}</x-alert>
    @if (session('xfir_validation_errors'))
        <x-alert type="error" style="margin-top: -8px;">
            <strong>Errori conformità xFIR (XSD MASE v1.0):</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                @foreach (session('xfir_validation_errors') as $xfirError)
                    <li>{{ $xfirError }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
@endif
</div>
