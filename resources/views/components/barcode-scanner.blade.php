@props([
    'target' => null,
    'buttonLabel' => 'Scansiona',
    'buttonClass' => 'seg-btn seg-btn-secondary seg-btn-sm',
])

<div
    x-data="barcodeScanner(@js($target))"
    @barcode-detected.window="if (!$event.detail.target || $event.detail.target === targetField) { $dispatch('scanner-result', $event.detail) }"
    {{ $attributes->class(['barcode-scanner']) }}
>
    <button type="button" class="{{ $buttonClass }}" x-on:click="openScanner()" aria-label="{{ $buttonLabel }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;" aria-hidden="true">
            <path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/>
            <path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
            <rect x="7" y="7" width="10" height="10" rx="1"/>
        </svg>
        {{ $buttonLabel }}
    </button>

    <div
        x-show="open"
        x-cloak
        style="position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;"
        x-on:keydown.escape.window="closeScanner()"
    >
        <div style="background:#fff;border-radius:12px;width:100%;max-width:420px;overflow:hidden;">
            <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                <strong style="font-size:15px;">Scansiona codice</strong>
                <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" x-on:click="closeScanner()">Chiudi</button>
            </div>
            <div style="position:relative;background:#000;aspect-ratio:4/3;">
                <video x-ref="video" playsinline muted style="width:100%;height:100%;object-fit:cover;"></video>
                <canvas x-ref="canvas" style="display:none;"></canvas>
                <div style="position:absolute;inset:20%;border:2px solid rgba(255,255,255,.8);border-radius:8px;pointer-events:none;"></div>
            </div>
            <p style="margin:0;padding:12px 16px;font-size:13px;color:#6b7280;">
                Inquadra il QR/barcode. La fotocamera si chiude automaticamente dopo la lettura.
            </p>
            <p x-show="error" x-text="error" style="margin:0;padding:0 16px 12px;font-size:13px;color:#dc2626;"></p>
        </div>
    </div>
</div>
