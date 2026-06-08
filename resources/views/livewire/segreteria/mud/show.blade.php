<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.mud') }}" wire:navigate>← MUD</a>
            </p>
            <h1>MUD {{ $dichiarazione->anno_riferimento }}</h1>
            <p>Dichiarazione ambientale — righe aggregate dal registro movimenti.</p>
        </div>
        <div class="seg-header-actions">
            <x-badge-stato :stato="$service->statoBadgeVariant($dichiarazione->stato)" :label="$service->statoLabel($dichiarazione->stato)" />
            <x-mud-telematico-mode-badge />
            @if ($dichiarazione->invio_protocollo)
                <x-badge-stato stato="info" :label="'Protocollo '.$dichiarazione->invio_protocollo" />
            @endif
        </div>
    </div>

    <div class="seg-kpi-grid mag-detail-kpi">
        @php
            $righe = $dichiarazione->righe ?? [];
            $totCarichi = collect($righe)->sum('carichi_kg');
            $totScarichi = collect($righe)->sum('scarichi_kg');
        @endphp
        <x-kpi-card title="Codici CER" :value="(string) count($righe)" />
        <x-kpi-card title="Carichi (kg)" :value="number_format($totCarichi, 2, ',', '.')" />
        <x-kpi-card title="Scarichi (kg)" :value="number_format($totScarichi, 2, ',', '.')" />
        <x-kpi-card title="Saldo (kg)" :value="number_format($totCarichi - $totScarichi, 2, ',', '.')" />
    </div>

    @if ($dichiarazione->stato === \App\Enums\MudStato::Completata)
        <div class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Checklist pre-invio telematico</h2>
            <ul class="seg-doc-list seg-mb-md">
                @foreach ($preInvioChecklist as $item)
                    <li wire:key="check-{{ $item['key'] }}">
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
            <button type="button" class="seg-btn seg-btn-primary"
                wire:click="inviaTelematico"
                wire:confirm="{{ $invioConfirmMessage }}"
                wire:loading.attr="disabled"
                @disabled(! $canInviare)>
                {{ $invioButtonLabel }}
            </button>
            @if (! $telematicoRuntime->isStub())
                <div class="seg-mt-sm">
                    <p class="seg-muted-inline">
                        Gateway {{ $mudEndpoints->environmentLabel() }}:
                        <code>{{ $mudEndpoints->submitUrl() }}</code>
                    </p>
                    <p class="seg-muted-inline">
                        Portale presentazione manuale MASE:
                        <a href="{{ $mudEndpoints::PORTAL_URL }}" target="_blank" rel="noopener noreferrer">mudtelematico.it</a>
                    </p>
                    @if ($endpointProbe !== null)
                        <p class="seg-muted-inline">
                            Reachability ({{ $endpointProbe['method'] }}):
                            @if ($endpointProbe['reachable'])
                                <x-badge-stato stato="success" label="OK" />
                            @else
                                <x-badge-stato stato="warning" label="Non verificato" />
                            @endif
                            @if ($endpointProbe['status'])
                                HTTP {{ $endpointProbe['status'] }}
                            @endif
                        </p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if ($dichiarazione->stato === \App\Enums\MudStato::Inviata)
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="status">
            <strong>Invio completato</strong>
            <span class="seg-muted-inline">Protocollo {{ $dichiarazione->invio_protocollo }}</span>
            <span class="seg-muted-inline">— {{ $dichiarazione->inviata_at?->format('d/m/Y H:i') }}</span>
        </div>
    @endif

    <div class="seg-card seg-card-padding">
        <h2 class="mag-section-title">Azioni</h2>
        <div class="seg-header-actions" style="flex-wrap: wrap; gap: 0.5rem;">
            @if ($dichiarazione->stato === \App\Enums\MudStato::Bozza)
                <button type="button" class="seg-btn seg-btn-primary" wire:click="completa" wire:confirm="Confermi il completamento della dichiarazione MUD?" wire:loading.attr="disabled">
                    Completa dichiarazione
                </button>
            @endif
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportJson" wire:loading.attr="disabled">
                Esporta JSON
            </button>
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportXml" wire:loading.attr="disabled">
                Esporta XML
            </button>
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportPdf" wire:loading.attr="disabled">
                Esporta PDF
            </button>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-page-header seg-page-header--compact" style="padding: 1rem 1.25rem;">
            <h2 class="mag-section-title" style="margin: 0;">Righe per codice CER</h2>
        </div>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Codice CER</th>
                        <th>Descrizione</th>
                        <th>Carichi (kg)</th>
                        <th>Scarichi (kg)</th>
                        <th>Saldo (kg)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($righe as $riga)
                        <tr wire:key="riga-{{ $riga['codice_cer_id'] ?? $loop->index }}">
                            <td class="seg-cell-strong">{{ $riga['codice'] ?? '—' }}</td>
                            <td>{{ $riga['descrizione'] ?? '' }}</td>
                            <td>{{ number_format((float) ($riga['carichi_kg'] ?? 0), 2, ',', '.') }}</td>
                            <td>{{ number_format((float) ($riga['scarichi_kg'] ?? 0), 2, ',', '.') }}</td>
                            <td>{{ number_format((float) ($riga['saldo_kg'] ?? 0), 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="seg-table-empty">Nessun movimento registro nell'anno selezionato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($dichiarazione->export_payload)
        <div class="seg-card seg-card-padding" style="margin-top: 1rem;">
            <h2 class="mag-section-title">Anteprima export JSON</h2>
            <pre class="seg-bg-muted seg-card-padding-sm" style="overflow-x: auto; font-size: 12px; line-height: 1.5; margin: 0; white-space: pre-wrap;">{{ json_encode($dichiarazione->export_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif
</div>
