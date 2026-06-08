<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>{{ $vfuRegistration ? 'Modifica accettazione VFU' : 'Nuova accettazione VFU' }}</h1>
        <p>Wizard in 4 passaggi: dati veicolo, documenti, riepilogo, conferma.</p>
    </div>

    <div class="seg-wizard-steps seg-wizard-steps-4">
        @foreach ([1 => 'Dati veicolo', 2 => 'Documenti', 3 => 'Riepilogo', 4 => 'Conferma'] as $num => $label)
            @if ($num > 1)
                <div @class(['seg-wizard-connector', 'done' => $step > $num - 1])></div>
            @endif
            <button type="button" @class(['seg-wizard-step', 'active' => $step === $num, 'done' => $step > $num])
                wire:click="goToStep({{ $num }})" @disabled($num > $step)>
                <span class="seg-wizard-step-num">{{ $num }}</span>
                <span class="seg-wizard-step-label">{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="seg-card seg-card-padding seg-content-max">

        @if ($step === 1)
            <h2 class="seg-step-title">Dati veicolo e intestatario</h2>
            <p class="seg-step-desc">Inserisci i dati del veicolo. Puoi caricare il certificato di rottamazione provvisorio per estrarre i dati dal PDF.</p>

            <div class="seg-form-group--span2" style="margin-bottom: 20px;">
                <label class="seg-label">Certificato rottamazione (PDF)</label>
                <input type="file" wire:model="certificatoPdf" accept="application/pdf" class="seg-input" />
                @error('certificatoPdf') <p class="seg-field-error">{{ $message }}</p> @enderror
                <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" style="margin-top: 8px;"
                    wire:click="uploadCertificato" wire:loading.attr="disabled" wire:target="uploadCertificato,certificatoPdf">
                    Estrai dati da PDF
                </button>
            </div>

            @if (! empty($extractedPreview))
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 20px;">
                    <strong>Dati estratti dal certificato</strong>
                    <ul class="seg-list-muted">
                        @foreach ($extractedPreview as $k => $v)
                            @if ($v)
                                <li>{{ ucfirst(str_replace('_', ' ', $k)) }}: {{ $v }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="seg-form-grid">
                <div>
                    <label class="seg-label">Tipo veicolo</label>
                    <select wire:model="tipo_veicolo" class="seg-input">
                        <option value="Autovettura">Autovettura</option>
                        <option value="Autocarro">Autocarro</option>
                        <option value="Motociclo">Motociclo</option>
                        <option value="Altro">Altro</option>
                    </select>
                </div>
                <div>
                    <label class="seg-label">Nazione</label>
                    <input type="text" wire:model="nazione" class="seg-input" />
                </div>
                <x-form-field label="Targa" name="targa" required>
                    <input type="text" id="targa" wire:model="targa" class="seg-input" style="text-transform: uppercase;" />
                </x-form-field>
                <x-form-field label="Telaio" name="telaio" required hint="17 caratteri, senza spazi.">
                    <input type="text" id="telaio" wire:model="telaio" class="seg-input" maxlength="17" style="text-transform: uppercase;" />
                </x-form-field>
                <x-form-field label="Codice motore" name="codice_motore" required>
                    <input type="text" id="codice_motore" wire:model="codice_motore" class="seg-input" />
                </x-form-field>
                <x-form-field label="Peso (kg)" name="peso_kg" required hint="Peso a vuoto del veicolo per il carico in magazzino.">
                    <input type="number" id="peso_kg" step="0.01" min="1" wire:model="peso_kg" class="seg-input" />
                </x-form-field>
                <div>
                    <label class="seg-label">Marca</label>
                    <input type="text" wire:model="marca" class="seg-input" />
                </div>
                <div>
                    <label class="seg-label">Modello</label>
                    <input type="text" wire:model="modello" class="seg-input" />
                </div>
                <div>
                    <label class="seg-label">Nome</label>
                    <input type="text" wire:model="nome" class="seg-input" />
                </div>
                <div>
                    <label class="seg-label">Cognome</label>
                    <input type="text" wire:model="cognome" class="seg-input" />
                </div>
                <div class="seg-form-group--span2">
                    <label class="seg-label">Proprietario</label>
                    <input type="text" wire:model="proprietario" class="seg-input" />
                </div>
                <div>
                    <label class="seg-label">Codice fiscale</label>
                    <input type="text" wire:model="codice_fiscale" class="seg-input" />
                </div>
                <div>
                    <label class="seg-label">Provincia</label>
                    <input type="text" wire:model="provincia" class="seg-input" maxlength="2" style="text-transform: uppercase;" />
                </div>
            </div>
        @endif

        @if ($step === 2)
            <h2 class="seg-step-title">Documenti</h2>
            <p class="seg-step-desc">Carica documento identità e carta di circolazione (obbligatori). Gli altri documenti sono facoltativi.</p>

            @php
                $uploaded = $vfuRegistration?->documents->keyBy(fn ($d) => $d->tipo->value) ?? collect();
            @endphp

            <div class="seg-doc-upload-grid">
                <div class="seg-doc-upload-item">
                    <h3>Documento identità *</h3>
                    @if ($uploaded->has('documento_identita'))
                        <p class="seg-hint">✓ {{ $uploaded->get('documento_identita')->original_name }}</p>
                    @endif
                    <input type="file" wire:model="documentoIdentita" accept=".pdf,.jpg,.jpeg,.png" class="seg-input" />
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="uploadDocument('documento_identita')">Carica</button>
                </div>
                <div class="seg-doc-upload-item">
                    <h3>Carta di circolazione *</h3>
                    @if ($uploaded->has('carta_circolazione'))
                        <p class="seg-hint">✓ {{ $uploaded->get('carta_circolazione')->original_name }}</p>
                    @endif
                    <input type="file" wire:model="cartaCircolazione" accept=".pdf,.jpg,.jpeg,.png" class="seg-input" />
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="uploadDocument('carta_circolazione')">Carica</button>
                </div>
                <div class="seg-doc-upload-item">
                    <h3>Denuncia smarrimento</h3>
                    @if ($uploaded->has('denuncia_smarrimento'))
                        <p class="seg-hint">✓ {{ $uploaded->get('denuncia_smarrimento')->original_name }}</p>
                    @endif
                    <input type="file" wire:model="denunciaSmarrimento" class="seg-input" />
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="uploadDocument('denuncia_smarrimento')">Carica</button>
                </div>
                <div class="seg-doc-upload-item">
                    <h3>Certificato di proprietà</h3>
                    @if ($uploaded->has('certificato_proprieta'))
                        <p class="seg-hint">✓ {{ $uploaded->get('certificato_proprieta')->original_name }}</p>
                    @endif
                    <input type="file" wire:model="certificatoProprieta" class="seg-input" />
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="uploadDocument('certificato_proprieta')">Carica</button>
                </div>
                <div class="seg-doc-upload-item">
                    <h3>Delega</h3>
                    @if ($uploaded->has('delega'))
                        <p class="seg-hint">✓ {{ $uploaded->get('delega')->original_name }}</p>
                    @endif
                    <input type="file" wire:model="delega" class="seg-input" />
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="uploadDocument('delega')">Carica</button>
                </div>
            </div>
        @endif

        @if ($step === 3)
            <h2 class="seg-step-title">Riepilogo</h2>
            <p class="seg-step-desc">Verifica i dati prima di confermare l'accettazione.</p>
            <dl class="seg-dl">
                <dt>Targa / Telaio</dt>
                <dd>{{ $targa }} / {{ $telaio }}</dd>
                <dt>Veicolo</dt>
                <dd>{{ $marca }} {{ $modello }} ({{ $tipo_veicolo }})</dd>
                <dt>Peso</dt>
                <dd>{{ $peso_kg }} kg</dd>
                <dt>Proprietario</dt>
                <dd>{{ $proprietario ?: trim($nome.' '.$cognome) ?: '—' }}</dd>
                <dt>Documenti</dt>
                <dd>
                    @if ($vfuRegistration)
                        {{ $vfuRegistration->documents->count() }} file caricati
                    @else
                        Salva i dati veicolo per caricare i documenti
                    @endif
                </dd>
            </dl>
        @endif

        @if ($step === 4)
            <h2 class="seg-step-title">Conferma accettazione</h2>
            <p class="seg-step-desc">Completando l'accettazione il veicolo passa in stato <strong>accettato</strong> (pronto per bonifica) e viene registrato un movimento di <strong>CARICO</strong> CER 16.01.04* nel registro movimenti.</p>
            @error('confirm') <p class="seg-alert seg-alert-error">{{ $message }}</p> @enderror
            <div class="seg-success-box">
                <p>Pronto per confermare l'accettazione di <strong>{{ $targa }}</strong>?</p>
            </div>
        @endif

        <div class="seg-form-actions" style="margin-top: 24px; justify-content: space-between;">
            <div>
                @if ($step > 1)
                    <button type="button" class="seg-btn seg-btn-ghost" wire:click="previousStep">Indietro</button>
                @else
                    <a href="{{ route('segreteria.vfu.index') }}" class="seg-btn seg-btn-ghost" wire:navigate>Annulla</a>
                @endif
            </div>
            <div>
                @if ($step < 4)
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="nextStep">Avanti</button>
                @else
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="confirm"
                        wire:confirm="Confermare l'accettazione del veicolo {{ $targa }}?">
                        Conferma accettazione
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
