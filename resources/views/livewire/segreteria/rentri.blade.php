<div>
    @include('livewire.partials.flash-messages')

    <x-page-header title="RENTRI — Trasmissione registro" lead="Invio periodico movimenti al registro cronologico nazionale RENTRI.">
        <x-slot name="actions">
            <x-rentri-api-mode-badge />
            <x-contextual-help title="Trasmissione RENTRI">
                Seleziona il periodo, verifica la checklist conformità e avvia la trasmissione al registro MASE.
            </x-contextual-help>
            <x-btn variant="secondary" :href="route('segreteria.registro-movimenti')" wire:navigate>Registro movimenti</x-btn>
            <x-btn variant="secondary" :href="route('segreteria.rentri.transazioni')" wire:navigate>Storico API</x-btn>
        </x-slot>
    </x-page-header>

    @if ($showRentriProdStubBanner ?? false)
        <x-rentri-prod-stub-banner />
    @endif

    <div class="seg-card seg-card-padding" style="margin-bottom: 1.5rem;" id="rentri-production-switch-status">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
            <div>
                <h2 class="mag-section-title" style="margin: 0;">Switch produzione MASE</h2>
                <p class="mag-section-lead" style="margin: 0.35rem 0 0;">
                    Checklist unificata env + UI + preflight — RENTRI_ENV={{ $productionSwitchSummary['rentri_env'] }}.
                </p>
            </div>
            @if ($productionActive ?? false)
                <span class="seg-badge seg-badge-success">Produzione MASE attiva</span>
            @elseif ($productionSwitchReady ?? false)
                <span class="seg-badge seg-badge-warning">Pronto al switch — confermare env</span>
            @else
                <span class="seg-badge seg-badge-muted">
                    {{ $productionSwitchSummary['ok'] ?? 0 }}/{{ $productionSwitchSummary['total'] ?? 0 }} voci OK
                </span>
            @endif
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0.75rem 0 0;">
            Runbook:
            <code>docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md</code> ·
            CLI: <code>php artisan rentri:production-switch-check</code> ·
            <a href="{{ route('segreteria.impostazioni.rentri', ['step' => 4]) }}" wire:navigate>Wizard step 4</a>
        </p>
    </div>

    @php
        $slaSelected = $slaDashboard['selected'];
        $slaThresholds = $slaDashboard['thresholds'];
    @endphp

    <div class="seg-card seg-card-padding" style="margin-bottom: 1.5rem;" data-tour="rentri-sla">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div>
                <h2 class="mag-section-title" style="margin: 0;">SLA transazioni API</h2>
                <p class="mag-section-lead" style="margin: 0.35rem 0 0;">
                    Latency, retry e dead-letter su fir / xFIR / registro — periodo selezionato.
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <x-badge-stato
                    :stato="match ($slaSelected['sla']['overall']) { 'ok' => 'success', 'warn' => 'warning', default => 'danger' }"
                    :label="$slaMetrics->slaBadgeLabel($slaSelected['sla']['overall'])"
                />
                <select wire:model.live="slaPeriodDays" class="seg-select" style="width: auto; min-width: 140px;" aria-label="Periodo SLA">
                    <option value="7">Ultimi 7 giorni</option>
                    <option value="30">Ultimi 30 giorni</option>
                </select>
                <x-btn variant="secondary" :href="route('segreteria.rentri.transazioni')" wire:navigate>Dettaglio transazioni</x-btn>
            </div>
        </div>

        @if (($slaSelected['totale'] ?? 0) === 0)
            <x-empty-state
                title="Nessuna transazione nel periodo"
                description="Le metriche SLA compariranno dopo le chiamate API RENTRI (vidima, xFIR, registro)."
            />
        @else
            <div class="seg-kpi-grid" style="margin-bottom: 1rem;">
                <x-kpi-card
                    title="p95 latency"
                    :value="$slaSelected['latency']['p95_seconds'] !== null ? $slaSelected['latency']['p95_seconds'].' s' : '—'"
                    :subtitle="'soglia '.$slaThresholds['p95_latency_seconds'].' s'"
                />
                <x-kpi-card
                    title="Retry medi"
                    :value="(string) $slaSelected['retry']['avg_count']"
                    :subtitle="'soglia '.$slaThresholds['max_avg_retry_count']"
                />
                <x-kpi-card
                    title="Dead-letter"
                    :value="(string) $slaSelected['dead_letter']['count']"
                    :subtitle="number_format($slaSelected['dead_letter']['rate_percent'], 1, ',', '.').'% del totale'"
                    valueColor="#dc2626"
                />
                <x-kpi-card title="Transazioni" :value="(string) $slaSelected['totale']" :subtitle="$slaSelected['completate'].' completate'" />
            </div>

            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Tipo API</th>
                            <th>Totale</th>
                            <th>p95 (s)</th>
                            <th>Retry medio</th>
                            <th>Dead-letter %</th>
                            <th>SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slaSelected['by_tipo'] as $tipo => $row)
                            <tr wire:key="sla-{{ $tipo }}-{{ $slaPeriodDays }}">
                                <td class="seg-cell-strong">{{ $row['label'] }}</td>
                                <td>{{ $row['totale'] }}</td>
                                <td>{{ $row['latency']['p95_seconds'] ?? '—' }}</td>
                                <td>{{ $row['retry']['avg_count'] }}</td>
                                <td>{{ number_format($row['dead_letter']['rate_percent'], 1, ',', '.') }}%</td>
                                <td>
                                    <x-badge-stato
                                        :stato="match ($row['sla']['overall']) { 'ok' => 'success', 'warn' => 'warning', default => 'danger' }"
                                        :label="$slaMetrics->slaBadgeLabel($row['sla']['overall'])"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="seg-list-muted" style="margin: 0.75rem 0 0; font-size: 0.875rem;">
                Confronto 7 gg: p95 {{ $slaDashboard['periods'][7]['latency']['p95_seconds'] ?? '—' }} s,
                dead-letter {{ number_format($slaDashboard['periods'][7]['dead_letter']['rate_percent'], 1, ',', '.') }}% —
                30 gg: p95 {{ $slaDashboard['periods'][30]['latency']['p95_seconds'] ?? '—' }} s,
                dead-letter {{ number_format($slaDashboard['periods'][30]['dead_letter']['rate_percent'], 1, ',', '.') }}%.
            </p>

            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--seg-border, #e5e7eb);">
                <h3 class="mag-section-title" style="font-size: 0.9375rem; margin: 0 0 0.5rem;">Automazione SLA (cron)</h3>
                <p class="seg-text-muted" style="font-size: 0.875rem; margin: 0 0 0.75rem;">
                    Ultimo check:
                    @if ($slaLastCheck)
                        <strong>{{ \Illuminate\Support\Carbon::parse($slaLastCheck['checked_at'])->format('d/m/Y H:i') }}</strong>
                        — esito {{ strtoupper($slaLastCheck['overall'] ?? '—') }}
                    @else
                        <span>— (eseguire <code>php artisan rentri:sla-check</code>)</span>
                    @endif
                </p>

                @if ($slaRecentBreaches !== [])
                    <p class="seg-text-muted" style="font-size: 0.8125rem; margin: 0 0 0.35rem;">Ultimi breach (activity log)</p>
                    <ul class="seg-list" style="list-style: none; padding: 0; margin: 0; font-size: 0.875rem;">
                        @foreach ($slaRecentBreaches as $breach)
                            <li style="padding: 0.35rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                                <span class="seg-badge seg-badge-danger">BREACH</span>
                                {{ $breach['description'] }}
                                <span class="seg-text-muted">· {{ $breach['created_at'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="seg-text-muted" style="font-size: 0.875rem; margin: 0;">Nessun breach SLA registrato in activity log.</p>
                @endif
            </div>
        @endif
    </div>

    <div class="seg-card seg-card-padding" data-tour="rentri-trasmissione">
        <h2 class="mag-section-title">Nuova trasmissione</h2>
        <p class="mag-section-lead">Seleziona il periodo e verifica l'anteprima dei movimenti non ancora trasmessi.</p>

        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label" for="rentri-da">Periodo da *</label>
                <input id="rentri-da" type="date" wire:model.live="periodo_da" class="seg-input @error('periodo_da') is-invalid @enderror" />
                @error('periodo_da') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
            <div class="seg-form-group">
                <label class="seg-label" for="rentri-a">Periodo a *</label>
                <input id="rentri-a" type="date" wire:model.live="periodo_a" class="seg-input @error('periodo_a') is-invalid @enderror" />
                @error('periodo_a') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($payload)
            <div class="seg-kpi-grid" style="margin-top: 1rem;">
                <x-kpi-card title="Movimenti da trasmettere" :value="(string) $payload->metadata['count']" />
                <x-kpi-card title="Hash payload" :value="Str::limit($payload->payloadHash, 16, '…')" subtitle="SHA-256" />
            </div>

            @if ($payload->metadata['count'] > 0)
                <div class="seg-card seg-card-padding" style="margin-top: 1rem;">
                    <h3 class="mag-section-title" style="font-size: 1rem;">Checklist conformità ministeriale</h3>
                    <ul class="seg-checklist" style="margin: 0.75rem 0 0; padding: 0; list-style: none;">
                        @foreach ($checklist as $item)
                            <li wire:key="chk-{{ $item['codice'] }}-{{ $loop->index }}" style="display: flex; gap: 0.5rem; align-items: flex-start; margin-bottom: 0.35rem;">
                                <x-badge-stato :stato="$item['ok'] ? 'success' : 'danger'" :label="$item['ok'] ? 'OK' : 'KO'" />
                                <span>
                                    {{ $item['label'] }}
                                    @if (! $item['ok'] && $item['message'])
                                        <span class="seg-field-error"> — {{ $item['message'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @unless ($conforme)
                        <p class="seg-field-error" style="margin-top: 0.75rem;">Correggi gli elementi segnati KO prima di trasmettere.</p>
                    @endunless
                </div>

                <div class="seg-card seg-card-padding-none" style="margin-top: 1rem;">
                    <div class="seg-table-wrap">
                        <table class="seg-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>CER</th>
                                    <th>Tipo</th>
                                    <th>Peso (kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($payload->movimenti, 0, 15) as $mov)
                                    <tr wire:key="prev-{{ $mov['id'] ?? $loop->index }}">
                                        <td>{{ \Illuminate\Support\Carbon::parse($mov['data'])->format('d/m/Y H:i') }}</td>
                                        <td class="seg-cell-strong">{{ $mov['codice_cer'] }}</td>
                                        <td>
                                            <x-badge-stato :stato="$mov['tipo'] === 'carico' ? 'success' : 'info'" :label="ucfirst($mov['tipo'])" />
                                        </td>
                                        <td>{{ number_format($mov['peso_kg'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($payload->metadata['count'] > 15)
                        <p class="mag-section-lead" style="padding: 0.75rem 1rem;">… e altri {{ $payload->metadata['count'] - 15 }} movimenti.</p>
                    @endif
                </div>

                <div style="margin-top: 1rem;">
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="trasmetti" wire:loading.attr="disabled" wire:confirm="Confermi la trasmissione al registro RENTRI? I movimenti verranno bloccati." @disabled(! $conforme)>
                        <span wire:loading.remove wire:target="trasmetti">Trasmetti a RENTRI</span>
                        <span wire:loading wire:target="trasmetti">Trasmissione in corso…</span>
                    </button>
                </div>
            @else
                <p class="mag-empty" style="margin-top: 1rem;">Nessun movimento non trasmesso nel periodo selezionato.</p>
            @endif
        @endif
    </div>

    <div class="seg-card seg-card-padding-none" style="margin-top: 1.5rem;">
        <div class="seg-page-header seg-page-header--compact" style="padding: 1rem 1.25rem;">
            <h2 class="mag-section-title" style="margin: 0;">Storico trasmissioni</h2>
        </div>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Esito</th>
                        <th>Trasmesso il</th>
                        <th>Protocollo</th>
                        <th>Movimenti</th>
                        <th>Audit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($storico as $tx)
                        <tr wire:key="tx-{{ $tx->id }}">
                            <td>{{ $tx->periodo_da->format('d/m/Y') }} — {{ $tx->periodo_a->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $esitoVariant = match (strtolower($tx->esito)) {
                                        'accettato', 'ok', 'completata', 'completato' => 'success',
                                        'in_attesa' => 'warning',
                                        default => 'danger',
                                    };
                                @endphp
                                <x-badge-stato :stato="$esitoVariant" :label="ucfirst(str_replace('_', ' ', $tx->esito))" />
                            </td>
                            <td>{{ $tx->trasmesso_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $tx->response_json['protocollo'] ?? '—' }}</td>
                            <td>{{ $tx->movimenti_count }}</td>
                            <td>
                                @if (in_array(strtolower($tx->esito), ['accettato', 'ok', 'completata', 'completato']))
                                    <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="exportAuditJson({{ $tx->id }})">JSON</button>
                                    <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="exportAuditCsv({{ $tx->id }})">CSV</button>
                                @else
                                    <span class="seg-list-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="seg-table-empty">Nessuna trasmissione registrata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
