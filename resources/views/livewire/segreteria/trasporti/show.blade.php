@push('head-extras')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('foot-scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPcM=" crossorigin=""></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('gpsLeafletMap', (initialLat, initialLng, trasportoId) => ({
        map: null,
        marker: null,
        init() {
            this.$nextTick(() => {
                if (!window.L || !this.$refs.mapEl) return;
                this.map = L.map(this.$refs.mapEl).setView([initialLat, initialLng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);
                this.marker = L.marker([initialLat, initialLng]).addTo(this.map);
                this.marker.bindPopup('<strong>Trasporto #' + trasportoId + '</strong>').openPopup();
            });
        },
        updatePosition(lat, lng) {
            if (!this.map || !this.marker) return;
            const latlng = [lat, lng];
            this.marker.setLatLng(latlng);
            this.map.panTo(latlng, { animate: true });
        },
    }));
});
</script>
@endpush

<div>
    @include('livewire.partials.flash-messages')

    <x-page-header
        :title="'Trasporto #' . $trasporto->id"
        :lead="$trasporto->codiceCer?->codice . ' — ' . $trasporto->codiceCer?->descrizione"
        :backHref="route('segreteria.trasporti')"
        backLabel="← Trasporti rifiuti"
    >
        <x-slot name="actions">
            <x-rentri-api-mode-badge />
            <x-contextual-help title="Dettaglio trasporto">
                Gestisci stati transito, vidima FIR e firma xFIR. Il tracking GPS è disponibile in fase di transito.
            </x-contextual-help>
            <x-badge-stato :stato="$service->statoBadgeVariant($trasporto->stato)" :label="$service->statoLabel($trasporto->stato)" />
        </x-slot>
    </x-page-header>

    <div class="seg-kpi-grid mag-detail-kpi">
        <x-kpi-card title="Quantità" :value="number_format((float) $trasporto->quantita_kg, 2, ',', '.') . ' ' . ($trasporto->codiceCer?->um ?? 'kg')" />
        <x-kpi-card title="Destinatario" :value="$trasporto->destinatario?->ragione_sociale ?? '—'" />
        <x-kpi-card
            title="Trasportatore"
            :value="$trasporto->svuotamento?->trasportatore_omesso ? 'Non indicato' : ($trasporto->svuotamento?->trasportatore?->ragione_sociale ?? '—')"
        />
    </div>

    <div class="mag-detail-grid">
        @if ($trackingAvailable)
            <section class="seg-card seg-card-padding seg-tracking-stub" id="seg-tracking-map" wire:poll.30000ms="refreshGpsPosition">
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 0.35rem;">
                    <h2 class="mag-section-title" style="margin: 0;">Tracking GPS</h2>
                    <x-trasporto-gps-mode-badge />
                </div>
                <p class="mag-section-lead">{{ $trackingPrepLabel }}</p>
                @if (! $gpsRuntime->isStub() && count($gpsPreflight) > 0)
                    <ul class="seg-doc-list seg-mb-md">
                        @foreach ($gpsPreflight as $item)
                            <li wire:key="gps-preflight-{{ $item['key'] }}">
                                @if ($item['ok'])
                                    <x-badge-stato stato="success" label="OK" />
                                @else
                                    <x-badge-stato stato="danger" label="KO" />
                                @endif
                                {{ $item['label'] }}
                                @if (! $item['ok'] && $item['hint'])
                                    <span class="seg-muted-inline">— {{ $item['hint'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($lastGpsPosition)
                    <dl class="seg-dl" style="margin: 0.75rem 0;">
                        <dt>Ultima posizione</dt>
                        <dd>
                            {{ number_format($lastGpsPosition['latitude'], 5, ',', '.') }},
                            {{ number_format($lastGpsPosition['longitude'], 5, ',', '.') }}
                            @if (! empty($lastGpsPosition['speed_kmh']))
                                — {{ number_format((float) $lastGpsPosition['speed_kmh'], 0) }} km/h
                            @endif
                        </dd>
                        @if ($trasporto->gps_tracked_at)
                            <dt>Aggiornata il</dt>
                            <dd>{{ $trasporto->gps_tracked_at->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                @else
                    <p class="seg-text-muted">Nessuna posizione registrata. Usa «Aggiorna posizione» per il primo poll GPS.</p>
                @endif

                @if ($trackingEtaStub)
                    <p class="mag-section-lead" style="margin-top: 0.25rem;">ETA stimata: <strong>{{ $trackingEtaStub }}</strong></p>
                @endif

                @if (! $gpsRuntime->isStub() && $lastGpsPosition)
                    {{-- Interactive Leaflet map for live GPS — wire:ignore prevents Livewire from destroying the map DOM on re-render --}}
                    <div wire:ignore>
                        <div
                            x-data="gpsLeafletMap({{ $lastGpsPosition['latitude'] }}, {{ $lastGpsPosition['longitude'] }}, {{ $trasporto->id }})"
                            x-init="init()"
                            @gps-position-updated.window="updatePosition($event.detail.lat, $event.detail.lng)"
                        >
                            <div x-ref="mapEl" class="seg-tracking-map-leaflet" style="height: 320px; width: 100%; border-radius: 6px; margin: 0.75rem 0;" aria-label="Mappa posizione GPS trasporto #{{ $trasporto->id }}"></div>
                        </div>
                    </div>
                @else
                    <div class="seg-tracking-map-placeholder" style="margin: 0.75rem 0;" aria-label="Tracciamento GPS non disponibile">
                        @if ($gpsRuntime->isStub())
                            <span>Tracciamento GPS non disponibile</span>
                            <small style="display: block; margin-top: 0.25rem; font-size: 0.8em; opacity: 0.7;">Modalità stub — mappa interattiva disponibile in modalità live</small>
                        @else
                            <span>Mappa tracking</span>
                            <small style="display: block; margin-top: 0.25rem; font-size: 0.8em; opacity: 0.7;">Usa «Aggiorna posizione» per ottenere la prima rilevazione GPS</small>
                        @endif
                    </div>
                @endif

                @if (count($trackingTimeline) > 0)
                    <ol class="seg-tracking-timeline" aria-label="Timeline eventi tracking">
                        @foreach ($trackingTimeline as $event)
                            <li class="seg-tracking-timeline-item seg-tracking-timeline-item--{{ $event['status'] }}" wire:key="tracking-{{ $event['key'] }}">
                                <span class="seg-tracking-timeline-label">{{ $event['label'] }}</span>
                                <time class="seg-tracking-timeline-at" datetime="{{ $event['at'] }}">{{ $event['at'] }}</time>
                            </li>
                        @endforeach
                    </ol>
                @endif
                <div class="seg-header-actions" style="margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" class="seg-btn seg-btn-primary seg-btn-sm" wire:click="refreshGpsPosition" wire:loading.attr="disabled"
                        @if (! $gpsRuntime->isStub() && ! $gpsPreflightReady) disabled @endif>
                        <span wire:loading.remove wire:target="refreshGpsPosition">Aggiorna posizione</span>
                        <span wire:loading wire:target="refreshGpsPosition">Aggiornamento…</span>
                    </button>
                    <a href="{{ $gpsMapLink ?? $trackingMapUrl }}" class="seg-btn seg-btn-secondary seg-btn-sm" target="_blank" rel="noopener noreferrer">
                        Apri mappa {{ $lastGpsPosition ? 'GPS' : 'destinazione' }}
                    </a>
                </div>
            </section>
        @endif

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Dettaglio</h2>
            <dl class="seg-dl">
                <dt>Codice CER</dt>
                <dd>
                    <a href="{{ route('segreteria.magazzino.show', $trasporto->codice_cer_id) }}" wire:navigate>
                        {{ $trasporto->codiceCer?->codice }}
                    </a>
                </dd>
                <dt>Impianto destinazione</dt>
                <dd>{{ $trasporto->destinatario?->ragione_sociale ?? '—' }}</dd>
                <dt>Svuotamento collegato</dt>
                <dd>#{{ $trasporto->magazzino_svuotamento_id ?? '—' }}</dd>
                <dt>Creato il</dt>
                <dd>{{ $trasporto->created_at->format('d/m/Y H:i') }}</dd>
                @if ($trasporto->note)
                    <dt>Note</dt>
                    <dd>{{ $trasporto->note }}</dd>
                @endif
            </dl>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Azioni</h2>
            <p class="mag-section-lead">Completamento con scarico magazzino e movimento registro (richiede FIR vidimato).</p>
            <div class="seg-header-actions" style="flex-wrap: wrap; gap: 0.5rem;">
                @if ($trasporto->stato === \App\Enums\TrasportoStato::InPreparazione)
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="avviaTransito" wire:loading.attr="disabled">
                        Avvia transito
                    </button>
                @endif
                @if ($trasporto->stato === \App\Enums\TrasportoStato::InTransito)
                    @if ($canComplete)
                        <button type="button" class="seg-btn seg-btn-primary" wire:click="completa" wire:confirm="Confermi il completamento? Verrà registrato lo scarico in magazzino e nel registro cronologico." wire:loading.attr="disabled">
                            Completa trasporto
                        </button>
                    @else
                        <p class="mag-section-lead" style="margin: 0;">Completamento non disponibile:</p>
                        <ul class="seg-list-muted">
                            @foreach ($completionBlockers as $blocker)
                                <li>{{ $blocker }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                @if (in_array($trasporto->stato, [\App\Enums\TrasportoStato::InPreparazione, \App\Enums\TrasportoStato::InTransito], true))
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="annulla" wire:confirm="Annullare il trasporto?" wire:loading.attr="disabled">
                        Annulla
                    </button>
                @endif
                @if (in_array($trasporto->stato, [\App\Enums\TrasportoStato::Completato, \App\Enums\TrasportoStato::Annullato], true))
                    <p class="mag-empty">Nessuna azione disponibile per questo stato.</p>
                @endif
            </div>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Formulario FIR</h2>
            @if ($trasporto->firCollegato)
                @php
                    $qrData = json_decode($trasporto->firCollegato->qr_payload ?? '{}', true) ?: [];
                @endphp
                <dl class="seg-dl">
                    <dt>Numero FIR</dt>
                    <dd class="seg-cell-strong">{{ $firService->numeroDisplay($trasporto->firCollegato) }}</dd>
                    <dt>Stato</dt>
                    <dd>
                        <x-badge-stato
                            :stato="$firService->statoBadgeVariant($trasporto->firCollegato->stato->value)"
                            :label="$firService->statoLabel($trasporto->firCollegato->stato->value)"
                        />
                    </dd>
                    <dt>Vidimato il</dt>
                    <dd>{{ $trasporto->firCollegato->vidimato_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    @if (! empty($qrData['protocollo']))
                        <dt>Protocollo RENTRI</dt>
                        <dd class="seg-cell-strong">{{ $qrData['protocollo'] }}</dd>
                    @endif
                    @if (! empty($qrData['transazione_id']))
                        <dt>Transazione RENTRI</dt>
                        <dd><code>{{ $qrData['transazione_id'] }}</code></dd>
                    @endif
                    @if (! empty($qrData['qr_code']))
                        <dt>Codice QR</dt>
                        <dd><code>{{ \Illuminate\Support\Str::limit((string) $qrData['qr_code'], 120) }}</code></dd>
                    @endif
                    <dt>Firma xFIR</dt>
                    <dd>
                        @if ($trasporto->firCollegato->firmato_at)
                            <x-badge-stato stato="success" label="Firmato" />
                            <span class="seg-list-muted"> — {{ $trasporto->firCollegato->firmato_at->format('d/m/Y H:i') }}</span>
                        @else
                            <x-badge-stato stato="warning" label="Da firmare" />
                        @endif
                    </dd>
                    <dt>Invio MASE xFIR</dt>
                    <dd>
                        @if ($trasporto->firCollegato->xfir_trasmesso_at)
                            <x-badge-stato stato="success" label="Trasmesso" />
                            <span class="seg-list-muted"> — {{ $trasporto->firCollegato->xfir_trasmesso_at->format('d/m/Y H:i') }}</span>
                            @if ($trasporto->firCollegato->xfir_protocollo)
                                <br><span class="seg-list-muted">Protocollo: <strong>{{ $trasporto->firCollegato->xfir_protocollo }}</strong></span>
                            @endif
                            @if ($trasporto->firCollegato->xfir_transazione_id)
                                <br><span class="seg-list-muted">Transazione: <code>{{ $trasporto->firCollegato->xfir_transazione_id }}</code></span>
                            @endif
                        @elseif ($trasporto->firCollegato->firmato_at)
                            <x-badge-stato stato="warning" label="Da inviare" />
                            <span class="seg-list-muted"> — modalità {{ $rentriApiModeLabel }}</span>
                        @else
                            <span class="seg-list-muted">—</span>
                        @endif
                    </dd>
                </dl>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;">
                    @if ($trasporto->firCollegato->vidimato_at)
                        <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="downloadFirPdf">
                            Scarica FIR PDF
                        </button>
                    @endif
                    @if ($canSignXfir)
                        <button type="button" class="seg-btn seg-btn-primary seg-btn-sm" wire:click="firmaXfir" wire:confirm="Confermi la firma COSE xFIR del formulario?" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="firmaXfir">Firma xFIR</span>
                            <span wire:loading wire:target="firmaXfir">Firma in corso…</span>
                        </button>
                    @elseif ($signBlockReason && ! $trasporto->firCollegato->firmato_at)
                        <p class="seg-field-error" style="margin: 0;">{{ $signBlockReason }}</p>
                    @endif
                    @if ($canTransmitXfir)
                        <button type="button" class="seg-btn seg-btn-primary seg-btn-sm" wire:click="inviaXfirMase" wire:confirm="Confermi l'invio del payload xFIR firmato a RENTRI?" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="inviaXfirMase">Invia xFIR a MASE</span>
                            <span wire:loading wire:target="inviaXfirMase">Invio in corso…</span>
                        </button>
                    @endif
                    @if ($trasporto->firCollegato->xfir_signed_payload)
                        <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="downloadXfirFirmato">
                            Scarica payload firmato
                        </button>
                    @endif
                    @if ($trasporto->firCollegato->xfir_transazione_id)
                        <a href="{{ route('segreteria.rentri.transazioni') }}?tipo_api=xfir" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Storico API xFIR</a>
                    @endif
                    <a href="{{ route('segreteria.fir') }}" class="seg-btn seg-btn-secondary seg-btn-sm" wire:navigate>Vedi elenco FIR</a>
                </div>
            @elseif (in_array($trasporto->stato, [\App\Enums\TrasportoStato::InPreparazione, \App\Enums\TrasportoStato::InTransito], true))
                <p class="mag-section-lead">Vidima un FIR digitale RENTRI per questo trasporto.</p>
                @if ($vidimaChecklist !== [])
                    <div style="margin-bottom: 0.75rem;">
                        <h4 class="mag-section-title" style="font-size: 0.95rem; margin-bottom: 0.35rem;">Checklist pre-vidima RENTRI</h4>
                        <ul class="seg-checklist" style="margin: 0; padding: 0; list-style: none;">
                            @foreach ($vidimaChecklist as $item)
                                <li wire:key="vidima-chk-{{ $item['codice'] }}" style="display: flex; gap: 0.5rem; align-items: flex-start; margin-bottom: 0.35rem;">
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
                        @unless ($vidimaReady)
                            <p class="seg-field-error" style="margin-top: 0.5rem;">Correggi gli elementi KO in impostazioni RENTRI prima di vidimare.</p>
                        @endunless
                    </div>
                @endif
                @if ($vidimaBlockers !== [])
                    <ul class="seg-list-muted" style="margin-bottom: 0.75rem;">
                        @foreach ($vidimaBlockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="seg-btn seg-btn-primary" wire:click="vidimaFir" wire:confirm="Confermi la vidimazione FIR?" wire:loading.attr="disabled" @disabled(! $canVidimaFir)>
                    <span wire:loading.remove wire:target="vidimaFir">Vidima FIR</span>
                    <span wire:loading wire:target="vidimaFir">Vidimazione…</span>
                </button>
            @else
                <p class="mag-empty">FIR non disponibile per lo stato attuale del trasporto.</p>
            @endif
        </section>
    </div>

    <livewire:timeline-widget :subject="$trasporto" title="Storico trasporto" wire:key="timeline-trasporto-{{ $trasporto->id }}" />
</div>
