<div id="ha-status">
    <div class="seg-page-header">
        <h1>HA multi-istanza — backup e failover</h1>
        <p>
            Checklist backup DB, restore drill e sessioni Redis condivise.
            Runbook: <code>docs/HA-BACKUP-DRILL-RUNBOOK.md</code>
        </p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span class="{{ $readyBadge }}" id="ha-ready-badge">
                HA produzione: {{ $summary['ready'] ? 'Pronto' : 'Incompleto' }}
            </span>
            <span class="seg-text-muted" style="font-size: 13px;">
                Session: <code>{{ $summary['session_driver'] }}</code> ·
                {{ $rpoRto['rpo_label'] }} · {{ $rpoRto['rto_label'] }}
            </span>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 12px 0 0;">
            Checklist: {{ $summary['ok'] }}/{{ $summary['total'] }} OK ·
            Backup schedulato: {{ $summary['backup_scheduled'] ? 'sì' : 'no' }} ·
            Ultimo drill: {{ $summary['last_drill'] ?? '—' }}
        </p>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Redis session" :value="$summary['redis_session'] ? 'Sì' : 'No'" />
        <x-kpi-card title="RPO (min)" :value="(string) $summary['rpo_minutes']" />
        <x-kpi-card title="RTO (min)" :value="(string) $summary['rto_minutes']" />
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Checklist HA / backup</h2>
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($checklist as $item)
                <li style="margin-bottom: 8px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @elseif ($item['optional'] ?? false)
                        <span aria-hidden="true">~</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    <span class="seg-text-muted" style="font-size: 12px;">({{ $item['group'] }})</span>
                    @if ($item['optional'] ?? false)
                        <span class="seg-text-muted" style="font-size: 12px;"> — opzionale prod</span>
                    @endif
                    @if ($item['hint'])
                        <span class="seg-text-muted" style="font-size: 13px;"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Failover rapido</h2>
        <ol style="margin: 0; padding-left: 20px;">
            @foreach ($failoverSteps as $step)
                <li style="margin-bottom: 8px;">
                    <strong>{{ $step['action'] }}</strong>
                    <span class="seg-text-muted"> — {{ $step['detail'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;" id="ha-failover-drill">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Failover drill (esercitazione)</h2>
            <span class="{{ $drillSummary['ready'] ? 'seg-badge seg-badge-success' : 'seg-badge seg-badge-warning' }}">
                Drill: {{ $drillSummary['ready'] ? 'Pronto' : 'Incompleto' }}
            </span>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
            CLI: <code>php artisan ha:failover-drill --dry-run</code>
            · Probe: <code>--probe</code>
            · Runbook: <code>docs/HA-FAILOVER-DRILL-RUNBOOK.md</code>
            · Checklist: {{ $drillSummary['ok'] }}/{{ $drillSummary['total'] }} OK
            · Ultimo drill: {{ $drillSummary['last_drill'] ?? '—' }}
        </p>
        @if ($drillSummary['primary_url'] || $drillSummary['secondary_url'])
            <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
                Nodi:
                @if ($drillSummary['primary_url'])<code>{{ $drillSummary['primary_url'] }}</code>@endif
                @if ($drillSummary['secondary_url']) → <code>{{ $drillSummary['secondary_url'] }}</code>@endif
            </p>
        @endif
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0 0 16px;">
            @foreach ($drillChecklist as $item)
                <li style="margin-bottom: 6px; font-size: 13px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    @if ($item['optional'] ?? false)
                        <span class="seg-text-muted">(opz.)</span>
                    @endif
                    @if (! $item['ok'] && $item['hint'])
                        <span class="seg-text-muted"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <details style="font-size: 13px; margin-bottom: 8px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Fase 1 — Health</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($drillHealthSteps as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
        <details style="font-size: 13px; margin-bottom: 8px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Fase 2 — Switch traffic</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($drillTrafficSteps as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
        <details style="font-size: 13px; margin-bottom: 8px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Fase 3 — Recovery post-drill</summary>
            <ul style="margin: 8px 0 0; padding-left: 20px; list-style: none;">
                @foreach ($drillRecovery as $item)
                    <li style="margin-bottom: 4px;">
                        @if ($item['ok'])✅@else⬜@endif {{ $item['label'] }}
                    </li>
                @endforeach
            </ul>
        </details>
        <details style="font-size: 13px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Rollback post-drill</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($drillRollback as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="mag-section-title" style="margin-top: 0;">Documentazione</h2>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($documents as $path)
                <li><code>{{ $path }}</code></li>
            @endforeach
        </ul>
        <p style="margin: 12px 0 0;">
            <a href="{{ route('admin.waf-status') }}" class="seg-btn seg-btn-ghost">WAF status</a>
            <a href="{{ route('admin.pen-test-prep') }}" class="seg-btn seg-btn-ghost">Pen-test prep</a>
        </p>
    </div>
</div>
