<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <div>
            <x-breadcrumb
                :items="[
                    'Magazzino' => route('segreteria.magazzino'),
                    'Registro movimenti' => route('segreteria.registro-movimenti'),
                ]"
                :current="'Movimento #'.$movimento->id"
            />
            <h1>Movimento #{{ $movimento->id }}</h1>
            <p>{{ $movimento->codiceCer?->codice }} — {{ $movimento->codiceCer?->descrizione }}</p>
        </div>
        <x-badge-stato
            :stato="$movimento->tipo->value === 'carico' ? 'success' : 'info'"
            :label="ucfirst($movimento->tipo->value)"
        />
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Quantità" :value="number_format((float) $movimento->peso_kg, 2, ',', '.') . ' kg'" />
        <x-kpi-card title="Data movimento" :value="$movimento->data_movimento->format('d/m/Y H:i')" />
        <x-kpi-card title="Operatore" :value="$operatoreLabel" />
    </div>

    <div class="mag-detail-grid">
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Dettaglio movimento</h2>
            <dl class="seg-dl">
                <dt>Tipo</dt>
                <dd>{{ ucfirst($movimento->tipo->value) }}</dd>
                <dt>Codice CER</dt>
                <dd>
                    <a href="{{ route('segreteria.magazzino.show', $movimento->codice_cer_id) }}" wire:navigate>
                        {{ $movimento->codiceCer?->codice }}
                    </a>
                    — {{ $movimento->codiceCer?->descrizione }}
                </dd>
                <dt>Peso</dt>
                <dd>{{ number_format((float) $movimento->peso_kg, 4, ',', '.') }} kg</dd>
                <dt>Data</dt>
                <dd>{{ $movimento->data_movimento->format('d/m/Y H:i:s') }}</dd>
                <dt>Provenienza</dt>
                <dd>
                    @if ($sourceInfo['href'])
                        <a href="{{ $sourceInfo['href'] }}" wire:navigate>{{ $sourceInfo['label'] }}</a>
                    @else
                        {{ $sourceInfo['label'] }}
                    @endif
                </dd>
                <dt>Note</dt>
                <dd>{{ $movimento->note ?: '—' }}</dd>
            </dl>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Trasmissione RENTRI</h2>
            @if ($movimento->isLocked())
                <x-badge-stato stato="warning" label="Bloccato" />
            @elseif ($movimento->rentri_trasmesso)
                <x-badge-stato stato="primary" label="Trasmesso" />
            @else
                <x-badge-stato stato="muted" label="Da trasmettere" />
            @endif

            <dl class="seg-dl" style="margin-top: 16px;">
                <dt>Stato</dt>
                <dd>
                    @if ($movimento->rentri_trasmesso)
                        Trasmesso a RENTRI
                    @else
                        Non ancora trasmesso
                    @endif
                </dd>
                @if ($movimento->rentriTransmissione)
                    <dt>Data trasmissione</dt>
                    <dd>{{ $movimento->rentriTransmissione->trasmesso_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt>Periodo</dt>
                    <dd>
                        {{ $movimento->rentriTransmissione->periodo_da?->format('d/m/Y') ?? '—' }}
                        —
                        {{ $movimento->rentriTransmissione->periodo_a?->format('d/m/Y') ?? '—' }}
                    </dd>
                    <dt>Esito</dt>
                    <dd>{{ $movimento->rentriTransmissione->esito ?? '—' }}</dd>
                @endif
                @if ($movimento->locked_at)
                    <dt>Bloccato il</dt>
                    <dd>{{ $movimento->locked_at->format('d/m/Y H:i') }}</dd>
                @endif
            </dl>
        </section>

        @if ($linkedDocuments !== [])
            <section class="seg-card seg-card-padding">
                <h2 class="mag-section-title">Documenti collegati</h2>
                <ul class="seg-list-muted">
                    @foreach ($linkedDocuments as $doc)
                        <li>
                            @if ($doc['href'])
                                <a href="{{ $doc['href'] }}" wire:navigate>{{ $doc['label'] }}</a>
                            @else
                                {{ $doc['label'] }}
                            @endif
                            @if ($doc['meta'])
                                <span class="seg-text-muted"> — {{ $doc['meta'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</div>
