<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.trasporti') }}" wire:navigate>← Trasporti rifiuti</a>
            </p>
            <h1>Nuovo trasporto</h1>
            <p>Crea un trasporto rifiuti senza passare da una richiesta di svuotamento.</p>
        </div>
    </div>

    <form wire:submit="save" class="seg-card seg-card-padding seg-content-max">
        <div class="seg-form-grid">
            <div class="seg-form-group--span2">
                <label class="seg-label">Trasportatore</label>
                <input wire:model.live.debounce.300ms="trasportatoreSearch" type="search" class="seg-input"
                    placeholder="Cerca per ragione sociale o P.IVA…" style="margin-bottom: 6px;" />
                <select wire:model="trasportatoreId" class="seg-input">
                    <option value="">— Nessuno / da indicare —</option>
                    @foreach ($this->trasportatori as $ana)
                        <option value="{{ $ana->id }}">{{ $ana->ragione_sociale }}
                            @if ($ana->piva) ({{ $ana->piva }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('trasportatoreId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="seg-form-group--span2">
                <label class="seg-label">Destinatario (impianto) *</label>
                <input wire:model.live.debounce.300ms="destinatarioSearch" type="search" class="seg-input"
                    placeholder="Cerca per ragione sociale o P.IVA…" style="margin-bottom: 6px;" />
                <select wire:model="destinatarioId" class="seg-input">
                    <option value="">— Seleziona —</option>
                    @foreach ($this->destinatari as $ana)
                        <option value="{{ $ana->id }}">{{ $ana->ragione_sociale }}
                            @if ($ana->piva) ({{ $ana->piva }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('destinatarioId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="seg-label">Targa mezzo</label>
                <input type="text" wire:model="targaMezzo" class="seg-input" style="text-transform: uppercase;" />
                @error('targaMezzo') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="seg-label">Conducente</label>
                <input type="text" wire:model="conducente" class="seg-input" />
                @error('conducente') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="seg-label">Data trasporto *</label>
                <input type="date" wire:model="dataTrasporto" class="seg-input" />
                @error('dataTrasporto') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="seg-label">Codice CER *</label>
                <select wire:model="codiceCerId" class="seg-input">
                    <option value="">— Seleziona —</option>
                    @foreach ($this->codiciCer as $cer)
                        <option value="{{ $cer->id }}">{{ $cer->codice }} — {{ Str::limit($cer->descrizione, 50) }}</option>
                    @endforeach
                </select>
                @error('codiceCerId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="seg-label">Quantità (kg) *</label>
                <input type="number" step="0.0001" min="0.0001" wire:model="quantitaKg" class="seg-input" />
                @error('quantitaKg') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="seg-form-group--span2">
                <label class="seg-label">VFU collegato (opzionale)</label>
                <input wire:model.live.debounce.300ms="vfuSearch" type="search" class="seg-input"
                    placeholder="Cerca per targa o telaio…" style="margin-bottom: 6px;" />
                <select wire:model="vfuRegistrationId" class="seg-input">
                    <option value="">— Nessun VFU —</option>
                    @foreach ($this->vfuOptions as $vfu)
                        <option value="{{ $vfu->id }}">{{ $vfu->targa }} / {{ $vfu->telaio }}
                            @if ($vfu->marca) — {{ $vfu->marca }} {{ $vfu->modello }} @endif
                        </option>
                    @endforeach
                </select>
                @error('vfuRegistrationId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="seg-form-group--span2">
                <label class="seg-label">Blocco FIR</label>
                <select wire:model="firBloccoId" class="seg-input">
                    <option value="">— Automatico alla vidima —</option>
                    @foreach ($this->blocchiFir as $blocco)
                        <option value="{{ $blocco->id }}">{{ $blocco->codice_blocco }}
                            ({{ $blocco->progressiviRimanenti() }} rimanenti)
                        </option>
                    @endforeach
                </select>
                @error('firBloccoId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="seg-form-group--span2">
                <label class="seg-label">Note</label>
                <textarea wire:model="note" rows="3" class="seg-input" placeholder="Note operative sul trasporto…"></textarea>
                @error('note') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="seg-form-actions" style="margin-top: 24px;">
            <a href="{{ route('segreteria.trasporti') }}" class="seg-btn seg-btn-ghost" wire:navigate>Annulla</a>
            <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Crea trasporto</span>
                <span wire:loading wire:target="save">Creazione…</span>
            </button>
        </div>
    </form>
</div>
