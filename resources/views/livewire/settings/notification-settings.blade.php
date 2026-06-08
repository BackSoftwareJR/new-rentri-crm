<div>
    @if (session('success'))
        <div class="seg-alert seg-alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="seg-alert seg-alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="seg-page-header seg-page-header--actions" style="margin-bottom: 1.5rem;">
        <div>
            <h2 class="seg-card-title" style="margin: 0;">Hub notifiche centralizzato</h2>
            <p class="seg-text-muted" style="margin: 0.5rem 0 0;">
                @if ($queued)
                    Invio in coda Horizon
                @else
                    Invio sincrono
                @endif
                · mailer effettivo: <strong>{{ $mailRuntime->effectiveMailerName() }}</strong>
            </p>
        </div>
        <div class="seg-header-actions">
            <x-notifications-mail-mode-badge />
            @if ($horizonReady)
                <span class="seg-badge seg-badge-success" id="horizon-scaling-badge">Horizon OK</span>
            @else
                <span class="seg-badge seg-badge-warning" id="horizon-scaling-badge">Horizon da verificare</span>
            @endif
            @if ($smtpVolumeReady)
                <span class="seg-badge seg-badge-success" id="smtp-volume-badge">SMTP volume OK</span>
            @elseif ($mailRuntime->isLive())
                <span class="seg-badge seg-badge-warning" id="smtp-volume-badge">SMTP volume incompleto</span>
            @endif
        </div>
    </div>

    <div class="seg-card" style="margin-bottom: 1.5rem;" id="horizon-scaling-checklist">
        <h3 class="seg-card-title">Horizon / queue scaling</h3>
        <p class="seg-text-muted" style="margin: 0 0 0.75rem;">
            Connection: <code>{{ $horizonSummary['queue_connection'] }}</code> ·
            Workers max: {{ $horizonSummary['max_workers'] }} ·
            Failed jobs: {{ $horizonSummary['failed_jobs'] }} ·
            Retry RENTRI: {{ $horizonSummary['retry_pending'] }}
            · <a href="{{ $horizonSummary['horizon_url'] }}" target="_blank" rel="noopener noreferrer">Horizon</a>
            · <code>docs/HORIZON-SCALING-RUNBOOK.md</code>
        </p>
        <ul class="seg-list" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($horizonChecklist as $item)
                <li style="padding: 0.35rem 0;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✓</span>
                    @else
                        <span aria-hidden="true">○</span>
                    @endif
                    {{ $item['label'] }}
                    @if (! $item['ok'] && ! empty($item['hint']))
                        <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card" style="margin-bottom: 1.5rem;" id="smtp-volume-checklist">
        <h3 class="seg-card-title">SMTP volume</h3>
        <p class="seg-text-muted" style="margin: 0 0 0.75rem;">
            Modalità: {{ $smtpVolumeSummary['mode'] }} ·
            Coda: {{ $smtpVolumeSummary['queued'] ? 'async' : 'sync' }} ·
            @if ($smtpVolumeSummary['daily_cap'])
                Daily cap: {{ $smtpVolumeSummary['daily_cap'] }}/giorno ·
            @endif
            Checklist: {{ $smtpVolumeSummary['ok'] }}/{{ $smtpVolumeSummary['total'] }} OK
        </p>
        <ul class="seg-list" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($smtpVolumeChecklist as $item)
                <li style="padding: 0.35rem 0;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✓</span>
                    @elseif ($item['optional'] ?? false)
                        <span aria-hidden="true">~</span>
                    @else
                        <span aria-hidden="true">○</span>
                    @endif
                    {{ $item['label'] }}
                    @if (($item['optional'] ?? false))
                        <span class="seg-text-muted" style="font-size: 0.75rem;"> (opzionale)</span>
                    @endif
                    @if (! $item['ok'] && ! empty($item['hint']))
                        <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @if ($mailRuntime->isLive() && count($mailPreflight) > 0)
        <div class="seg-card" style="margin-bottom: 1.5rem;">
            <h3 class="seg-card-title">Checklist SMTP (produzione)</h3>
            <ul class="seg-list" style="list-style: none; padding: 0; margin: 0;">
                @foreach ($mailPreflight as $item)
                    <li style="padding: 0.35rem 0;">
                        @if ($item['ok'])
                            <span aria-hidden="true">✓</span>
                        @else
                            <span aria-hidden="true">○</span>
                        @endif
                        {{ $item['label'] }}
                        @if (! $item['ok'] && ! empty($item['hint']))
                            <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['hint'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif (! $mailRuntime->isLive())
        <div class="seg-card" style="margin-bottom: 1.5rem;">
            <p class="seg-text-muted" style="margin: 0;">
                Modalità stub: le notifiche vengono registrate sul canale log <code>notifications</code> senza invio SMTP reale.
                Impostare <code>NOTIFICATIONS_LIVE=true</code> e configurare <code>MAIL_*</code> per produzione.
            </p>
        </div>
    @endif

    <div class="seg-card" style="margin-bottom: 1.5rem;">
        <h3 class="seg-card-title">Invia email di test</h3>
        <p class="seg-text-muted">Verifica configurazione SMTP o registrazione log in modalità stub.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-top: 1rem;">
            <label style="flex: 1; min-width: 14rem;">
                <span class="seg-text-muted" style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem;">Destinatario</span>
                <input
                    type="email"
                    wire:model="testRecipient"
                    class="seg-input"
                    placeholder="ops@example.it"
                    aria-label="Destinatario email di test"
                >
            </label>
            <button
                type="button"
                class="seg-btn seg-btn-secondary"
                wire:click="sendTestEmail"
                wire:loading.attr="disabled"
                @if ($mailRuntime->isLive() && ! $mailPreflightOk) disabled @endif
            >
                Invia email di test
            </button>
        </div>
        @error('testRecipient')
            <p class="seg-text-danger" style="margin: 0.5rem 0 0; font-size: 0.875rem;">{{ $message }}</p>
        @enderror
    </div>

    <form wire:submit="save" class="seg-card">
        <h2 class="seg-card-title">Eventi email per modulo</h2>
        <p class="seg-text-muted">Attiva o disattiva le notifiche per tipo di evento.</p>

        <ul class="seg-list" style="list-style: none; padding: 0; margin: 1rem 0;">
            @foreach ($events as $event)
                <li style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                    <div>
                        <strong>{{ $event->label() }}</strong>
                        <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">
                            Modulo {{ $event->module() }} · {{ $event->value }}
                        </span>
                    </div>
                    <label class="seg-switch" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input
                            type="checkbox"
                            wire:model="toggles.{{ str_replace('.', '__', $event->value) }}"
                            aria-label="Abilita {{ $event->label() }}"
                        >
                        <span>{{ ($toggles[str_replace('.', '__', $event->value)] ?? false) ? 'Attivo' : 'Off' }}</span>
                    </label>
                </li>
            @endforeach
        </ul>

        <button type="submit" class="seg-btn seg-btn-primary">Salva preferenze</button>
    </form>
</div>
