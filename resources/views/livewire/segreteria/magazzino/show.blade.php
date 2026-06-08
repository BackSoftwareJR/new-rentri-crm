<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.magazzino') }}" wire:navigate>← Magazzino rifiuti</a>
            </p>
            <h1>{{ $serbatoio['codice'] }}</h1>
            <p>{{ $serbatoio['descrizione'] }}</p>
        </div>
        <x-badge-stato :stato="$magazzino->statoBadgeVariant($serbatoio['stato'])" :label="$magazzino->statoBadgeLabel($serbatoio['stato'])" />
    </div>

    @if ($serbatoioAlert)
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="alert">
            <strong>Alert serbatoio:</strong>
            @if (($serbatoioAlert['sotto_soglia_minima'] ?? false))
                <x-badge-stato stato="danger" label="Sotto soglia minima" />
                <span class="seg-muted-inline">
                    Giacenza {{ number_format($serbatoioAlert['quantita_attuale_kg'], 2, ',', '.') }} kg
                    — soglia minima {{ number_format($serbatoioAlert['soglia_minima_kg'], 2, ',', '.') }} kg
                </span>
            @elseif ($serbatoioAlert['stato'] === 'superata')
                <x-badge-stato stato="danger" label="Soglia superata" />
            @else
                <x-badge-stato stato="warning" label="Attenzione (≥{{ \App\Domain\Magazzino\MagazzinoService::SOGLIA_ATTENZIONE_PCT }}%)" />
            @endif
            @if (! ($serbatoioAlert['sotto_soglia_minima'] ?? false))
            <span class="seg-muted-inline">
                Giacenza {{ number_format($serbatoioAlert['quantita_attuale_kg'], 2, ',', '.') }} kg
                @if ($serbatoioAlert['limite_kg'])
                    su limite {{ number_format($serbatoioAlert['limite_kg'], 0, ',', '.') }} kg
                    ({{ number_format($serbatoioAlert['percentuale'], 1, ',', '.') }}%)
                @endif
            </span>
            @endif
        </div>
    @endif

    <div class="seg-kpi-grid mag-detail-kpi">
        <x-kpi-card title="Giacenza attuale" :value="number_format($serbatoio['quantita_attuale_kg'], 2, ',', '.') . ' ' . $serbatoio['um']" />
        <x-kpi-card title="Limite serbatoio" :value="$serbatoio['limite_kg'] ? number_format($serbatoio['limite_kg'], 0, ',', '.') . ' ' . $serbatoio['um'] : '—'" />
        <x-kpi-card title="Riempimento" :value="$serbatoio['percentuale'] !== null ? number_format($serbatoio['percentuale'], 1, ',', '.') . '%' : '—'" />
    </div>

    <div class="mag-detail-grid">
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Soglia minima giacenza</h2>
            <p class="mag-section-lead">Imposta la soglia sotto la quale scattano alert automatici (controllo ogni 6 ore).</p>
            <form wire:submit="salvaSogliaMinima" class="seg-form-grid">
                <div class="seg-form-group">
                    <label class="seg-label">Soglia minima ({{ $serbatoio['um'] }})</label>
                    <input type="number" step="0.0001" min="0" wire:model="soglia_minima_kg" class="seg-input @error('soglia_minima_kg') is-invalid @enderror" placeholder="Es. 50" />
                    @error('soglia_minima_kg') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <button type="submit" class="seg-btn seg-btn-secondary" wire:loading.attr="disabled">Salva soglia</button>
                </div>
            </form>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Carico manuale</h2>
            <p class="mag-section-lead">Registra un carico diretto sul serbatoio. Verrà creato un movimento in registro e aggiornata la giacenza.</p>
            <form wire:submit="salvaCarico" class="seg-form-grid">
                <div class="seg-form-group">
                    <label class="seg-label">Quantità ({{ $serbatoio['um'] }}) *</label>
                    <input type="number" step="0.0001" min="0.0001" wire:model="peso_kg" class="seg-input @error('peso_kg') is-invalid @enderror" />
                    @error('peso_kg') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <label class="seg-label">Descrizione / causale *</label>
                    <textarea wire:model="note" rows="3" class="seg-input @error('note') is-invalid @enderror" placeholder="Es. Carico da fornitore esterno, rettifica inventario…"></textarea>
                    @error('note') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="salvaCarico">Registra carico</span>
                        <span wire:loading wire:target="salvaCarico">Registrazione…</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Cronologia registro movimenti</h2>
            <p class="mag-section-lead">Ultimi movimenti carico/scarico per questo codice CER.</p>
            @if ($cronologia->isEmpty())
                <p class="mag-empty">Nessun movimento registrato.</p>
            @else
                <ul class="mag-timeline">
                    @foreach ($cronologia as $mov)
                        <li class="mag-tl-item" wire:key="mov-{{ $mov->id }}">
                            <div class="mag-tl-head">
                                <span class="mag-tl-date">{{ $mov->data_movimento->format('d/m/Y H:i') }}</span>
                                <x-badge-stato :stato="$mov->tipo->value === 'carico' ? 'success' : 'info'" :label="ucfirst($mov->tipo->value)" />
                                <span class="mag-tl-qty">{{ number_format((float) $mov->peso_kg, 2, ',', '.') }} kg</span>
                            </div>
                            @if ($mov->note)
                                <p class="mag-tl-note">{{ $mov->note }}</p>
                            @endif
                            <p class="mag-tl-meta">
                                Origine:
                                @php
                                    $origine = match ($mov->source_type) {
                                        \App\Models\RegistroMovimento::SOURCE_CARICO_MANUALE => 'Carico manuale',
                                        \App\Models\RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO => 'Bonifica VFU',
                                        \App\Models\RegistroMovimento::SOURCE_VFU_REGISTRATION => 'Accettazione VFU',
                                        \App\Models\RegistroMovimento::SOURCE_TRASPORTO => 'Trasporto',
                                        default => $mov->source_type ? class_basename($mov->source_type) : '—',
                                    };
                                @endphp
                                {{ $origine }}
                                @if ($mov->rentri_trasmesso)
                                    · <span class="mag-tl-rentri">Trasmesso RENTRI</span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('segreteria.registro-movimenti', ['codice_cer_id' => $codiceCer->id]) }}" class="seg-btn seg-btn-secondary seg-btn-sm" wire:navigate>Vedi tutti nel registro</a>
            @endif
        </section>
    </div>

    @if ($puoSvuotare || $storicoSvuotamenti->isNotEmpty())
        <div class="mag-detail-grid" style="margin-top: 1.5rem;">
            @if ($puoSvuotare)
                <section class="seg-card seg-card-padding">
                    <h2 class="mag-section-title">Richiesta svuotamento</h2>
                    <p class="mag-section-lead">
                        Richiedi lo svuotamento verso un impianto autorizzato.
                        Giacenza disponibile: <strong>{{ number_format($quantitaDisponibile, 2, ',', '.') }} {{ $serbatoio['um'] }}</strong>
                        @if ($quantitaImpegnata > 0)
                            <span class="seg-muted-inline">({{ number_format($quantitaImpegnata, 2, ',', '.') }} {{ $serbatoio['um'] }} già impegnati)</span>
                        @endif
                    </p>
                    <form wire:submit="richiediSvuotamento" class="seg-form-grid">
                        <div class="seg-form-group seg-form-group--span2">
                            <label class="seg-label">Impianto destinazione *</label>
                            <select wire:model="impianto_id" class="seg-select @error('impianto_id') is-invalid @enderror">
                                <option value="">— Seleziona impianto —</option>
                                @foreach ($impianti as $imp)
                                    <option value="{{ $imp->id }}">{{ $imp->ragione_sociale }} ({{ $imp->email }})</option>
                                @endforeach
                            </select>
                            @error('impianto_id') <p class="seg-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="seg-form-group seg-form-group--span2">
                            <label class="seg-label">Trasportatore</label>
                            <select wire:model="trasportatore_id" class="seg-select @error('trasportatore_id') is-invalid @enderror" @if($trasportatore_omesso) disabled @endif>
                                <option value="">— Seleziona trasportatore —</option>
                                @foreach ($trasportatori as $tr)
                                    <option value="{{ $tr->id }}">{{ $tr->ragione_sociale }}</option>
                                @endforeach
                            </select>
                            @error('trasportatore_id') <p class="seg-field-error">{{ $message }}</p> @enderror
                            <label class="seg-checkbox" style="margin-top: 0.5rem;">
                                <input type="checkbox" wire:model.live="trasportatore_omesso" />
                                Trasportatore non indicato
                            </label>
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-label">Quantità da svuotare ({{ $serbatoio['um'] }}) *</label>
                            <input type="number" step="0.0001" min="0.0001" wire:model="svuotamento_quantita_kg" class="seg-input @error('svuotamento_quantita_kg') is-invalid @enderror" />
                            @error('svuotamento_quantita_kg') <p class="seg-field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="seg-form-group">
                            <label class="seg-label">Note interne</label>
                            <input type="text" wire:model="svuotamento_note" class="seg-input" placeholder="Opzionale" />
                        </div>
                        <div class="seg-form-group seg-form-group--span2">
                            <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled" wire:confirm="Confermi la richiesta di svuotamento?">
                                <span wire:loading.remove wire:target="richiediSvuotamento">Richiedi svuotamento</span>
                                <span wire:loading wire:target="richiediSvuotamento">Invio richiesta…</span>
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            @if ($storicoSvuotamenti->isNotEmpty())
                <section class="seg-card seg-card-padding">
                    <h2 class="mag-section-title">Storico svuotamenti</h2>
                    <ul class="mag-timeline">
                        @foreach ($storicoSvuotamenti as $sv)
                            <li class="mag-tl-item" wire:key="svu-{{ $sv->id }}">
                                <div class="mag-tl-head">
                                    <span class="mag-tl-date">{{ $sv->created_at->format('d/m/Y H:i') }}</span>
                                    <x-badge-stato :stato="$sv->stato->value === 'richiesto' ? 'warning' : ($sv->stato->value === 'completato' ? 'success' : 'muted')" :label="ucfirst($sv->stato->value)" />
                                    <span class="mag-tl-qty">{{ number_format((float) $sv->quantita_kg, 2, ',', '.') }} kg</span>
                                </div>
                                <p class="mag-tl-meta">
                                    Destinazione: {{ $sv->impianto?->ragione_sociale ?? '—' }}
                                    · Trasportatore:
                                    @if ($sv->trasportatore_omesso)
                                        non indicato
                                    @else
                                        {{ $sv->trasportatore?->ragione_sociale ?? '—' }}
                                    @endif
                                </p>
                                @if ($sv->note_interne)
                                    <p class="mag-tl-note">{{ $sv->note_interne }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif
</div>
