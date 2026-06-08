<div>
    @include('livewire.partials.flash-messages')

    @if ($settings->ambiente === 'produzione' && $apiStub)
        <x-rentri-prod-stub-banner />
    @endif

    <div class="seg-page-header">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <h1 style="margin: 0;">Impostazioni RENTRI</h1>
            <x-rentri-api-mode-badge :label="$rentriApiModeLabel" :variant="$rentriApiModeVariant" />
        </div>
        <p>Collegamento account operatore: certificato interoperabilità (mTLS), ambiente demo/produzione e test API.</p>
    </div>

    @if ($demoActive)
        <div class="seg-card seg-card-padding" style="margin-bottom: 1rem; border-left: 4px solid #f59e0b;">
            <h2 class="seg-step-title" style="margin-top: 0;">Sandbox MASE — palestra operativa</h2>
            <p class="seg-step-desc" style="margin-bottom: 0.75rem;">
                @if ($sessionDemo)
                    Scope demo attivo in <strong>sessione</strong> — dati RENTRI/FIR isolati (<code>is_demo=true</code>).
                @else
                    Istanza in <strong>modalità DEMO</strong> (<code>APP_DEMO_MODE=true</code>).
                @endif
                API solo <strong>demoapi.rentri.gov.it</strong> (mai api.rentri.gov.it).
            </p>
            <ul class="seg-step-desc" style="margin: 0 0 0.75rem; padding-left: 1.25rem;">
                <li>MASE non usa API key pubblica: caricare certificato PKCS#12 sandbox via wizard.</li>
                <li>Seleziona un <strong>profilo operatore</strong> demo (multi-sede) prima di applicare il preset.</li>
                @if (config('demo.rentri.offline_no_http'))
                    <li>Chiamate HTTP disabilitate (<code>RENTRI_DEMO_NO_HTTP=true</code>) — stub locali.</li>
                @else
                    <li>Con certificato sandbox: chiamate live verso demoapi; senza certificato: stub locale.</li>
                @endif
            </ul>
            <div style="margin-bottom: 0.75rem;">
                <label class="seg-field-label" for="selectedOperatorPreset">Profilo operatore sandbox</label>
                <select id="selectedOperatorPreset" wire:model.live="selectedOperatorPreset" class="seg-input" style="max-width: 420px;">
                    @foreach ($operatorProfiles as $profile)
                        <option value="{{ $profile['key'] }}">
                            {{ $profile['label'] }} — CF {{ $profile['cf_operatore'] }}, sito {{ $profile['num_iscr_sito'] }}
                        </option>
                    @endforeach
                </select>
                <p class="seg-list-muted" style="margin: 0.35rem 0 0; font-size: 13px;">
                    Anteprima: CF operatore <code>{{ $sandboxPreset['cf_operatore'] }}</code>, sito <code>{{ $sandboxPreset['num_iscr_sito'] }}</code>.
                </p>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <button type="button" class="seg-btn seg-btn-secondary" wire:click="applySandboxPreset">
                    Applica preset sandbox
                </button>
                <button type="button" class="seg-btn seg-btn-primary" wire:click="testSandboxConnection">
                    Test connessione sandbox
                </button>
            </div>
            @if ($settings->cert_scadenza)
                <p class="seg-list-muted" style="margin: 0.75rem 0 0;">
                    Certificato mTLS scade il <strong>{{ $settings->cert_scadenza->format('d/m/Y') }}</strong>
                    @if ($settings->cert_scadenza->isPast())
                        <span class="seg-badge seg-badge-danger">Scaduto</span>
                    @elseif ($settings->cert_scadenza->lte(now()->addDays(30)))
                        <span class="seg-badge seg-badge-warning">In scadenza</span>
                    @else
                        <span class="seg-badge seg-badge-success">Valido</span>
                    @endif
                </p>
            @else
                <p class="seg-list-muted" style="margin: 0.75rem 0 0;">Certificato mTLS non ancora caricato.</p>
            @endif
            <div style="margin-top: 0.75rem;">
                <label class="seg-field-label" for="note_operatore">Note operatore (demo)</label>
                <textarea id="note_operatore" class="seg-input" rows="2" wire:model.defer="note_operatore" placeholder="Es. credenziali sandbox, contatto MASE, promemoria formazione…"></textarea>
                <button type="button" class="seg-btn seg-btn-secondary" style="margin-top: 0.5rem;" wire:click="saveNoteOperatore">Salva note</button>
            </div>
            @error('preset') <p class="seg-field-error">{{ $message }}</p> @enderror
        </div>
    @endif

    <div class="seg-card seg-card-padding" style="margin-bottom: 1rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 8px;">
            <x-rentri-api-mode-badge :label="$rentriApiModeLabel" :variant="$rentriApiModeVariant" />
            <span @class([
                'seg-badge',
                'seg-badge-success' => in_array($connectionStatus['state'], ['connected_sandbox', 'connected_production', 'stub_mode'], true),
                'seg-badge-warning' => $connectionStatus['state'] === 'not_configured',
                'seg-badge-danger' => $connectionStatus['state'] === 'cert_expired',
            ])>{{ $connectionStatus['label'] }}</span>
        </div>
        <p class="seg-list-muted" style="margin: 0;">
            Certificato: {{ $connectionStatus['certificato'] }}
            @if ($connectionStatus['ultimo_health'])
                — ultimo test: {{ $connectionStatus['ultimo_health'] }}
            @endif
        </p>
    </div>

    @if ($onboardingComplete)
        <div class="seg-card seg-card-padding" style="margin-bottom: 1rem;">
            <p class="seg-badge seg-badge-success" style="display: inline-block; margin-bottom: 0.5rem;">Configurazione completa</p>
            <p class="mag-section-lead" style="margin: 0;">
                Ambiente <strong>{{ $settings->ambiente }}</strong> — sito <strong>{{ $settings->num_iscr_sito }}</strong>.
                @if ($settings->last_health_check_at)
                    Ultimo test: {{ $settings->last_health_check_at->format('d/m/Y H:i') }}.
                @endif
            </p>
        </div>
    @endif

    <div class="seg-wizard-steps seg-wizard-steps-3">
        @foreach ([1 => 'Dati operatore', 2 => 'Certificato', 3 => 'Test connessione', 4 => 'Passaggio produzione'] as $num => $label)
            @if ($num > 1)
                <div @class(['seg-wizard-connector', 'done' => $step > $num - 1 || ($onboardingComplete && $num <= 3) || ($num === 4 && $liveEnabled)])></div>
            @endif
            <button type="button"
                @class(['seg-wizard-step', 'active' => $step === $num, 'done' => ($step > $num) || ($onboardingComplete && $num <= 3) || ($num === 4 && $liveEnabled)])
                wire:click="goToStep({{ $num }})"
                @disabled(!$onboardingComplete && $num > $step)
                @if($num === 4) @disabled(!$onboardingComplete) @endif
                aria-current="{{ $step === $num ? 'step' : 'false' }}">
                <span class="seg-wizard-step-num">{{ $num }}</span>
                <span class="seg-wizard-step-label">{{ $label }}</span>
                @if ($num === 2 && ($certPreviews['mtls']['state'] ?? '') !== 'missing')
                    <span class="seg-wizard-step-hint">{{ $certPreviews['mtls']['scadenza'] ?? 'OK' }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="seg-cert-preview-grid" style="margin-bottom: 1rem;">
        @foreach (['mtls' => $certPreviews['mtls'], 'firma' => $certPreviews['firma']] as $kind => $preview)
            <x-cert-expiry-preview :preview="$preview">
                <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="openCertModal('{{ $kind }}')">
                    Dettagli scadenza
                </button>
            </x-cert-expiry-preview>
        @endforeach
    </div>

    <div class="seg-card seg-card-padding seg-content-max">

        @if ($step === 1)
            <h2 class="seg-step-title">Dati operatore</h2>
            <p class="seg-step-desc">P.IVA impresa, CF operatore incaricato (per API blocchi FIR) e numero iscrizione sito RENTRI.</p>

            <form wire:submit="saveOperatorData" class="seg-form-grid">
                <x-form-field label="Ambiente" name="ambiente" required>
                    <select id="ambiente" wire:model="ambiente" class="seg-input @error('ambiente') is-invalid @enderror">
                        <option value="sandbox">Sandbox / Demo (demoapi.rentri.gov.it)</option>
                        <option value="produzione">Produzione (api.rentri.gov.it)</option>
                    </select>
                </x-form-field>
                <x-form-field label="CF operatore API" name="cf_operatore" required hint="Codice fiscale dell'incaricato interoperabilità MASE.">
                    <input type="text" id="cf_operatore" wire:model="cf_operatore" class="seg-input @error('cf_operatore') is-invalid @enderror" maxlength="16" placeholder="Incaricato interoperabilità" />
                </x-form-field>
                <x-form-field label="CF / P.IVA impresa" name="cf" required>
                    <input type="text" id="cf" wire:model="cf" class="seg-input @error('cf') is-invalid @enderror" maxlength="16" />
                </x-form-field>
                <x-form-field label="Partita IVA" name="piva" required>
                    <input type="text" id="piva" wire:model="piva" class="seg-input @error('piva') is-invalid @enderror" maxlength="20" />
                </x-form-field>
                <x-form-field label="Ragione sociale" name="ragione_sociale" required>
                    <input type="text" id="ragione_sociale" wire:model="ragione_sociale" class="seg-input @error('ragione_sociale') is-invalid @enderror" />
                </x-form-field>
                <x-form-field label="N. iscrizione sito RENTRI" name="num_iscr_sito" required hint="Es. OP123XXXXXXXX00-PD00001" class="seg-form-group--span2">
                    <input type="text" id="num_iscr_sito" wire:model="num_iscr_sito" class="seg-input @error('num_iscr_sito') is-invalid @enderror" placeholder="Es. OP123XXXXXXXX00-PD00001" />
                </x-form-field>
                <div class="seg-form-group seg-form-group--span2">
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveOperatorData">Salva e continua</span>
                        <span wire:loading wire:target="saveOperatorData">Salvataggio…</span>
                    </button>
                </div>
            </form>
        @endif

        @if ($step === 2)
            <h2 class="seg-step-title">Certificato interoperabilità PKCS#12</h2>
            <p class="seg-step-desc">Certificato CA dominio RENTRI o eIDAS per mTLS verso le API ministeriali. Password cifrata nel database; file in storage privato.</p>

            @if ($settings->cert_path_encrypted)
                <x-cert-expiry-preview :preview="$certPreviews['mtls']" style="margin-bottom: 1rem;" />
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 1rem;">
                    <strong>Certificato configurato</strong>
                    @if ($settings->cert_scadenza)
                        <p class="seg-list-muted" style="margin: 0.25rem 0 0;">Scadenza: {{ $settings->cert_scadenza->format('d/m/Y') }}</p>
                    @endif
                </div>
            @else
                <x-cert-expiry-preview :preview="$certPreviews['mtls']" style="margin-bottom: 1rem;" />
            @endif

            <form wire:submit="uploadCertificato" class="seg-form-grid">
                <x-form-field label="File .p12 / .pfx" name="certificato" required hint="Certificato PKCS#12 rilasciato da CA dominio RENTRI." class="seg-form-group--span2">
                    <input type="file" id="certificato" wire:model="certificato" accept=".p12,.pfx" class="seg-input @error('certificato') is-invalid @enderror" />
                </x-form-field>
                <x-form-field label="Password keystore" name="cert_password" required class="seg-form-group--span2">
                    <input type="password" id="cert_password" wire:model="cert_password" class="seg-input @error('cert_password') is-invalid @enderror" autocomplete="new-password" />
                </x-form-field>
                <div class="seg-form-group seg-form-group--span2 seg-form-actions">
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="previousStep">Indietro</button>
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled" wire:target="uploadCertificato,certificato">
                        <span wire:loading.remove wire:target="uploadCertificato,certificato">Carica certificato</span>
                        <span wire:loading wire:target="uploadCertificato,certificato">Caricamento…</span>
                    </button>
                </div>
            </form>
        @endif

        @if ($step === 3)
            <h2 class="seg-step-title">Test connessione</h2>
            <p class="seg-step-desc">
                @if ($apiStub)
                    Modalità stub: simula risposta senza chiamate HTTP ministeriali.
                @else
                    Chiamata live: recupero blocchi FIR (health) + codifiche CER via mTLS.
                @endif
            </p>

            <x-cert-expiry-preview :preview="$certPreviews['mtls']" style="margin-bottom: 1rem;" />

            <div class="seg-kpi-grid" style="margin-bottom: 1rem;">
                <x-kpi-card title="Ambiente" :value="$settings->ambiente" />
                <x-kpi-card title="Modalità API" :value="$rentriApiModeLabel" />
                <x-kpi-card title="N. iscrizione sito" :value="$settings->num_iscr_sito ?? '—'" />
            </div>

            @if ($healthStatus)
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 1rem;">
                    <strong>Esito: {{ $healthStatus['status'] ?? '—' }}</strong>
                    <p class="seg-list-muted" style="margin: 0.25rem 0 0;">{{ $healthStatus['message'] ?? '' }}</p>
                    @if (! empty($healthStatus['correlation_id']))
                        <p class="seg-list-muted" style="margin: 0.25rem 0 0;">Correlation ID: {{ $healthStatus['correlation_id'] }}</p>
                    @endif
                </div>
            @endif

            @if ($lastCodificheCount !== null)
                <p class="seg-list-muted">Codifiche CER disponibili: <strong>{{ $lastCodificheCount }}</strong> voci.</p>
            @endif

            @if ($lastSyncError !== null)
                <div class="seg-card seg-card-padding-sm" style="background: #fef3cd; border-left: 4px solid #f59e0b; margin-bottom: 1rem;">
                    <strong>⚠️ Connessione OK — sincronizzazione automatica fallita</strong>
                    <p class="seg-list-muted" style="margin: 0.25rem 0 0;">{{ $lastSyncError }}</p>
                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" style="margin-top: 0.5rem;" wire:click="testConnection">
                        Riprova sincronizzazione
                    </button>
                </div>
            @elseif ($lastCodificheSync !== null)
                <div class="seg-card seg-card-padding-sm" style="background: #d1fae5; border-left: 4px solid #10b981; margin-bottom: 1rem;">
                    <strong>
                        ✅ Connesso
                        • {{ $lastCodificheSync }} codici CER sincronizzati
                        • {{ $lastSerbatoi }} serbatoi pronti
                    </strong>
                    <p class="seg-list-muted" style="margin: 0.25rem 0 0;">Sincronizzazione in background avviata (CER + blocchi FIR).</p>
                </div>
            @endif

            @error('health') <p class="seg-field-error">{{ $message }}</p> @enderror

            <div class="seg-form-actions">
                <button type="button" class="seg-btn seg-btn-secondary" wire:click="previousStep">Indietro</button>
                <button type="button" class="seg-btn seg-btn-primary" wire:click="testConnection" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="testConnection">{{ $onboardingComplete ? 'Ripeti test connessione' : 'Esegui test connessione' }}</span>
                    <span wire:loading wire:target="testConnection">
                        <svg style="display:inline;width:1em;height:1em;animation:spin 1s linear infinite;vertical-align:middle;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                        Verifica e sincronizzazione in corso…
                    </span>
                </button>
            </div>
        @endif

        @if ($step === 4)
            <h2 class="seg-step-title">Passaggio produzione</h2>
            <p class="seg-step-desc">
                Checklist unificata switch produzione MASE (env + wizard + preflight).
                L'override UI disabilita lo stub a runtime; per il go-live definitivo impostare anche
                <code>RENTRI_ENV=production</code>, <code>RENTRI_API_STUB=false</code> e <code>RENTRI_FIRMA_STUB=false</code>.
            </p>

            <p class="seg-text-muted" style="margin-bottom: 0.5rem;">
                Runbook: <code>{{ $productionRunbookPath }}</code> ·
                <code>php artisan rentri:production-switch-check --dry-run</code>
            </p>

            @if ($productionActive)
                <div class="seg-alert seg-alert-success" role="status" style="margin-bottom: 1rem;">
                    <strong>Produzione MASE attiva</strong> — API e firma non in stub.
                </div>
            @elseif ($productionSwitchReady)
                <div class="seg-alert seg-alert-warning" role="status" style="margin-bottom: 1rem;">
                    <strong>Checklist completa</strong> — verificare deploy env e avviare monitoraggio 48h post-switch.
                </div>
            @endif

            <p class="seg-text-muted" style="margin-bottom: 1rem;">
                Switch unificato: <strong>{{ $productionSwitchSummary['ok'] }}/{{ $productionSwitchSummary['total'] }}</strong> voci obbligatorie OK
                · Wizard UI: <strong>{{ $prodSummary['ok'] }}/{{ $prodSummary['total'] }}</strong>
            </p>

            <h3 class="mag-section-title" style="font-size: 1rem; margin: 0 0 0.5rem;">Checklist switch produzione</h3>
            <ul class="seg-list" style="list-style: none; padding: 0; margin: 0 0 1rem;">
                @foreach ($productionSwitchChecklist as $item)
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                        @if ($item['ok'])
                            <span class="seg-badge seg-badge-success">OK</span>
                        @elseif ($item['optional'] ?? false)
                            <span class="seg-badge seg-badge-secondary">Opt</span>
                        @else
                            <span class="seg-badge seg-badge-warning">Da fare</span>
                        @endif
                        {{ $item['label'] }}
                        <span class="seg-text-muted" style="font-size: 0.75rem;">({{ $item['group'] }})</span>
                        @if (! $item['ok'] && $item['hint'])
                            <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['hint'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            <h3 class="mag-section-title" style="font-size: 1rem; margin: 0 0 0.5rem;">Override runtime (wizard UI)</h3>
            <p class="seg-text-muted" style="font-size: 0.875rem; margin: 0 0 0.5rem;">
                Voci wizard per attivazione live senza redeploy immediato del file <code>.env</code>.
            </p>

            <ul class="seg-list" style="list-style: none; padding: 0; margin: 0 0 1rem;">
                @foreach ($prodChecklist as $item)
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                        @if ($item['ok'])
                            <span class="seg-badge seg-badge-success">OK</span>
                        @else
                            <span class="seg-badge seg-badge-warning">Da fare</span>
                        @endif
                        {{ $item['label'] }}
                        @if (! $item['ok'] && $item['hint'])
                            <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['hint'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @error('live') <p class="seg-field-error">{{ $message }}</p> @enderror

            <div class="seg-form-actions">
                <button type="button" class="seg-btn seg-btn-secondary" wire:click="previousStep">Indietro</button>
                @if ($liveEnabled)
                    <span class="seg-badge seg-badge-success">Live attivo dal {{ $settings->live_mode_enabled_at?->format('d/m/Y H:i') }}</span>
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="revertLiveMode" wire:confirm="Disattivare l'override live e tornare allo stub da configurazione?">
                        Rientra in stub
                    </button>
                @elseif ($canEnableLive)
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="enableLiveMode" wire:confirm="Attivare le chiamate API RENTRI live? Verificare certificati e ambiente produzione.">
                        Attiva modalità live
                    </button>
                @else
                    <button type="button" class="seg-btn seg-btn-primary" disabled>Completa la checklist</button>
                @endif
            </div>
        @endif

    </div>

    @if ($onboardingComplete)
        <div class="seg-card seg-card-padding" style="margin-top: 1.5rem;">
            <h2 class="seg-step-title">Validazione reale sandbox MASE</h2>
            <p class="seg-step-desc">
                Wizard «Test reale MASE»: verifica prerequisiti certificato e dati operatore, poi health check live
                e conteggio codifiche CER su
                <a href="{{ $demoapiDocsUrl }}" target="_blank" rel="noopener noreferrer">{{ $sandboxBaseUrl }}</a>.
                La vidimazione FIR non viene eseguita automaticamente — vedi
                <code>docs/VALIDAZIONE-SANDBOX-MASE.md</code>.
            </p>

            <ul class="seg-list" style="list-style: none; padding: 0; margin: 0 0 1rem;">
                @foreach ($sandboxValidationPrerequisites as $item)
                    <li style="padding: 0.35rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                        @if ($item['status'] === 'ok')
                            <span class="seg-badge seg-badge-success">OK</span>
                        @else
                            <span class="seg-badge seg-badge-warning">KO</span>
                        @endif
                        {{ $item['label'] }}
                        <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['message'] }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($sandboxValidationResult)
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 1rem;">
                    <strong>Esito validazione: {{ strtoupper($sandboxValidationResult['overall'] ?? '—') }}</strong>
                    <ul class="seg-list" style="list-style: none; padding: 0.5rem 0 0; margin: 0;">
                        @foreach ($sandboxValidationResult['steps'] ?? [] as $step)
                            <li style="padding: 0.25rem 0;">
                                @if ($step['status'] === 'ok')
                                    <span class="seg-badge seg-badge-success">OK</span>
                                @elseif ($step['status'] === 'fail')
                                    <span class="seg-badge seg-badge-danger">FAIL</span>
                                @elseif ($step['status'] === 'info')
                                    <span class="seg-badge seg-badge-secondary">INFO</span>
                                @else
                                    <span class="seg-badge seg-badge-warning">{{ strtoupper($step['status']) }}</span>
                                @endif
                                {{ $step['label'] }} — {{ $step['message'] }}
                            </li>
                        @endforeach
                    </ul>
                    @if (($sandboxValidationResult['codifiche_count'] ?? null) !== null)
                        <p class="seg-list-muted" style="margin: 0.5rem 0 0;">Codifiche CER: <strong>{{ $sandboxValidationResult['codifiche_count'] }}</strong></p>
                    @endif
                </div>
            @endif

            <div class="seg-form-actions">
                <button type="button" class="seg-btn seg-btn-primary" wire:click="runSandboxValidation" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="runSandboxValidation">Esegui test reale MASE</span>
                    <span wire:loading wire:target="runSandboxValidation">Validazione in corso…</span>
                </button>
            </div>
        </div>

        <div class="seg-card seg-card-padding" style="margin-top: 1.5rem;">
            <h2 class="seg-step-title">Validazione certificato produzione</h2>
            <p class="seg-step-desc">
                Wizard E2E verso <strong>{{ $productionBaseUrl }}</strong> — solo
                <code>api.rentri.gov.it</code> in modalità produzione (demoapi bloccato).
                Richiede certificato mTLS + firma xFIR, <code>RENTRI_ENV=production</code> e stub disabilitati.
                Runbook: <code>{{ $productionValidationRunbook }}</code> ·
                guida: <code>{{ $productionValidationDoc }}</code>.
            </p>

            <ul class="seg-list" style="list-style: none; padding: 0; margin: 0 0 1rem;">
                @foreach ($productionCertValidationPrerequisites as $item)
                    <li style="padding: 0.35rem 0; border-bottom: 1px solid var(--seg-border, #e5e7eb);">
                        @if ($item['status'] === 'ok')
                            <span class="seg-badge seg-badge-success">OK</span>
                        @else
                            <span class="seg-badge seg-badge-warning">KO</span>
                        @endif
                        {{ $item['label'] }}
                        <span class="seg-text-muted" style="display: block; font-size: 0.875rem;">{{ $item['message'] }}</span>
                    </li>
                @endforeach
            </ul>

            @if ($productionCertValidationResult)
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 1rem;">
                    <strong>Esito validazione produzione: {{ strtoupper($productionCertValidationResult['overall'] ?? '—') }}</strong>
                    <ul class="seg-list" style="list-style: none; padding: 0.5rem 0 0; margin: 0;">
                        @foreach ($productionCertValidationResult['steps'] ?? [] as $step)
                            <li style="padding: 0.25rem 0;">
                                @if ($step['status'] === 'ok')
                                    <span class="seg-badge seg-badge-success">OK</span>
                                @elseif ($step['status'] === 'fail')
                                    <span class="seg-badge seg-badge-danger">FAIL</span>
                                @elseif ($step['status'] === 'info')
                                    <span class="seg-badge seg-badge-secondary">INFO</span>
                                @else
                                    <span class="seg-badge seg-badge-warning">{{ strtoupper($step['status']) }}</span>
                                @endif
                                {{ $step['label'] }} — {{ $step['message'] }}
                            </li>
                        @endforeach
                    </ul>
                    @if (($productionCertValidationResult['codifiche_count'] ?? null) !== null)
                        <p class="seg-list-muted" style="margin: 0.5rem 0 0;">Codifiche CER: <strong>{{ $productionCertValidationResult['codifiche_count'] }}</strong></p>
                    @endif
                </div>
            @endif

            <div class="seg-form-actions">
                <button type="button" class="seg-btn seg-btn-primary" wire:click="runProductionCertValidation" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="runProductionCertValidation">Esegui validazione certificato produzione</span>
                    <span wire:loading wire:target="runProductionCertValidation">Validazione in corso…</span>
                </button>
            </div>
        </div>

        <div class="seg-card seg-card-padding" style="margin-top: 1.5rem;">
            <h2 class="seg-step-title">Certificato firma remota xFIR</h2>
            <p class="seg-step-desc">
                Certificato <strong>distinto</strong> dal PKCS#12 interoperabilità (mTLS). Usato per la firma COSE_Sign1 sui formulari FIR digitali post-vidima.
                @if ($firmaStub)
                    Modalità firma stub attiva (RENTRI_FIRMA_STUB=true).
                @endif
            </p>

            @if ($settings->firma_cert_path_encrypted)
                <div class="seg-bg-muted seg-card-padding-sm" style="margin-bottom: 1rem;">
                    <strong>Certificato firma configurato</strong>
                    @if ($settings->firma_cert_scadenza)
                        <p class="seg-list-muted" style="margin: 0.25rem 0 0;">Scadenza: {{ $settings->firma_cert_scadenza->format('d/m/Y') }}</p>
                    @endif
                </div>
            @endif

            <form wire:submit="uploadFirmaCertificato" class="seg-form-grid">
                <div class="seg-form-group seg-form-group--span2">
                    <label class="seg-label">File firma .p12 / .pfx *</label>
                    <input type="file" wire:model="firma_certificato" accept=".p12,.pfx" class="seg-input @error('firma_certificato') is-invalid @enderror" />
                    @error('firma_certificato') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <label class="seg-label">Password keystore firma *</label>
                    <input type="password" wire:model="firma_cert_password" class="seg-input @error('firma_cert_password') is-invalid @enderror" autocomplete="new-password" />
                    @error('firma_cert_password') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled" wire:target="uploadFirmaCertificato,firma_certificato">
                        <span wire:loading.remove wire:target="uploadFirmaCertificato,firma_certificato">Carica certificato firma</span>
                        <span wire:loading wire:target="uploadFirmaCertificato,firma_certificato">Caricamento…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    @php
        $modalPreview = $certModalKind === 'firma' ? $certPreviews['firma'] : $certPreviews['mtls'];
    @endphp
    <x-modal :show="$certModalOpen" :title="$modalPreview['label']" close-action="closeCertModal">
        <x-cert-expiry-preview :preview="$modalPreview" />
        <p class="seg-list-muted" style="margin-top: 1rem;">
            @if ($modalPreview['days_remaining'] !== null)
                Giorni alla scadenza: <strong>{{ $modalPreview['days_remaining'] }}</strong>
            @else
                Carica un certificato PKCS#12 per visualizzare la scadenza.
            @endif
        </p>
        <div class="seg-modal-footer">
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="closeCertModal">Chiudi</button>
        </div>
    </x-modal>
</div>
