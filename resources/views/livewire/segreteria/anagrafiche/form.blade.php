<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Dati anagrafici e autorizzazioni al trasporto rifiuti.</p>
        </div>
        <a href="{{ $anagrafica ? route('segreteria.anagrafiche.show', $anagrafica) : route('segreteria.anagrafiche') }}" class="seg-btn seg-btn-secondary" wire:navigate>Indietro</a>
    </div>

    <form wire:submit="save" class="seg-form-stack">
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Dati contatto</h2>

            <div class="seg-form-group">
                <label class="seg-label">Tipo contatto *</label>
                <div class="seg-tipo-grid">
                    @foreach (\App\Models\Anagrafica::TIPI as $t)
                        <label class="seg-tipo-option @if($tipo === $t) is-active @endif">
                            <input type="radio" wire:model.live="tipo" value="{{ $t }}" class="sr-only" />
                            <span>{{ ucfirst(str_replace('_', ' ', $t)) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('tipo') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>

            <div class="seg-form-grid">
                <div class="seg-form-group seg-form-group--span2">
                    <label for="ragione_sociale" class="seg-label">Ragione sociale / Nome *</label>
                    <input id="ragione_sociale" type="text" wire:model="ragione_sociale" class="seg-input @error('ragione_sociale') is-invalid @enderror" />
                    @error('ragione_sociale') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label for="piva" class="seg-label">Partita IVA</label>
                    <input id="piva" type="text" wire:model="piva" class="seg-input @error('piva') is-invalid @enderror" />
                    @error('piva') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label for="codice_fiscale" class="seg-label">Codice fiscale</label>
                    <input id="codice_fiscale" type="text" wire:model="codice_fiscale" class="seg-input @error('codice_fiscale') is-invalid @enderror" maxlength="16" />
                    @error('codice_fiscale') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label for="codice_sdi" class="seg-label">Codice SDI</label>
                    <input id="codice_sdi" type="text" wire:model="codice_sdi" class="seg-input" maxlength="7" />
                </div>
                <div class="seg-form-group">
                    <label for="email" class="seg-label">Email</label>
                    <input id="email" type="email" wire:model="email" class="seg-input @error('email') is-invalid @enderror" />
                    @error('email') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label for="pec" class="seg-label">PEC</label>
                    <input id="pec" type="email" wire:model="pec" class="seg-input" />
                </div>
                <div class="seg-form-group">
                    <label for="telefono" class="seg-label">Telefono</label>
                    <input id="telefono" type="text" wire:model="telefono" class="seg-input" />
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <label for="indirizzo" class="seg-label">Indirizzo</label>
                    <input id="indirizzo" type="text" wire:model="indirizzo" class="seg-input" />
                </div>
                <div class="seg-form-group">
                    <label for="cap" class="seg-label">CAP</label>
                    <input id="cap" type="text" wire:model="cap" class="seg-input" />
                </div>
                <div class="seg-form-group">
                    <label for="citta" class="seg-label">Città</label>
                    <input id="citta" type="text" wire:model="citta" class="seg-input" />
                </div>
                <div class="seg-form-group">
                    <label for="provincia" class="seg-label">Provincia</label>
                    <input id="provincia" type="text" wire:model="provincia" class="seg-input" maxlength="2" />
                </div>
                @if ($tipo === 'impianto')
                    <div class="seg-form-group seg-form-group--span2">
                        <label class="seg-checkbox">
                            <input type="checkbox" wire:model="gestisce_trasporti" />
                            <span>Gestisce anche trasporti</span>
                        </label>
                    </div>
                @endif
                <div class="seg-form-group seg-form-group--span2">
                    <label for="note" class="seg-label">Note</label>
                    <textarea id="note" wire:model="note" class="seg-input seg-textarea" rows="3"></textarea>
                </div>
            </div>
        </div>

        @if (in_array($tipo, ['trasportatore', 'impianto'], true))
            <div class="seg-card seg-card-padding">
                <div class="seg-section-header">
                    <h2 class="seg-section-title">Autorizzazioni</h2>
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="addAuthorizationRow">+ Aggiungi riga</button>
                </div>

                @foreach ($authorizations as $index => $auth)
                    <div class="seg-auth-row" wire:key="auth-row-{{ $index }}">
                        <div class="seg-form-grid seg-form-grid--auth">
                            <div class="seg-form-group">
                                <label class="seg-label">Numero</label>
                                <input type="text" wire:model="authorizations.{{ $index }}.numero" class="seg-input @error('authorizations.'.$index.'.numero') is-invalid @enderror" />
                                @error('authorizations.'.$index.'.numero') <p class="seg-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="seg-form-group">
                                <label class="seg-label">Rilasciata il</label>
                                <input type="date" wire:model="authorizations.{{ $index }}.rilasciata_il" class="seg-input @error('authorizations.'.$index.'.rilasciata_il') is-invalid @enderror" />
                                @error('authorizations.'.$index.'.rilasciata_il') <p class="seg-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="seg-form-group">
                                <label class="seg-label">Scade il</label>
                                <input type="date" wire:model="authorizations.{{ $index }}.scade_il" class="seg-input @error('authorizations.'.$index.'.scade_il') is-invalid @enderror" />
                                @error('authorizations.'.$index.'.scade_il') <p class="seg-field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="seg-form-group seg-form-group--actions">
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm seg-btn-danger"
                                    wire:click="removeAuthorizationRow({{ $index }})"
                                    @if(count($authorizations) <= 1) disabled @endif>
                                    Rimuovi
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="seg-form-actions">
            <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Salva</span>
                <span wire:loading wire:target="save">Salvataggio…</span>
            </button>
        </div>
    </form>
</div>
