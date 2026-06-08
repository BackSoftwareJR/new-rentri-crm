<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>{{ $vfuRegistration->targa }}</h1>
            <p>{{ $vfuRegistration->veicoloLabel() }} — Telaio {{ $vfuRegistration->telaio }}</p>
        </div>
        <div class="seg-form-actions">
            @if ($canCreateFattura)
                <a href="{{ route('segreteria.fatture.create', ['riferimento_vfu_id' => $vfuRegistration->id]) }}"
                   class="seg-btn seg-btn-primary" wire:navigate>
                    Crea fattura
                </a>
            @endif
            @if (in_array($vfuRegistration->stato, [\App\Enums\VfuStato::Bozza, \App\Enums\VfuStato::InAccettazione], true))
                <a href="{{ route('segreteria.vfu.edit', $vfuRegistration) }}" class="seg-btn seg-btn-secondary" wire:navigate>Modifica accettazione</a>
            @endif
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="exportStoricoCsv">
                Export storico CSV
            </button>
            <a href="{{ route('segreteria.vfu.index') }}" class="seg-btn seg-btn-ghost" wire:navigate>← Elenco VFU</a>
        </div>
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="seg-section-title">Avanzamento pratica</h2>
        <x-vfu-timeline :steps="$timelineSteps" />
    </div>

    @if ($vfuRegistration->stato === \App\Enums\VfuStato::InSmontaggio)
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Smontaggio in corso</h2>
            @if ($smontaggioSession)
                <dl class="seg-dl">
                    <dt>Sessione avviata il</dt>
                    <dd>{{ $smontaggioSession->started_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt>Ricambi registrati</dt>
                    <dd>{{ $smontaggioSession->ricambi->count() }}</dd>
                    <dt>Stato sessione</dt>
                    <dd><x-badge-stato stato="warning" :label="ucfirst(str_replace('_', ' ', $smontaggioSession->stato))" /></dd>
                </dl>
            @else
                <p class="seg-muted-inline">Nessuna sessione di smontaggio attiva registrata.</p>
            @endif
            @if (auth()->user()?->hasRole(['admin', 'editor', 'operatore']))
                <div class="seg-form-actions" style="margin-top: 12px;">
                    <a href="{{ route('operatore.smontaggio.wizard', $vfuRegistration) }}"
                       class="seg-btn seg-btn-primary" wire:navigate>
                        Vai a smontaggio
                    </a>
                </div>
            @endif
        </div>
    @endif

    <div class="seg-detail-grid">
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Dati veicolo</h2>
            <dl class="seg-dl">
                <dt>Stato</dt>
                <dd><x-badge-stato :stato="$vfuRegistration->stato->badgeStato()" :label="$vfuRegistration->stato->label()" /></dd>
                <dt>Tipo</dt>
                <dd>{{ $vfuRegistration->tipo_veicolo }} ({{ $vfuRegistration->nazione }})</dd>
                <dt>Codice motore</dt>
                <dd>{{ $vfuRegistration->codice_motore ?: '—' }}</dd>
                <dt>Peso</dt>
                <dd>{{ number_format((float) $vfuRegistration->peso_kg, 2, ',', '.') }} kg</dd>
                <dt>Data consegna</dt>
                <dd>{{ $vfuRegistration->data_consegna?->format('d/m/Y') ?? '—' }}</dd>
                <dt>Data accettazione</dt>
                <dd>{{ $vfuRegistration->data_accettazione?->format('d/m/Y') ?? '—' }}</dd>
            </dl>
        </div>

        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Intestatario</h2>
            <dl class="seg-dl">
                <dt>Proprietario</dt>
                <dd>{{ $vfuRegistration->proprietario ?: '—' }}</dd>
                <dt>Codice fiscale</dt>
                <dd>{{ $vfuRegistration->codice_fiscale ?: '—' }}</dd>
                <dt>Indirizzo</dt>
                <dd>{{ $vfuRegistration->indirizzo ?: '—' }}</dd>
                <dt>Comune</dt>
                <dd>{{ $vfuRegistration->comune ?: '—' }} {{ $vfuRegistration->provincia ? '('.$vfuRegistration->provincia.')' : '' }}</dd>
            </dl>
        </div>
    </div>

    @if ($certificatoEligible)
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Certificato rottamazione</h2>
            <p class="seg-step-desc">Generazione stub PDF — non ufficiale, senza firma digitale. Usa anteprima e stampa prima del download.</p>
            <div class="seg-form-actions seg-certificato-actions">
                <button type="button" class="seg-btn seg-btn-secondary"
                    wire:click="toggleCertificatoPreview">
                    {{ $showCertificatoPreview ? 'Chiudi anteprima' : 'Anteprima certificato' }}
                </button>
                @if ($showCertificatoPreview && $certificatoPreviewHtml)
                    <button type="button" class="seg-btn seg-btn-secondary" id="seg-certificato-print-btn" onclick="window.printCertificatoPreview?.()">
                        Stampa
                    </button>
                @endif
                <button type="button" class="seg-btn seg-btn-primary"
                    wire:click="downloadCertificato">
                    Scarica PDF
                </button>
            </div>
            @if ($certificatoPreviewHtml)
                <div class="seg-certificato-preview" id="seg-certificato-preview">
                    <iframe id="seg-certificato-preview-frame" title="Anteprima certificato rottamazione" srcdoc="{{ e($certificatoPreviewHtml) }}"></iframe>
                </div>
                @once
                    <script>
                        window.printCertificatoPreview = function () {
                            const frame = document.getElementById('seg-certificato-preview-frame');
                            frame?.contentWindow?.print();
                        };
                    </script>
                @endonce
            @endif
        </div>
    @endif

    <div class="seg-card seg-card-padding">
        <h2 class="seg-section-title">Documenti accettazione</h2>
        @if ($vfuRegistration->documents->isEmpty())
            <p class="seg-muted-inline">Nessun documento caricato.</p>
        @else
            <ul class="seg-doc-list">
                @foreach ($vfuRegistration->documents as $doc)
                    <li wire:key="doc-{{ $doc->id }}">
                        <span>{{ $doc->tipo->label() }}</span>
                        <span class="seg-muted-inline">{{ $doc->original_name }}</span>
                        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm"
                            wire:click="downloadDocument({{ $doc->id }})">
                            Scarica
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="seg-section-title">Allegati pratica</h2>
        <p class="seg-step-desc">PDF o immagini (max 5 MB) — contratti, foto, verbali e altro.</p>

        @if ($vfuRegistration->documenti->isNotEmpty())
            <ul class="seg-doc-list seg-mb-md">
                @foreach ($vfuRegistration->documenti as $allegato)
                    <li wire:key="allegato-{{ $allegato->id }}">
                        <x-badge-stato stato="muted" :label="$allegato->tipo->label()" />
                        <span class="seg-muted-inline">{{ $allegato->original_name }}</span>
                        @if ($allegato->uploader)
                            <span class="seg-muted-inline">— {{ $allegato->uploader->name }}</span>
                        @endif
                        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm"
                            wire:click="downloadAllegato({{ $allegato->id }})">
                            Scarica
                        </button>
                        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm seg-btn-danger"
                            wire:click="deleteAllegato({{ $allegato->id }})"
                            wire:confirm="Eliminare questo allegato?">
                            Elimina
                        </button>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="seg-muted-inline seg-mb-md">Nessun allegato caricato.</p>
        @endif

        <form wire:submit="uploadAllegato" class="seg-form-grid">
            <div class="seg-form-group">
                <label for="allegatoTipo" class="seg-label">Tipo allegato</label>
                <select id="allegatoTipo" wire:model="allegatoTipo" class="seg-input">
                    @foreach ($allegatoTipi as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                    @endforeach
                </select>
                @error('allegatoTipo') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
            <div class="seg-form-group">
                <label for="allegatoUpload" class="seg-label">File</label>
                <input id="allegatoUpload" type="file" wire:model="allegatoUpload" class="seg-input" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                @error('allegatoUpload') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
            <div class="seg-form-group seg-form-group--span2">
                <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                    Carica allegato
                </button>
            </div>
        </form>
    </div>

    @if ($vfuRegistration->registroMovimenti->isNotEmpty())
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Movimenti registro</h2>
            <ul class="seg-doc-list">
                @foreach ($vfuRegistration->registroMovimenti as $mov)
                    <li wire:key="mov-{{ $mov->id }}">
                        <strong>{{ strtoupper($mov->tipo->value) }}</strong>
                        {{ $mov->codiceCer?->codice }} — {{ number_format((float) $mov->peso_kg, 2, ',', '.') }} kg
                        <span class="seg-muted-inline">{{ $mov->data_movimento->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="seg-card seg-card-padding">
        <h2 class="seg-section-title">Assegna operatore</h2>
        <p class="seg-step-desc">Assegna la bonifica a un operatore. Riceverà notifica in-app e push.</p>
        <div class="seg-form-grid">
            <div class="seg-form-group--span2">
                <label class="seg-label" for="operatoreAssegnatoId">Operatore</label>
                <select id="operatoreAssegnatoId" wire:model="operatoreAssegnatoId" class="seg-input">
                    <option value="">— Seleziona operatore —</option>
                    @foreach ($operatori as $operatore)
                        <option value="{{ $operatore->id }}">{{ $operatore->name }}</option>
                    @endforeach
                </select>
                @error('operatoreAssegnatoId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
        </div>
        @if ($vfuRegistration->operatoreAssegnato)
            <p class="seg-muted-inline">Attualmente assegnato a: {{ $vfuRegistration->operatoreAssegnato->name }}</p>
        @endif
        <div class="seg-form-actions" style="margin-top: 16px;">
            <button type="button" class="seg-btn seg-btn-primary" wire:click="assegnaOperatore">
                Assegna operatore
            </button>
        </div>
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="seg-section-title">Invio ad agenzia</h2>
        <p class="seg-step-desc">Funzione stub: segna la pratica come inviata senza invio email.</p>
        <div class="seg-form-grid">
            <div class="seg-form-group--span2">
                <label class="seg-label" for="agenziaId">Agenzia pratiche auto</label>
                <select id="agenziaId" wire:model="agenziaId" class="seg-input">
                    <option value="">— Seleziona agenzia —</option>
                    @foreach ($agenzie as $a)
                        <option value="{{ $a->id }}">{{ $a->ragione_sociale }}</option>
                    @endforeach
                </select>
                @error('agenziaId') <p class="seg-field-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="seg-form-actions" style="margin-top: 16px;">
            <button type="button" class="seg-btn seg-btn-primary" wire:click="inviaAgenzia"
                @disabled($vfuRegistration->stato === \App\Enums\VfuStato::InviatoAgenzia)>
                Invia ad agenzia (stub)
            </button>
            @if ($vfuRegistration->data_invio_agenzia)
                <span class="seg-muted-inline">Inviato il {{ $vfuRegistration->data_invio_agenzia->format('d/m/Y H:i') }}</span>
            @endif
        </div>
    </div>

    @if (
        auth()->user()?->hasRole(['admin', 'segreteria'])
        && in_array($vfuRegistration->stato, [\App\Enums\VfuStato::Smontato, \App\Enums\VfuStato::Bonificato], true)
    )
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Chiusura pratica</h2>
            <p class="seg-step-desc">Segna il veicolo come rottamato e chiude definitivamente la pratica VFU.</p>
            <div class="seg-form-actions">
                <button type="button" class="seg-btn seg-btn-primary"
                    wire:click="rottama"
                    wire:confirm="Confermi la chiusura della pratica e la rottamazione del veicolo?">
                    Chiudi pratica / Rottama
                </button>
            </div>
        </div>
    @endif

    <div class="seg-form-actions" style="margin-top: 24px;">
        <button type="button" class="seg-btn seg-btn-danger"
            wire:click="delete"
            wire:confirm="Eliminare definitivamente questa pratica VFU?">
            Elimina pratica
        </button>
    </div>
</div>
