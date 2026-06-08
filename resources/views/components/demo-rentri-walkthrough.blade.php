@props(['steps' => [], 'seeded' => false, 'progress' => null])

@php
    $completed = $progress['completed'] ?? count(array_filter($steps, fn ($s) => $s['done'] ?? false));
    $total = $progress['total'] ?? count($steps);
    $percent = $progress['percent'] ?? ($total > 0 ? (int) round($completed / $total * 100) : 0);
    $certWarning = $progress['cert_warning'] ?? null;
@endphp

<div class="seg-card seg-card-padding demo-walkthrough" style="margin-bottom: 24px; border-left: 4px solid #f59e0b;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div>
            <h2 class="mag-section-title" style="margin: 0;">Prova flusso RENTRI</h2>
            <p class="seg-list-muted" style="margin: 4px 0 0;">
                Walkthrough end-to-end in modalità demo — dati isolati, solo sandbox MASE.
            </p>
        </div>
        @if (! $seeded)
            <span class="seg-badge seg-badge-warning">Esegui <code>php artisan rentri:demo-seed</code></span>
        @else
            <span class="seg-badge seg-badge-success">Fixture demo caricate</span>
        @endif
    </div>

    <div class="demo-walkthrough-progress" style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="seg-list-muted" style="font-size: 13px;">Progresso walkthrough</span>
            <strong style="font-size: 13px;">{{ $completed }}/{{ $total }} ({{ $percent }}%)</strong>
        </div>
        <div style="background: #e5e7eb; border-radius: 999px; height: 8px; overflow: hidden;">
            <div style="background: #f59e0b; height: 100%; width: {{ $percent }}%; transition: width 0.3s ease;"></div>
        </div>
    </div>

    @if ($certWarning)
        <div class="seg-badge seg-badge-warning" style="display: block; margin-bottom: 12px; padding: 8px 12px; text-align: left;">
            {{ $certWarning }}
            <a href="{{ route('segreteria.impostazioni.rentri') }}?step=2" class="demo-walkthrough-link" style="display: inline-block; margin-left: 8px;" wire:navigate>→ Impostazioni certificato</a>
        </div>
    @endif

    <ol class="demo-walkthrough-steps" style="margin: 0; padding-left: 0; list-style: none;">
        @foreach ($steps as $step)
            <li class="demo-walkthrough-step">
                <a href="{{ $step['href'] }}" class="demo-walkthrough-link" wire:navigate>
                    <span @class(['demo-walkthrough-marker', 'done' => $step['done']])>
                        @if ($step['done'])
                            ✓
                        @else
                            →
                        @endif
                    </span>
                    <span>
                        <strong>{{ $step['label'] }}</strong>
                        <span class="seg-list-muted" style="display: block; font-size: 13px;">{{ $step['description'] }}</span>
                        @if (! empty($step['mobile_href']))
                            <span class="seg-list-muted" style="display: block; font-size: 12px; margin-top: 2px;">
                                App operatore:
                                <a href="{{ $step['mobile_href'] }}" class="demo-walkthrough-link" wire:navigate>{{ parse_url($step['mobile_href'], PHP_URL_PATH) }}</a>
                            </span>
                        @endif
                    </span>
                </a>
            </li>
        @endforeach
    </ol>

    <p class="seg-list-muted" style="margin: 12px 0 0; font-size: 13px;">
        Reset scenario: <code>php artisan rentri:demo-reset</code> · Rigenera: <code>php artisan rentri:demo-seed --fresh</code>
    </p>
</div>
