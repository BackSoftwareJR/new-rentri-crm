<section class="seg-card seg-card-padding" style="margin-top:16px;">
    <h2 class="seg-section-title">{{ $title ?? 'Storico attività' }}</h2>

    @if ($events->isEmpty())
        <p class="seg-text-muted" style="margin:0;">Nessun evento registrato.</p>
    @else
        <ul class="seg-activity-timeline">
            @foreach ($events as $event)
                @php $meta = $audit->eventPresentation($event); @endphp
                <li class="seg-activity-timeline-item" wire:key="tl-{{ $event->id }}">
                    <span class="seg-activity-timeline-icon" style="background:{{ $meta['color'] }}1a;color:{{ $meta['color'] }};border-color:{{ $meta['color'] }};">
                        {{ $meta['icon'] }}
                    </span>
                    <div class="seg-activity-timeline-body">
                        <div class="seg-activity-timeline-head">
                            <strong>{{ $event->description }}</strong>
                            <time datetime="{{ $event->created_at?->toIso8601String() }}"
                                  title="{{ $event->created_at?->format('d/m/Y H:i:s') }}"
                                  class="seg-activity-timeline-time">
                                {{ $event->created_at?->diffForHumans() }}
                            </time>
                        </div>
                        <p class="seg-activity-timeline-meta">
                            {{ $event->causer?->name ?? 'Sistema' }}
                            @if ($meta['detail'])
                                · {{ $meta['detail'] }}
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
