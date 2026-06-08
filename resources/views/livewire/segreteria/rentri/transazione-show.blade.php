<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <p class="mag-back">
            <a href="{{ route('segreteria.rentri.transazioni') }}" wire:navigate>← Storico transazioni</a>
        </p>
        <h1>Transazione API</h1>
        <p><code>{{ $transazione->transazione_id }}</code></p>
    </div>

    <div class="seg-kpi-grid mag-detail-kpi">
        <x-kpi-card title="Tipo API" :value="$service->tipoApiLabel($transazione->tipo_api)" />
        <x-kpi-card title="Stato" :value="$service->statoLabel($transazione->stato)" />
        <x-kpi-card title="Endpoint" :value="$service->endpointDisplay($transazione)" />
        <x-kpi-card title="Metodo" :value="$service->methodDisplay($transazione)" />
    </div>

    <div class="mag-detail-grid">
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Metadati</h2>
            <dl class="seg-dl">
                <dt>ID transazione</dt>
                <dd><code>{{ $transazione->transazione_id }}</code></dd>
                <dt>Registrata il</dt>
                <dd>{{ $transazione->created_at->format('d/m/Y H:i:s') }}</dd>
                <dt>Completata il</dt>
                <dd>{{ $transazione->completed_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                <dt>Stato</dt>
                <dd>
                    <x-badge-stato
                        :stato="$service->statoBadgeVariant($transazione->stato)"
                        :label="$service->statoLabel($transazione->stato)"
                    />
                </dd>
                @if ($retryLabel = $service->retryStatusLabel($transazione))
                    <dt>Retry MASE</dt>
                    <dd>
                        <x-badge-stato
                            :stato="$service->retryBadgeVariant($transazione) ?? 'warning'"
                            :label="$retryLabel"
                        />
                        @if ($transazione->retry_count > 0)
                            <span class="seg-list-muted"> — tentativi: {{ $transazione->retry_count }}</span>
                        @endif
                    </dd>
                @endif
                @if ($transazione->dead_letter_at)
                    <dt>Dead-letter il</dt>
                    <dd>{{ $transazione->dead_letter_at->format('d/m/Y H:i:s') }}</dd>
                @endif
            </dl>
            @if ($canRetryNow)
                <div style="margin-top: 1rem;">
                    <button type="button" class="seg-btn seg-btn-primary seg-btn-sm" wire:click="retryNow" wire:confirm="Eseguire subito un nuovo tentativo verso MASE?" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="retryNow">Riprova ora</span>
                        <span wire:loading wire:target="retryNow">Retry in corso…</span>
                    </button>
                </div>
            @endif
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Request</h2>
            <p class="mag-section-lead">Payload e header inviati (firma troncata in log).</p>
            <pre class="seg-bg-muted seg-card-padding-sm" style="overflow-x: auto; font-size: 12px; line-height: 1.5; margin: 0; white-space: pre-wrap;">{{ $service->formatJson($transazione->request_json) }}</pre>
        </section>

        <section class="seg-card seg-card-padding seg-form-group--span2">
            <h2 class="mag-section-title">Response</h2>
            <p class="mag-section-lead">Risposta API o messaggio di errore.</p>
            <pre class="seg-bg-muted seg-card-padding-sm" style="overflow-x: auto; font-size: 12px; line-height: 1.5; margin: 0; white-space: pre-wrap;">{{ $service->formatJson($transazione->response_json) }}</pre>
        </section>
    </div>
</div>
