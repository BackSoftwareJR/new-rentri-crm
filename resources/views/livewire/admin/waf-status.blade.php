<div id="waf-status">
    <div class="seg-page-header">
        <h1>WAF — stato deploy</h1>
        <p>
            Modalità edge da <code>WAF_MODE</code> (off · monitor · block).
            Rollout: <a href="{{ route('admin.pen-test-prep') }}">Pen-test prep</a> ·
            <code>docs/WAF-STAGING-ROLLOUT.md</code>
        </p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span class="{{ $modeBadge }}" id="waf-mode-badge">
                WAF: {{ $summary['mode_label'] }}
            </span>
            <span class="seg-text-muted" style="font-size: 13px;">
                Provider: {{ $summary['provider'] }} ·
                Path protetti: {{ $summary['paths_count'] }} ·
                Finestra monitor: {{ $summary['monitor_hours'] }}h
            </span>
        </div>
        @if ($summary['mode'] === 'off')
            <p class="seg-text-muted" style="font-size: 13px; margin: 12px 0 0;">
                Locale/dev: WAF non attivo. Impostare <code>WAF_MODE=monitor</code> su staging per avviare il rollout.
            </p>
        @endif
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Monitor ready" :value="$summary['ready_monitor'] ? 'Sì' : 'No'" />
        <x-kpi-card title="Block ready" :value="$summary['ready_block'] ? 'Sì' : 'No'" />
        <x-kpi-card title="Prod block ready" :value="$summary['ready_production_block'] ? 'Sì' : 'No'" />
        <x-kpi-card title="Path da tuning" :value="(string) ($summary['paths_needing_tune'] ?? 0)" />
        <x-kpi-card title="P0/P1 su path WAF" :value="(string) ($summary['open_p0_p1_on_waf_paths'] ?? 0)" />
        <x-kpi-card title="WAF attivo" :value="$summary['active'] ? 'Sì' : 'No'" />
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Toggle modalità WAF (env)</h2>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
            Il CRM non attiva regole edge — impostare <code>WAF_MODE</code> in env e redeploy infra.
            Runbook: <code>docs/WAF-STAGING-ROLLOUT.md</code>.
        </p>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Modalità</th>
                        <th>Env</th>
                        <th>Descrizione</th>
                        <th>Stato attuale</th>
                        <th>Prossimo passo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modeGuide as $guide)
                        <tr @if ($guide['mode'] === $currentMode) style="background: var(--seg-bg-muted, #f9fafb);" @endif>
                            <td><strong>{{ $guide['label'] }}</strong></td>
                            <td><code>{{ $guide['env'] }}</code></td>
                            <td class="seg-text-muted" style="font-size: 13px;">{{ $guide['description'] }}</td>
                            <td>
                                @if ($guide['mode'] === $currentMode)
                                    <span class="seg-badge seg-badge-success">Attivo</span>
                                @else
                                    <span class="seg-text-muted">—</span>
                                @endif
                            </td>
                            <td class="seg-text-muted" style="font-size: 13px;">{{ $guide['next_step'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Runbook tuning block post-deploy (Sprint 114)</h2>
        <ol style="margin: 0; padding-left: 20px;">
            @foreach ($tuningSteps as $step)
                <li style="margin-bottom: 10px;">
                    <strong>{{ $step['title'] }}</strong>
                    <span class="seg-text-muted" style="display: block; font-size: 13px;">{{ $step['action'] }}</span>
                </li>
            @endforeach
        </ol>
        <p style="margin: 12px 0 0;">
            <a href="{{ route('admin.pen-test-prep') }}" class="seg-btn seg-btn-ghost">Pen-test findings correlati →</a>
        </p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Checklist block mode produzione</h2>
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($productionBlock as $item)
                <li style="margin-bottom: 8px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    @if ($item['hint'])
                        <span class="seg-text-muted" style="font-size: 13px;"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Checklist deploy</h2>
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($checklist as $item)
                <li style="margin-bottom: 8px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    <span class="seg-text-muted" style="font-size: 12px;">({{ $item['applies_in'] }})</span>
                    @if ($item['hint'])
                        <span class="seg-text-muted" style="font-size: 13px;"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Path protetti × findings P0/P1 (cross-ref)</h2>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
            Findings aperti da pen-test vendor mappati su regole WAF via <code>asset_key</code>.
            <a href="{{ route('admin.pen-test-prep') }}">Gestione findings</a>
        </p>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Superficie</th>
                        <th>Path</th>
                        <th>P0</th>
                        <th>P1</th>
                        <th>Tuning</th>
                        <th>Findings correlati</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pathsWithFindings as $path)
                        <tr>
                            <td>{{ $path['label'] }}</td>
                            <td><code>{{ $path['path'] }}</code></td>
                            <td>{{ $path['open_p0'] ?? 0 }}</td>
                            <td>{{ $path['open_p1'] ?? 0 }}</td>
                            <td>
                                @if ($path['needs_tune'] ?? false)
                                    <span class="seg-badge seg-badge-warning">Sì</span>
                                @else
                                    <span class="seg-text-muted">—</span>
                                @endif
                            </td>
                            <td class="seg-text-muted" style="font-size: 13px;">
                                @if (($path['findings'] ?? []) === [])
                                    —
                                @else
                                    @foreach ($path['findings'] as $finding)
                                        <span class="seg-badge seg-badge-danger">{{ $finding['severity'] }}</span>
                                        {{ $finding['id'] }}: {{ $finding['title'] }}
                                        @if (! $loop->last)<br>@endif
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Path protetti (post-ciclo 9)</h2>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Superficie</th>
                        <th>Path</th>
                        <th>Rischio</th>
                        <th>Regola</th>
                        <th>Monitor</th>
                        <th>Block</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paths as $path)
                        <tr>
                            <td>{{ $path['label'] }}</td>
                            <td><code>{{ $path['path'] }}</code></td>
                            <td>{{ $path['risk'] }}</td>
                            <td class="seg-text-muted" style="font-size: 13px;">{{ $path['rule'] }}</td>
                            <td>{{ $path['monitor'] ? '✓' : '—' }}</td>
                            <td>{{ $path['block'] ? '✓' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 12px 0 0;">
            Webhook Stripe: monitor sì, block no (esclusione firma HMAC).
        </p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">SIEM / logging</h2>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($siem as $item)
                <li style="margin-bottom: 6px;">
                    @if ($item['ok'])
                        ✅
                    @else
                        ⬜
                    @endif
                    {{ $item['label'] }}
                    @if ($item['hint'])
                        <span class="seg-text-muted"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="mag-section-title" style="margin-top: 0;">Documentazione</h2>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($documents as $path)
                <li><code>{{ $path }}</code></li>
            @endforeach
        </ul>
        <p style="margin: 12px 0 0;">
            <a href="{{ route('admin.pen-test-prep') }}" class="seg-btn seg-btn-ghost">← Pen-test OWASP prep</a>
        </p>
        <p style="margin: 12px 0 0;">
            <a href="{{ route('admin.ha-status') }}" class="seg-btn seg-btn-ghost">HA backup status →</a>
        </p>
    </div>
</div>
