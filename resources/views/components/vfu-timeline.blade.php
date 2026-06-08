@props(['steps'])

<nav class="seg-vfu-timeline" aria-label="Avanzamento pratica VFU">
    <ol class="seg-vfu-timeline-list">
        @foreach ($steps as $step)
            <li @class([
                'seg-vfu-timeline-item',
                'seg-vfu-timeline-item--'.$step['status'],
            ]) wire:key="vfu-step-{{ $step['key'] }}">
                <span class="seg-vfu-timeline-marker" aria-hidden="true"></span>
                <div class="seg-vfu-timeline-body">
                    <strong class="seg-vfu-timeline-label">{{ $step['label'] }}</strong>
                    @if (! empty($step['date']))
                        <span class="seg-vfu-timeline-date">{{ $step['date'] }}</span>
                    @endif
                    @if (! empty($step['hint']))
                        <span class="seg-vfu-timeline-hint">{{ $step['hint'] }}</span>
                    @endif
                    @if ($step['status'] === 'current')
                        <span class="seg-badge seg-badge-info seg-vfu-timeline-badge">In corso</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
