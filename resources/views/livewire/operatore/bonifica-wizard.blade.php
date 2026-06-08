<div class="op-bn-wizard">
    @if ($success)
        <div class="op-bn-success">
            <div class="op-bn-success-icon">✅</div>
            <h2 class="op-bn-success-title">Bonifica completata</h2>
            <p class="op-bn-success-lead">Carichi registrati in registro movimenti e magazzino rifiuti.</p>
            <a href="{{ route('operatore.bonifica') }}" class="op-btn op-btn-primary op-btn-full" wire:navigate>← Torna alla lista</a>
        </div>
    @else
        <div class="op-bn-wizard-head">
            <a href="{{ route('operatore.bonifica') }}" class="op-bn-back" wire:navigate>← Lista</a>
            <div class="op-bn-wizard-vehicle">
                <span class="op-bn-targa op-bn-targa--light">{{ $vfu->targa }}</span>
                <span class="op-bn-wizard-sub">{{ $vfu->marca }} {{ $vfu->modello }}</span>
            </div>
            @if ($enriched['bonifica_giorni_alla_scadenza'] !== null && ! $pericolosiCompletata)
                <p class="op-bn-wizard-deadline {{ ($enriched['bonifica_giorni_alla_scadenza'] ?? 0) < 0 ? 'op-bn-meta--danger' : '' }}">
                    Scadenza pericolosi: {{ $enriched['bonifica_pericolosi_deadline'] }}
                    ({{ $enriched['bonifica_giorni_alla_scadenza'] }} gg)
                </p>
            @endif
        </div>

        @include('livewire.partials.flash-messages')

        @if ($errorMessage)
            <div class="op-bn-alert op-bn-alert--danger">{{ $errorMessage }}</div>
        @endif

        <div class="op-bn-steps">
            @if ($cerPericolosi->isNotEmpty())
                <button type="button" class="op-bn-step {{ $step === 1 ? 'op-bn-step--active' : '' }} {{ $pericolosiCompletata ? 'op-bn-step--done' : '' }}" wire:click="$set('step', '1')">
                    1 · Pericolosi
                </button>
            @endif
            <button type="button" class="op-bn-step {{ $step === 2 ? 'op-bn-step--active' : '' }}" wire:click="$set('step', 2)" @if($cerPericolosi->isNotEmpty() && ! $pericolosiCompletata) disabled @endif>
                {{ $cerPericolosi->isNotEmpty() ? '2 · ' : '' }}Altri rifiuti
            </button>
        </div>

        @if ($step === 1 && $cerPericolosi->isNotEmpty() && ! $pericolosiCompletata)
            <section class="op-bn-section op-bn-section--danger">
                <div class="op-bn-checklist-head">
                    <h3 class="op-bn-section-title">Checklist pericolosi</h3>
                    <span class="op-bn-pill op-bn-pill--{{ $checklistSummary['complete'] ? 'info' : 'warn' }}">
                        {{ $checklistSummary['done'] }}/{{ $checklistSummary['total'] }}
                    </span>
                </div>
                <ul class="op-bn-checklist">
                    @foreach ($checklistSteps as $stepItem)
                        <li class="op-bn-checklist-item {{ $stepItem['done'] ? 'op-bn-checklist-item--done' : '' }}" wire:key="chk-{{ $stepItem['key'] }}">
                            @if ($stepItem['manual'])
                                <label class="op-bn-checklist-label">
                                    <input type="checkbox" wire:model.live="checklist.{{ $stepItem['key'] }}" />
                                    <span>{{ $stepItem['label'] }}</span>
                                </label>
                            @else
                                <span class="op-bn-checklist-auto {{ $stepItem['done'] ? 'op-bn-checklist-auto--done' : '' }}">
                                    {{ $stepItem['done'] ? '✓' : '○' }} {{ $stepItem['label'] }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="op-bn-section op-bn-section--danger">
                <h3 class="op-bn-section-title">Liquidi e rifiuti pericolosi (prima)</h3>
                @foreach ($cerPericolosi as $cer)
                    @include('livewire.operatore.partials.bonifica-cer-row', ['cer' => $cer])
                @endforeach
            </section>
        @else
            <section class="op-bn-section">
                <h3 class="op-bn-section-title">Altri rifiuti e completamento</h3>
                @foreach ($cerAltri as $cer)
                    @include('livewire.operatore.partials.bonifica-cer-row', ['cer' => $cer])
                @endforeach
                @if ($cerAltri->isEmpty() && $cerPericolosi->isNotEmpty())
                    <p class="op-bn-hint">Nessun codice non pericoloso configurato.</p>
                @endif
            </section>
        @endif

        <div class="op-bn-wizard-footer">
            <button type="button" class="op-btn op-btn-secondary op-btn-full" wire:click="saveDraft" wire:loading.attr="disabled">
                Salva bozza
            </button>

            @if ($step === 1 && $cerPericolosi->isNotEmpty() && ! $pericolosiCompletata)
                <button type="button" class="op-btn op-btn-primary op-btn-full op-btn-phase" wire:click="confirmPericolosi" wire:loading.attr="disabled">
                    Conferma fase pericolosi
                </button>
            @else
                <button type="button" class="op-btn op-btn-primary op-btn-full op-btn-phase" wire:click="confirmBonifica" wire:loading.attr="disabled">
                    Completa bonifica
                </button>
            @endif
        </div>
    @endif
</div>
