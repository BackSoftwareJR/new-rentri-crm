<div class="op-smontaggio-wizard"
    x-data="{ touchStartX: 0, touchStartY: 0 }"
    @touchstart="touchStartX = $event.changedTouches[0].screenX; touchStartY = $event.changedTouches[0].screenY"
    @touchend="
        const touch = $event.changedTouches[0];
        const dx = touch.screenX - touchStartX;
        const dy = touch.screenY - touchStartY;
        if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
            if (dx < 0) $wire.swipeNext();
            else $wire.swipePrev();
        }
    ">
    @if ($success)
        <div class="op-bn-success">
            <div class="op-bn-success-icon">✅</div>
            <h2 class="op-bn-success-title">Smontaggio completato</h2>
            <p class="op-bn-success-lead">
                Il veicolo è stato marcato come smontato. I ricambi sono stati registrati nella sessione.
            </p>
            <a href="{{ route('operatore.smontaggio') }}" class="op-btn op-btn-primary op-btn-full" wire:navigate>← Torna alla lista</a>
        </div>
    @else
        {{-- Header --}}
        <div class="op-bn-wizard-head">
            <x-breadcrumb
                variant="op"
                :items="[
                    'Operatore' => route('operatore.dashboard'),
                    'Smontaggio' => route('operatore.smontaggio'),
                ]"
                :current="$vfu->targa"
            />
            <div class="op-bn-wizard-vehicle">
                <span class="op-bn-targa op-bn-targa--light">{{ $vfu->targa }}</span>
                <span class="op-bn-wizard-sub">{{ $vfu->marca }} {{ $vfu->modello }}</span>
                <span class="op-bn-pill op-bn-pill--{{ $vfu->stato->badgeStato() }}">{{ $vfu->stato->label() }}</span>
            </div>
        </div>

        @include('livewire.partials.flash-messages')

        @if ($errorMessage)
            <div class="op-bn-alert op-bn-alert--danger">{{ $errorMessage }}</div>
        @endif

        {{-- Step indicator --}}
        <div class="op-bn-steps">
            <button type="button"
                class="op-bn-step {{ $step === 1 ? 'op-bn-step--active' : '' }} {{ $step > 1 ? 'op-bn-step--done' : '' }}"
                wire:click="goToStep(1)">
                1 · Avvia
            </button>
            <button type="button"
                class="op-bn-step {{ $step === 2 ? 'op-bn-step--active' : '' }} {{ $step > 2 ? 'op-bn-step--done' : '' }}"
                wire:click="goToStep(2)"
                @if($step < 2) disabled @endif>
                2 · Ricambi
            </button>
            <button type="button"
                class="op-bn-step {{ $step === 3 ? 'op-bn-step--active' : '' }}"
                wire:click="goToStep(3)"
                @if($step < 3) disabled @endif>
                3 · Completa
            </button>
        </div>

        {{-- Step 1: Confirm start --}}
        @if ($step === 1)
            <section class="op-bn-section">
                <h3 class="op-bn-section-title">Dati veicolo</h3>
                <dl class="op-smontaggio-dl">
                    <dt>Targa</dt><dd>{{ $vfu->targa }}</dd>
                    <dt>Telaio</dt><dd>{{ $vfu->telaio ?? '—' }}</dd>
                    <dt>Marca/Modello</dt><dd>{{ $vfu->veicoloLabel() }}</dd>
                    <dt>Stato</dt><dd>{{ $vfu->stato->label() }}</dd>
                    @if ($session)
                        <dt>Sessione avviata</dt>
                        <dd>{{ $session->started_at?->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
                <p class="op-bn-hint">
                    Il veicolo risulta bonificato. Puoi procedere allo smontaggio e alla catalogazione dei ricambi.
                </p>
            </section>

            <div class="op-bn-wizard-footer">
                <button type="button" class="op-btn op-btn-primary op-btn-full" wire:click="goToStep(2)">
                    Avvia smontaggio →
                </button>
            </div>
        @endif

        {{-- Step 2: Add parts --}}
        @if ($step === 2)
            <section class="op-bn-section">
                <h3 class="op-bn-section-title">Aggiungi ricambio</h3>

                <div class="op-smontaggio-form">
                    <div class="op-form-row">
                        <label class="op-form-label" for="sm-descr">Descrizione <span aria-hidden="true">*</span></label>
                        <input
                            id="sm-descr"
                            type="text"
                            class="op-form-input @error('nuovaDescrizione') op-form-input--error @enderror"
                            wire:model.defer="nuovaDescrizione"
                            placeholder="es. Porta anteriore sinistra, Motore 1.6 TDI…"
                            maxlength="500"
                            autocomplete="off"
                        />
                        @error('nuovaDescrizione')<span class="op-form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="op-form-row">
                        <label class="op-form-label" for="sm-nparte">N° parte</label>
                        <input
                            id="sm-nparte"
                            type="text"
                            class="op-form-input"
                            wire:model.defer="nuovoNumeroParte"
                            placeholder="es. VW-3C0-837-461"
                            maxlength="100"
                            autocomplete="off"
                        />
                    </div>

                    <div class="op-form-row">
                        <label class="op-form-label" for="sm-cond">Condizione <span aria-hidden="true">*</span></label>
                        <select id="sm-cond" class="op-form-select" wire:model.defer="nuovaCondizione">
                            <option value="buono">Buono</option>
                            <option value="accettabile">Accettabile</option>
                            <option value="per_ricambi">Solo per ricambi</option>
                        </select>
                        @error('nuovaCondizione')<span class="op-form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="op-form-row">
                        <label class="op-form-label" for="sm-val">Valore stimato (€)</label>
                        <input
                            id="sm-val"
                            type="number"
                            step="0.01"
                            min="0"
                            inputmode="decimal"
                            autocomplete="off"
                            class="op-form-input @error('nuovoValore') op-form-input--error @enderror"
                            wire:model.defer="nuovoValore"
                            placeholder="0.00"
                        />
                        @error('nuovoValore')<span class="op-form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="op-form-row">
                        <label class="op-form-label" for="sm-foto">Foto (opzionale)</label>
                        <input
                            id="sm-foto"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            class="op-form-file"
                            wire:model="nuovaFoto"
                        />
                        @error('nuovaFoto')<span class="op-form-error">{{ $message }}</span>@enderror
                        @if ($nuovaFoto && ! $errors->has('nuovaFoto'))
                            <p class="op-form-hint">Foto selezionata: {{ $nuovaFoto->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    <button
                        type="button"
                        class="op-btn op-btn-secondary op-btn-full"
                        wire:click="aggiungiRicambio"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="aggiungiRicambio">+ Aggiungi ricambio</span>
                        <span wire:loading wire:target="aggiungiRicambio">Aggiunta…</span>
                    </button>
                </div>
            </section>

            {{-- List of added parts --}}
            <section class="op-bn-section">
                <div class="op-bn-checklist-head">
                    <h3 class="op-bn-section-title">Ricambi catalogati</h3>
                    <span class="op-bn-pill op-bn-pill--info">{{ $ricambi->count() }}</span>
                </div>

                @if ($ricambi->isEmpty())
                    <p class="op-bn-hint">Nessun ricambio aggiunto. Aggiungi almeno un ricambio o vai direttamente al completamento.</p>
                @else
                    <ul class="op-smontaggio-ricambi">
                        @foreach ($ricambi as $ricambio)
                            <li class="op-smontaggio-ricambio-item" wire:key="ric-{{ $ricambio->id }}">
                                <div class="op-smontaggio-ricambio-info">
                                    <strong>{{ $ricambio->descrizione }}</strong>
                                    @if ($ricambio->numero_parte)
                                        <span class="op-smontaggio-ricambio-meta">{{ $ricambio->numero_parte }}</span>
                                    @endif
                                    <span class="op-bn-pill op-bn-pill--{{ $ricambio->condizione === 'buono' ? 'info' : ($ricambio->condizione === 'accettabile' ? 'warn' : 'muted') }}">
                                        {{ $ricambio->condizioneLabel() }}
                                    </span>
                                    @if ($ricambio->valore_stimato)
                                        <span class="op-smontaggio-ricambio-meta">€ {{ number_format((float) $ricambio->valore_stimato, 2, ',', '.') }}</span>
                                    @endif
                                    @if ($ricambio->foto_path)
                                        <a href="{{ $ricambio->fotoUrl() }}" class="op-smontaggio-ricambio-meta" target="_blank" rel="noopener">
                                            📷 foto
                                        </a>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="op-btn op-btn-danger op-btn-xs"
                                    wire:click="rimuoviRicambio({{ $ricambio->id }})"
                                    wire:confirm="Rimuovere questo ricambio?"
                                    aria-label="Rimuovi {{ $ricambio->descrizione }}"
                                >
                                    ✕
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <div class="op-bn-wizard-footer">
                <button type="button" class="op-btn op-btn-primary op-btn-full" wire:click="goToStep(3)">
                    Procedi al riepilogo →
                </button>
            </div>
        @endif

        {{-- Step 3: Review & complete --}}
        @if ($step === 3)
            <section class="op-bn-section">
                <h3 class="op-bn-section-title">Riepilogo smontaggio</h3>

                <dl class="op-smontaggio-dl">
                    <dt>Veicolo</dt><dd>{{ $vfu->targa }} — {{ $vfu->veicoloLabel() }}</dd>
                    <dt>Ricambi catalogati</dt><dd>{{ $ricambi->count() }}</dd>
                    @if ($ricambi->isNotEmpty())
                        <dt>Valore totale stimato</dt>
                        <dd>
                            € {{ number_format($ricambi->sum(fn($r) => (float)$r->valore_stimato), 2, ',', '.') }}
                        </dd>
                    @endif
                </dl>

                @if ($ricambi->isNotEmpty())
                    <ul class="op-smontaggio-ricambi op-smontaggio-ricambi--compact">
                        @foreach ($ricambi as $ricambio)
                            <li wire:key="rev-{{ $ricambio->id }}" class="op-smontaggio-ricambio-review">
                                <div class="op-smontaggio-ricambio-review-main">
                                    <span>{{ $ricambio->descrizione }}</span>
                                    <span class="op-bn-pill op-bn-pill--{{ $ricambio->condizione === 'buono' ? 'info' : ($ricambio->condizione === 'accettabile' ? 'warn' : 'muted') }}">
                                        {{ $ricambio->condizioneLabel() }}
                                    </span>
                                    @if ($ricambio->valore_stimato)
                                        <span class="op-smontaggio-ricambio-meta">€ {{ number_format((float) $ricambio->valore_stimato, 2, ',', '.') }}</span>
                                    @endif
                                    @if ($ricambio->foto_path)
                                        <a href="{{ $ricambio->fotoUrl() }}" class="op-smontaggio-ricambio-meta" target="_blank" rel="noopener">📷 foto</a>
                                    @endif
                                </div>
                                <label class="op-smontaggio-vetrina-check">
                                    <input type="checkbox" wire:model="pubblicaInVetrina.{{ $ricambio->id }}" />
                                    Pubblica in vetrina
                                </label>
                                @if (isset($vetrinaPubblicati[$ricambio->id]))
                                    <a href="{{ route('segreteria.ecommerce.prodotti.show', $vetrinaPubblicati[$ricambio->id]['prodotto_id']) }}"
                                       class="op-smontaggio-ricambio-meta"
                                       target="_blank"
                                       rel="noopener">
                                        Vedi prodotto vetrina →
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <button
                        type="button"
                        class="op-btn op-btn-secondary op-btn-full"
                        wire:click="pubblicaSelezionatiInVetrina"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="pubblicaSelezionatiInVetrina">Pubblica selezionati in vetrina</span>
                        <span wire:loading wire:target="pubblicaSelezionatiInVetrina">Pubblicazione…</span>
                    </button>
                @endif
            </section>

            <section class="op-bn-section">
                <h3 class="op-bn-section-title">Note (opzionale)</h3>
                <textarea
                    class="op-form-input op-form-textarea @error('note') op-form-input--error @enderror"
                    wire:model.defer="note"
                    rows="3"
                    maxlength="2000"
                    placeholder="Aggiungi note sullo smontaggio…"
                ></textarea>
                @error('note')<span class="op-form-error">{{ $message }}</span>@enderror
            </section>

            <div class="op-bn-wizard-footer">
                <button type="button" class="op-btn op-btn-secondary op-btn-full" wire:click="saveNote" wire:loading.attr="disabled">
                    Salva note
                </button>
                <button
                    type="button"
                    class="op-btn op-btn-primary op-btn-full op-btn-phase"
                    wire:click="completa"
                    wire:loading.attr="disabled"
                    wire:confirm="Confermare il completamento dello smontaggio? Il veicolo verrà marcato come smontato."
                >
                    <span wire:loading.remove wire:target="completa">Completa smontaggio ✓</span>
                    <span wire:loading wire:target="completa">Completamento…</span>
                </button>
            </div>
        @endif
    @endif
</div>
