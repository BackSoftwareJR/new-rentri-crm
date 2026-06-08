<div>
    <div data-tour="welcome">
        <x-page-header title="Dashboard" lead="Panoramica operativa: priorità VFU e RENTRI, poi magazzino e amministrazione.">
            <x-slot name="actions">
                @if ($kpiCache['enabled'] ?? false)
                    <x-badge-stato
                        :stato="($kpiCache['hit'] ?? false) ? 'success' : 'info'"
                        :label="'KPI cache: ' . (($kpiCache['hit'] ?? false) ? 'hit' : 'miss') . ' (' . ($kpiCache['driver'] ?? 'array') . ')'"
                    />
                    <button type="button" wire:click="refreshKpi" class="seg-btn seg-btn-secondary seg-btn-sm">
                        Refresh KPI
                    </button>
                @endif
                <x-contextual-help title="Dashboard segreteria">
                    KPI VFU, magazzino e RENTRI in un colpo d'occhio. Usa le azioni rapide per le operazioni più frequenti.
                </x-contextual-help>
            </x-slot>
        </x-page-header>
    </div>

    @if ($showRentriProdStubBanner ?? false)
        <x-rentri-prod-stub-banner />
    @endif

    <section class="seg-card seg-card-padding seg-mb-lg" aria-label="Analytics periodo">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div>
                <h2 class="mag-section-title" style="margin: 0;">Report &amp; analytics</h2>
                <p class="seg-text-muted" style="margin: 4px 0 0;">{{ $analytics['label'] }} · vs {{ $analytics['previous_label'] }}</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <label for="periodo-analytics" class="seg-text-muted" style="font-size: 0.875rem;">Periodo</label>
                <select id="periodo-analytics" wire:model.live="periodo" class="input" style="min-width: 180px;">
                    @foreach ($periodOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="exportKpiCsv" class="seg-btn seg-btn-secondary">
                    Export KPI mensile (CSV)
                </button>
            </div>
        </div>

        <div class="seg-kpi-grid">
            @php
                $deltaCards = [
                    ['key' => 'vfu_nuove', 'title' => 'VFU nuove pratiche', 'value' => $analytics['current']['vfu']['nuove_pratiche']],
                    ['key' => 'magazzino_movimenti', 'title' => 'Movimenti magazzino', 'value' => $analytics['current']['magazzino']['movimenti']],
                    ['key' => 'rentri_transazioni', 'title' => 'Transazioni RENTRI', 'value' => $analytics['current']['rentri']['totale']],
                    ['key' => 'mud_inviate', 'title' => 'MUD inviate', 'value' => $analytics['current']['mud']['inviate']],
                ];
            @endphp
            @foreach ($deltaCards as $card)
                @php $delta = $analytics['delta'][$card['key']]; @endphp
                <div class="seg-card seg-card-padding" style="margin: 0;">
                    <p class="seg-text-muted" style="margin: 0 0 4px; font-size: 0.8125rem;">{{ $card['title'] }}</p>
                    <p style="margin: 0; font-size: 1.5rem; font-weight: 600;">{{ $card['value'] }}</p>
                    <p class="seg-text-muted" style="margin: 6px 0 0; font-size: 0.8125rem;">
                        @if ($delta['direction'] === 'up')
                            <span style="color: #16a34a;">▲ +{{ $delta['diff'] }} ({{ $delta['pct'] }}%)</span>
                        @elseif ($delta['direction'] === 'down')
                            <span style="color: #dc2626;">▼ {{ $delta['diff'] }} ({{ $delta['pct'] }}%)</span>
                        @else
                            <span>— invariato</span>
                        @endif
                        vs periodo precedente
                    </p>
                </div>
            @endforeach
        </div>

        <h3 class="mag-section-title seg-mt-md">Trend mensile (ultimi 6 mesi)</h3>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Mese</th>
                        <th>VFU nuove</th>
                        <th>Movimenti</th>
                        <th>RENTRI API</th>
                        <th>MUD inviate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyTrend as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['vfu_nuove'] }}</td>
                            <td>{{ $row['magazzino_movimenti'] }}</td>
                            <td>{{ $row['rentri_transazioni'] }}</td>
                            <td>{{ $row['mud_inviate'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="seg-card seg-card-padding seg-mb-lg" aria-label="KPI business v3">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div>
                <h2 class="mag-section-title" style="margin: 0;">KPI business v3</h2>
                <p class="seg-text-muted" style="margin: 4px 0 0;">{{ $businessKpi['label'] }} · vs {{ $businessKpi['previous_label'] }}</p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <label for="periodo-business-kpi" class="seg-text-muted" style="font-size: 0.875rem;">Finestra</label>
                <select id="periodo-business-kpi" wire:model.live="businessPeriodo" class="input" style="min-width: 180px;">
                    @foreach ($businessPeriodOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="exportBusinessKpiCsv" class="seg-btn seg-btn-secondary seg-btn-sm">
                    Export CSV
                </button>
            </div>
        </div>

        <div class="seg-text-muted" style="font-size: 13px; margin-bottom: 12px;">
            Alert KPI:
            @if ($businessKpiAlert)
                ultimo check <strong>{{ \Illuminate\Support\Carbon::parse($businessKpiAlert['checked_at'])->format('d/m/Y H:i') }}</strong>
                — esito <strong>{{ strtoupper($businessKpiAlert['overall'] ?? '—') }}</strong>
            @else
                — (eseguire <code>php artisan kpi:business-check</code>)
            @endif
            · CLI: <code>kpi:business-check --notify</code>
        </div>

        @if ($businessKpiBreaches !== [])
            <ul style="margin: 0 0 12px; padding-left: 20px; font-size: 13px;">
                @foreach ($businessKpiBreaches as $breach)
                    <li>{{ $breach['description'] }} <span class="seg-text-muted">· {{ $breach['created_at'] }}</span></li>
                @endforeach
            </ul>
        @endif

        <div class="seg-kpi-grid">
            @php
                $businessCards = [
                    [
                        'key' => 'ordini_confermati',
                        'title' => 'Ordini e-commerce confermati',
                        'value' => (string) $businessKpi['current']['ecommerce']['ordini_confermati'],
                        'href' => route('segreteria.ecommerce'),
                    ],
                    [
                        'key' => 'vfu_accettate',
                        'title' => 'VFU accettate',
                        'value' => (string) $businessKpi['current']['vfu']['accettate'],
                        'href' => route('segreteria.vfu.index'),
                    ],
                    [
                        'key' => 'magazzino_kg',
                        'title' => 'Movimenti magazzino (kg)',
                        'value' => number_format($businessKpi['current']['magazzino']['movimenti_kg'], 2, ',', '.') . ' kg',
                        'href' => route('segreteria.registro-movimenti'),
                    ],
                    [
                        'key' => 'revenue_eur',
                        'title' => 'Revenue fatture pagate',
                        'value' => '€ ' . number_format($businessKpi['current']['ecommerce']['revenue_eur'], 2, ',', '.'),
                        'href' => route('segreteria.fatture.index'),
                    ],
                ];
                $thresholdColors = ['alert' => '#dc2626', 'warn' => '#ca8a04', 'ok' => null];
            @endphp
            @foreach ($businessCards as $card)
                @php
                    $delta = $businessKpi['delta'][$card['key']];
                    $threshold = $businessKpi['thresholds'][$card['key']];
                    $valueColor = $thresholdColors[$threshold] ?? null;
                    $deltaLabel = match ($delta['direction']) {
                        'up' => '▲ +'.$delta['diff'].' ('.$delta['pct'].'%)',
                        'down' => '▼ '.$delta['diff'].' ('.$delta['pct'].'%)',
                        default => '— invariato',
                    };
                @endphp
                <x-kpi-card
                    :title="$card['title']"
                    :value="$card['value']"
                    :href="$card['href']"
                    :valueColor="$valueColor"
                    :subtitle="$deltaLabel . ' vs periodo precedente'"
                />
            @endforeach
        </div>
    </section>

    <div class="seg-quick-actions" data-tour="quick-actions">
        <span class="seg-quick-actions-label">Azioni rapide</span>
        <x-btn variant="primary" :href="route('segreteria.rentri')" wire:navigate data-tour="rentri-shortcut">Trasmetti registro</x-btn>
        <x-btn variant="secondary" :href="route('segreteria.vfu.index')" wire:navigate>VFU attivi</x-btn>
        <x-btn variant="secondary" :href="route('segreteria.magazzino')" wire:navigate>Magazzino</x-btn>
        <x-btn variant="secondary" :href="route('segreteria.trasporti')" wire:navigate>Trasporti</x-btn>
        <x-btn variant="ghost" :href="route('segreteria.impostazioni.rentri')" wire:navigate>Impostazioni RENTRI</x-btn>
    </div>

    <p class="seg-dashboard-widgets-hint">Trascina le sezioni per riordinare — l'ordine viene salvato nel browser.</p>

    <div id="seg-dashboard-widgets" class="seg-dashboard-widgets" data-tour="dashboard-widgets">
        @php
            $rentriStatus = $kpi['rentri_status'] ?? [];
            $rentriBadgeColor = ($rentriStatus['ambiente'] ?? 'sandbox') === 'live' ? '#166534' : '#92400e';
            $rentriBadgeBg = ($rentriStatus['ambiente'] ?? 'sandbox') === 'live' ? '#dcfce7' : '#fef9c3';
            $certDays = $rentriStatus['cert_days'] ?? null;
            $certSubtitle = match (true) {
                $certDays === null => 'Cert mTLS — scadenza N/D',
                $certDays < 0 => 'Cert mTLS scaduto',
                $certDays < 30 => "Cert mTLS scade tra {$certDays} gg",
                default => "Cert mTLS valido ({$certDays} gg)",
            };
        @endphp

        <section class="seg-dashboard-widget" data-widget-id="operativa-oggi" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px;">
                <h2 class="mag-section-title" style="margin: 0;">Panoramica operativa</h2>
                <span class="seg-badge" style="font-size: 11px; background: {{ $rentriBadgeBg }}; color: {{ $rentriBadgeColor }};">
                    RENTRI {{ strtoupper($rentriStatus['ambiente'] ?? 'sandbox') }}
                </span>
                @if (($rentriStatus['health_ok'] ?? false) === false)
                    <x-badge-stato stato="warning" label="Health check KO" />
                @endif
            </div>
            <div class="seg-kpi-grid">
                <x-kpi-card title="VFU oggi" :value="(string) ($kpi['vfu_oggi'] ?? 0)" subtitle="Accettati nelle ultime 24h" :href="route('segreteria.vfu.index')" />
                <x-kpi-card title="VFU in bonifica" :value="(string) ($kpi['vfu_in_bonifica'] ?? 0)" valueColor="{{ ($kpi['vfu_in_bonifica'] ?? 0) > 0 ? '#ca8a04' : null }}" subtitle="Stato in bonifica" :href="route('segreteria.vfu.index')" />
                <x-kpi-card title="VFU in smontaggio" :value="(string) ($kpi['vfu_in_smontaggio'] ?? 0)" subtitle="Stato in smontaggio" :href="route('segreteria.vfu.index')" />
                <x-kpi-card title="Trasporti in transito" :value="(string) ($kpi['trasporti_in_transito'] ?? 0)" subtitle="In viaggio verso destinazione" :href="route('segreteria.trasporti')" />
                <x-kpi-card title="Fatture in scadenza" :value="(string) ($kpi['fatture_in_scadenza'] ?? 0)" valueColor="{{ ($kpi['fatture_in_scadenza'] ?? 0) > 0 ? '#ca8a04' : null }}" subtitle="Entro 7 giorni" :href="route('segreteria.fatture.index')" />
                <x-kpi-card title="Serbatoi in alert" :value="(string) ($kpi['magazzino_alert'] ?? 0)" valueColor="{{ ($kpi['magazzino_alert'] ?? 0) > 0 ? '#dc2626' : null }}" subtitle="Giacenza sotto soglia" :href="route('segreteria.magazzino')" />
                <x-kpi-card title="Movimenti da trasmettere" :value="(string) ($kpi['rentri_pending'] ?? 0)" valueColor="{{ ($kpi['rentri_pending'] ?? 0) > 0 ? '#ca8a04' : null }}" subtitle="Registro non trasmesso (non locked)" :href="route('segreteria.rentri')" />
                <x-kpi-card title="Revenue mese corrente" :value="'€ ' . number_format($kpi['revenue_mese_corrente'] ?? 0, 2, ',', '.')" subtitle="Fatture pagate · {{ $certSubtitle }}" :href="route('segreteria.fatture.index')" />
            </div>
        </section>

        @if ($kpi['rentri_pending'] > 0 || $kpi['rentri_dead_letter'] > 0)
            <section class="seg-dashboard-widget" data-widget-id="priority-rentri" draggable="true">
                <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
                <div class="seg-dashboard-priority" aria-label="Priorità RENTRI">
                    <h2 class="mag-section-title">Priorità RENTRI</h2>
                    <div class="seg-kpi-grid">
                        @if ($kpi['rentri_pending'] > 0)
                            <x-kpi-card title="Da trasmettere" :value="(string) $kpi['rentri_pending']" valueColor="#ca8a04" subtitle="Movimenti in attesa MASE" :href="route('segreteria.rentri')" />
                        @endif
                        @if ($kpi['rentri_dead_letter'] > 0)
                            <x-kpi-card title="Dead-letter" :value="(string) $kpi['rentri_dead_letter']" valueColor="#dc2626" subtitle="Intervento manuale richiesto" :href="route('segreteria.rentri.transazioni')" />
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($authAlerts['in_scadenza'] > 0 || $authAlerts['scadute'] > 0)
            <section class="seg-dashboard-widget" data-widget-id="auth-alerts" draggable="true">
                <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
                <div aria-label="Alert autorizzazioni trasporto">
                    <h2 class="mag-section-title">Autorizzazioni trasporto</h2>
                    <div class="seg-kpi-grid">
                        @if ($authAlerts['scadute'] > 0)
                            <x-kpi-card title="Scadute" :value="(string) $authAlerts['scadute']" valueColor="#dc2626" subtitle="Trasportatori/impianti non conformi" :href="route('segreteria.anagrafiche')" />
                        @endif
                        @if ($authAlerts['in_scadenza'] > 0)
                            <x-kpi-card title="In scadenza (15 gg)" :value="(string) $authAlerts['in_scadenza']" valueColor="#ca8a04" subtitle="Rinnovo autorizzazione consigliato" :href="route('segreteria.anagrafiche')" />
                        @endif
                    </div>
                    @if ($authAlertItems->isNotEmpty())
                        <ul class="seg-alert-list seg-mt-md">
                            @foreach ($authAlertItems as $item)
                                <li>
                                    <a href="{{ route('segreteria.anagrafiche.show', $item['anagrafica_id']) }}" class="seg-link" wire:navigate>
                                        {{ $item['ragione_sociale'] }}
                                    </a>
                                    — {{ $item['numero'] }}
                                    @if ($item['stato'] === 'scaduta')
                                        <x-badge-stato stato="danger" label="Scaduta" />
                                    @else
                                        <x-badge-stato stato="warning" label="Scade tra {{ $item['giorni'] }} gg" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif

        @if (! empty($demoWalkthrough))
            <section class="seg-dashboard-widget" data-widget-id="demo-walkthrough" draggable="true">
                <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
                <x-demo-rentri-walkthrough :steps="$demoWalkthrough['steps']" :seeded="$demoWalkthrough['seeded']" :progress="$demoWalkthrough['progress']" />
            </section>
        @endif

        <section class="seg-dashboard-widget" data-widget-id="vfu-bonifica" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">VFU &amp; Bonifica</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="VFU attivi" :value="(string) $kpi['vfu_attivi']" subtitle="Pratiche in corso" :href="route('segreteria.vfu.index')" />
                <x-kpi-card title="Bonifiche in attesa" :value="(string) $kpi['bonifiche_pending']" valueColor="{{ $kpi['bonifiche_pending'] > 0 ? '#ca8a04' : null }}" subtitle="Attesa o in bonifica" :href="route('segreteria.vfu.index')" />
            </div>
        </section>

        <section class="seg-dashboard-widget" data-widget-id="magazzino-registro" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">Magazzino &amp; Registro</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="Giacenza magazzino" :value="number_format($kpi['magazzino_kg'], 2, ',', '.') . ' kg'" subtitle="{{ $kpi['magazzino_serbatoi'] }} serbatoi attivi" :href="route('segreteria.magazzino')" />
                <x-kpi-card title="Serbatoi in alert" :value="(string) $kpi['magazzino_alert']" valueColor="{{ $kpi['magazzino_alert'] > 0 ? '#dc2626' : null }}" subtitle="{{ $kpi['magazzino_alert'] > 0 ? 'Attenzione o soglia superata' : 'Tutti regolari' }}" :href="route('segreteria.magazzino')" />
                <x-kpi-card title="Movimenti (mese)" :value="(string) $kpi['movimenti_mese']" subtitle="Carichi e scarichi registrati" :href="route('segreteria.registro-movimenti')" />
            </div>
            @if ($serbatoioAlerts['totale_alert'] > 0)
                <ul class="seg-alert-list seg-mt-md">
                    @foreach ($serbatoioAlertItems as $item)
                        <li>
                            <a href="{{ route('segreteria.magazzino.show', $item['id']) }}" class="seg-link" wire:navigate>
                                {{ $item['codice'] }}
                            </a>
                            — {{ number_format($item['quantita_attuale_kg'], 0, ',', '.') }} kg
                            @if ($item['stato'] === 'superata')
                                <x-badge-stato stato="danger" label="Soglia superata" />
                            @else
                                <x-badge-stato stato="warning" label="{{ number_format($item['percentuale'], 1, ',', '.') }}%" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="seg-dashboard-widget" data-widget-id="rentri" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">RENTRI</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="Movimenti da trasmettere" :value="(string) $kpi['rentri_pending']" valueColor="{{ $kpi['rentri_pending'] > 0 ? '#ca8a04' : null }}" subtitle="Non ancora trasmessi a RENTRI" :href="route('segreteria.rentri')" />
                <x-kpi-card title="Chiamate API (totale)" :value="(string) $kpi['rentri_transazioni']" subtitle="Storico transazioni sandbox/live" :href="route('segreteria.rentri.transazioni')" />
                <x-kpi-card title="Errori API" :value="(string) $kpi['rentri_errori']" valueColor="{{ $kpi['rentri_errori'] > 0 ? '#dc2626' : null }}" subtitle="Transazioni in errore (retry attivo)" :href="route('segreteria.rentri.transazioni')" />
                <x-kpi-card title="Dead-letter RENTRI" :value="(string) $kpi['rentri_dead_letter']" valueColor="{{ $kpi['rentri_dead_letter'] > 0 ? '#dc2626' : null }}" subtitle="{{ $kpi['rentri_dead_letter'] > 0 ? 'Intervento manuale richiesto' : 'Nessuna transazione abbandonata' }}" :href="route('segreteria.rentri.transazioni')" />
                <x-kpi-card title="Retry pianificati" :value="(string) $kpi['rentri_retry_pianificati']" valueColor="{{ $kpi['rentri_retry_pianificati'] > 0 ? '#ca8a04' : null }}" subtitle="Backoff MASE in coda" :href="route('segreteria.rentri.transazioni')" />
            </div>
        </section>

        <section class="seg-dashboard-widget" data-widget-id="fatturazione" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">Fatturazione</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card
                    title="Fatture emesse (mese)"
                    :value="(string) ($kpi['fatturazione_mese']['count'] ?? 0)"
                    :subtitle="'€ ' . number_format($kpi['fatturazione_mese']['totale'] ?? 0, 2, ',', '.') . ' totale'"
                    :href="route('segreteria.fatture.index')"
                />
                <x-kpi-card
                    title="Fatture scadute"
                    :value="(string) ($kpi['fatture_scadute'] ?? 0)"
                    valueColor="{{ ($kpi['fatture_scadute'] ?? 0) > 0 ? '#dc2626' : null }}"
                    subtitle="Da sollecitare"
                    :href="route('segreteria.fatture.index')"
                />
                <x-kpi-card
                    title="Prossima scadenza"
                    :value="$kpi['prossima_scadenza'] ? \Illuminate\Support\Carbon::parse($kpi['prossima_scadenza'])->format('d/m/Y') : '—'"
                    subtitle="Prima fattura emessa in scadenza"
                    :href="route('segreteria.fatture.index')"
                />
            </div>
        </section>

        <section class="seg-dashboard-widget" data-widget-id="mud-ecommerce" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">MUD &amp; E-commerce</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="Dichiarazioni MUD" :value="(string) $kpi['mud_totale']" subtitle="{{ $kpi['mud_bozze'] }} in bozza" :href="route('segreteria.mud')" />
                <x-kpi-card title="MUD in bozza" :value="(string) $kpi['mud_bozze']" valueColor="{{ $kpi['mud_bozze'] > 0 ? '#ca8a04' : null }}" subtitle="Da completare ed esportare" :href="route('segreteria.mud')" />
                <x-kpi-card title="Ricambi in catalogo" :value="(string) $kpi['ecommerce_prodotti']" subtitle="{{ $kpi['ecommerce_disponibili'] }} disponibili" :href="route('segreteria.ecommerce')" />
                <x-kpi-card title="Ordini bozza" :value="(string) $kpi['ecommerce_ordini_bozza']" subtitle="E-commerce stub (no pagamento)" :href="route('segreteria.ecommerce')" />
            </div>
        </section>

        <section class="seg-dashboard-widget" data-widget-id="anagrafiche-catalogo" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title">Anagrafiche &amp; Catalogo</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="Anagrafiche" :value="(string) $kpi['anagrafiche']" subtitle="Trasportatori, impianti, privati" :href="route('segreteria.anagrafiche')" />
                <x-kpi-card title="Codici CER attivi" :value="(string) $kpi['codici_cer']" subtitle="Catalogo rifiuti operativo" :href="route('segreteria.codici-cer.index')" />
            </div>
        </section>

        <section class="seg-dashboard-widget" data-widget-id="migrazione-legacy" draggable="true">
            <div class="seg-dashboard-widget-handle" aria-hidden="true">⋮⋮</div>
            <h2 class="mag-section-title" id="migrazione-legacy">Migrazione legacy</h2>
            <div class="seg-kpi-grid">
                <x-kpi-card title="Record legacy tracciati" :value="(string) $kpi['legacy_total']" subtitle="Snapshot import nel database" />
            </div>

            <div class="seg-card seg-card-padding" id="go-live-checklist" style="margin-bottom: 0;">
                <h3 class="mag-section-title" style="margin-top: 0;">Stato import per entità</h3>
                <div class="seg-table-wrap">
                    <table class="seg-table">
                        <thead>
                            <tr>
                                <th>Entità</th>
                                <th>Record</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($legacyReportRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>
                                        @if ($row['status'] === 'imported')
                                            <span class="seg-badge seg-badge-success">Importato</span>
                                        @else
                                            <span class="seg-badge seg-badge-warning">Vuoto</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="seg-legacy-report-hint">
                    <p><strong>CLI:</strong> <code>php artisan rentri:import-legacy --report</code> · <code>php artisan legacy:sync-incremental</code></p>
                    <p class="seg-text-muted">Output tabellare con stato per entità e totale record tracciati.</p>
                </div>

                @can('legacy.viewRuns')
                    <div style="margin-top: 16px;">
                        <h3 class="mag-section-title" style="margin-top: 0;">Ultimo sync incrementale</h3>
                        @if ($legacyLastSyncRun)
                            <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
                                {{ $legacyLastSyncRun->started_at?->format('d/m/Y H:i') ?? '—' }}
                                · Run <code>{{ Str::limit($legacyLastSyncRun->run_id, 8, '') }}</code>
                                · {{ $legacyLastSyncRun->dry_run ? 'dry-run' : 'live' }}
                                · {{ $legacyLastSyncRun->total_new }} nuovi,
                                {{ $legacyLastSyncRun->total_updated }} aggiornati,
                                {{ $legacyLastSyncRun->total_skipped }} skipped
                            </p>
                            @if (! empty($legacyDiffSummary))
                                <div class="seg-table-wrap">
                                    <table class="seg-table">
                                        <thead>
                                            <tr>
                                                <th>Entità</th>
                                                <th>Nuovi</th>
                                                <th>Aggiornati</th>
                                                <th>Skipped</th>
                                                <th>Errori</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($legacyDiffSummary as $entity => $diff)
                                                <tr wire:key="legacy-diff-{{ $entity }}">
                                                    <td>{{ $diff['label'] ?? $entity }}</td>
                                                    <td>{{ $diff['new'] ?? 0 }}</td>
                                                    <td>{{ $diff['updated'] ?? 0 }}</td>
                                                    <td>{{ $diff['skipped'] ?? 0 }}</td>
                                                    <td>{{ $diff['errors'] ?? 0 }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            <p class="seg-text-muted" style="font-size: 13px;">Nessun sync incrementale eseguito. Usa <code>legacy:sync-incremental</code>.</p>
                        @endif

                        @if (! empty($legacySyncRunLog))
                            <h4 class="mag-section-title" style="font-size: 14px; margin-top: 16px;">Log run recenti</h4>
                            <div class="seg-table-wrap">
                                <table class="seg-table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Run</th>
                                            <th>Stato</th>
                                            <th>Nuovi</th>
                                            <th>Agg.</th>
                                            <th>Skip</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($legacySyncRunLog as $log)
                                            <tr wire:key="legacy-log-{{ $log['run_id'] }}">
                                                <td>{{ $log['started_at'] }}</td>
                                                <td><code>{{ Str::limit($log['run_id'], 8, '') }}</code></td>
                                                <td>{{ $log['dry_run'] ? 'dry-run' : $log['status'] }}</td>
                                                <td>{{ $log['totals']['new'] }}</td>
                                                <td>{{ $log['totals']['updated'] }}</td>
                                                <td>{{ $log['totals']['skipped'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endcan
                <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px;">
                    @can('viewAny', Spatie\Activitylog\Models\Activity::class)
                        <a href="{{ route('admin.audit') }}?modulo=legacy" class="seg-btn seg-btn-secondary" wire:navigate>
                            Audit import legacy
                        </a>
                    @endcan
                    <a href="#go-live-checklist" class="seg-btn seg-btn-ghost">Checklist go-live</a>
                </div>
                <ul class="seg-text-muted" style="margin: 16px 0 0; padding-left: 20px; font-size: 13px;">
                    <li><code>rentri:preflight</code> verde</li>
                    <li><code>rentri:import-legacy --report</code> coerente con dati attesi</li>
                    <li>Audit log: eventi legacy tracciati per entità</li>
                    <li>Magazzino/registro riconciliati manualmente</li>
                </ul>
            </div>
        </section>
    </div>
</div>
