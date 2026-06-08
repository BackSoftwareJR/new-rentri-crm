<div
    class="seg-notification-bell"
    wire:poll.60000ms="refresh"
    x-data="{ open: @entangle('open') }"
    @click.outside="open = false; $wire.set('open', false)"
>
    {{-- Bell trigger button --}}
    <button
        type="button"
        class="seg-icon-btn seg-noti-btn"
        wire:click="toggle"
        aria-label="Notifiche{{ $unreadCount > 0 ? ' (' . $unreadCount . ' non lette)' : '' }}"
        title="Notifiche"
        :aria-expanded="open.toString()"
    >
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        @if ($unreadCount > 0)
            <span class="seg-noti-badge" aria-hidden="true">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div
        class="seg-noti-dropdown"
        x-show="open"
        x-transition:enter="seg-noti-enter"
        x-transition:enter-start="seg-noti-enter-start"
        x-transition:enter-end="seg-noti-enter-end"
        x-cloak
        role="dialog"
        aria-modal="true"
        aria-label="Pannello notifiche"
    >
        <div class="seg-noti-header">
            <span class="seg-noti-title">Notifiche</span>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    class="seg-noti-mark-all"
                    wire:click="markAllRead"
                    wire:loading.attr="disabled"
                >
                    Segna tutte lette
                </button>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="seg-noti-empty">
                <p>Nessuna notifica.</p>
            </div>
        @else
            <ul class="seg-noti-list" role="list">
                @foreach ($notifications as $notification)
                    @php
                        $data    = $notification->data;
                        $isRead  = $notification->read_at !== null;
                        $title   = $data['title'] ?? ($data['event'] ?? 'Notifica');
                        $body    = $data['body'] ?? null;
                        $url     = $data['url'] ?? null;
                        $module  = $data['module'] ?? null;
                        $relTime = $notification->created_at->diffForHumans();
                    @endphp
                    <li
                        class="seg-noti-item{{ $isRead ? ' seg-noti-item--read' : ' seg-noti-item--unread' }}"
                        wire:key="noti-{{ $notification->id }}"
                    >
                        <div class="seg-noti-item-inner">
                            @if (! $isRead)
                                <span class="seg-noti-dot" aria-hidden="true"></span>
                            @endif
                            <div class="seg-noti-item-body">
                                @if ($url)
                                    <a href="{{ $url }}" class="seg-noti-item-title" wire:click="markOneRead('{{ $notification->id }}')">
                                        {{ $title }}
                                    </a>
                                @else
                                    <span class="seg-noti-item-title">{{ $title }}</span>
                                @endif
                                @if ($body)
                                    <p class="seg-noti-item-text">{{ $body }}</p>
                                @endif
                                <div class="seg-noti-item-meta">
                                    @if ($module)
                                        <span class="seg-badge seg-badge-info seg-noti-module">{{ $module }}</span>
                                    @endif
                                    <time datetime="{{ $notification->created_at->toIso8601String() }}" class="seg-noti-time">
                                        {{ $relTime }}
                                    </time>
                                </div>
                            </div>
                            @if (! $isRead)
                                <button
                                    type="button"
                                    class="seg-noti-dismiss"
                                    wire:click="markOneRead('{{ $notification->id }}')"
                                    aria-label="Segna come letta"
                                    title="Segna come letta"
                                >
                                    ✓
                                </button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($notifications->count() >= 10)
            <div class="seg-noti-footer">
                <a href="{{ route('segreteria.impostazioni.notifiche') }}" class="seg-noti-footer-link">
                    Impostazioni notifiche →
                </a>
            </div>
        @endif
    </div>
</div>
