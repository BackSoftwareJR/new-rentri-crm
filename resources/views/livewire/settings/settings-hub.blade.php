<div x-data="{ mobileTab: false }" class="sh-wrapper">

    <div class="seg-page-header">
        <h1>Impostazioni</h1>
        <p>Configura dati azienda, RENTRI, pagamenti, email e sistema.</p>
    </div>

    {{-- ── Flash messages ──────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="sh-toast sh-toast--success" role="status" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="sh-toast-leave" x-transition:leave-start="opacity-1" x-transition:leave-end="opacity-0">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="sh-toast sh-toast--error" role="alert">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Layout: side nav + content ────────────────────────────────────── --}}
    <div class="sh-layout">

        {{-- Side Nav --}}
        <nav class="sh-sidenav" aria-label="Sezioni impostazioni">
            {{-- Mobile dropdown toggle --}}
            <button
                type="button"
                class="sh-mobile-tab-toggle"
                @click="mobileTab = !mobileTab"
                aria-expanded="mobileTab"
                aria-controls="sh-sidenav-list"
            >
                <span>{{ match($activeTab) {
                    'rentri'      => 'RENTRI',
                    'pagamenti'   => 'Pagamenti',
                    'email'       => 'Email & Notifiche',
                    'integrazioni'=> 'Integrazioni',
                    'sistema'     => 'Sistema',
                    default       => 'Azienda',
                } }}</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <ul id="sh-sidenav-list" class="sh-sidenav-list" :class="{ 'sh-sidenav-list--open': mobileTab }">
                @php
                    $tabs = [
                        ['key' => 'azienda',      'label' => 'Azienda',         'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                        ['key' => 'rentri',       'label' => 'RENTRI',          'icon' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>'],
                        ['key' => 'pagamenti',    'label' => 'Pagamenti',       'icon' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
                        ['key' => 'email',        'label' => 'Email & Notifiche','icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'],
                        ['key' => 'integrazioni', 'label' => 'Integrazioni',    'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>'],
                        ['key' => 'sistema',      'label' => 'Sistema',         'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
                    ];
                @endphp

                @foreach ($tabs as $tab)
                    <li>
                        <button
                            type="button"
                            wire:click="switchTab('{{ $tab['key'] }}')"
                            @click="mobileTab = false"
                            @class(['sh-sidenav-item', 'sh-sidenav-item--active' => $activeTab === $tab['key']])
                            aria-current="{{ $activeTab === $tab['key'] ? 'page' : 'false' }}"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $tab['icon'] !!}</svg>
                            <span>{{ $tab['label'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Main content --}}
        <main class="sh-content" aria-live="polite">

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 1: AZIENDA
            ════════════════════════════════════════════════════════════════ --}}
            @if ($activeTab === 'azienda')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Dati azienda</h2>
                        <p class="sh-section-desc">Informazioni aziendali usate nei PDF (certificati, fatture) e nelle comunicazioni.</p>
                    </div>

                    <form wire:submit="saveAzienda" class="sh-form">
                        {{-- Logo --}}
                        <div class="sh-card sh-card--logo">
                            <span class="sh-field-label">Logo aziendale</span>
                            <div class="sh-logo-area">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo" class="sh-logo-preview">
                                    <button type="button" wire:click="removeLogo" class="sh-btn sh-btn--ghost sh-btn--sm">Rimuovi</button>
                                @else
                                    <div class="sh-logo-placeholder">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        <span>Nessun logo</span>
                                    </div>
                                @endif
                                <div>
                                    <input type="file" wire:model="company_logo" accept="image/*" class="sh-file-input" id="logo-upload">
                                    <label for="logo-upload" class="sh-btn sh-btn--secondary sh-btn--sm">
                                        {{ $logoUrl ? 'Cambia logo' : 'Carica logo' }}
                                    </label>
                                    <p class="sh-field-hint">JPG, PNG, WebP o SVG. Max 2 MB.</p>
                                    @error('company_logo') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Fields --}}
                        <div class="sh-card">
                            <div class="sh-grid sh-grid--2">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="rag-soc">Ragione Sociale</label>
                                    <input id="rag-soc" type="text" wire:model="company_ragione_sociale" class="sh-input" placeholder="Nome impresa S.r.l.">
                                    @error('company_ragione_sociale') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="piva">Partita IVA</label>
                                    <input id="piva" type="text" wire:model="company_piva" class="sh-input" placeholder="IT12345678901">
                                    @error('company_piva') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="codfis">Codice Fiscale</label>
                                    <input id="codfis" type="text" wire:model="company_cf" class="sh-input" placeholder="RSSMRA80A01H501U">
                                    @error('company_cf') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="num-albo">N° iscrizione Albo Gestori Ambientali</label>
                                    <input id="num-albo" type="text" wire:model="company_num_albo" class="sh-input" placeholder="AB/123456">
                                    @error('company_num_albo') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="sh-card">
                            <h3 class="sh-card-title">Sede legale</h3>
                            <div class="sh-grid sh-grid--full">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="indirizzo">Indirizzo</label>
                                    <input id="indirizzo" type="text" wire:model="company_indirizzo" class="sh-input" placeholder="Via Roma 1">
                                    @error('company_indirizzo') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="sh-grid sh-grid--3">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="cap">CAP</label>
                                    <input id="cap" type="text" wire:model="company_cap" class="sh-input" placeholder="20100" maxlength="10">
                                    @error('company_cap') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="citta">Città</label>
                                    <input id="citta" type="text" wire:model="company_citta" class="sh-input" placeholder="Milano">
                                    @error('company_citta') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="prov">Provincia</label>
                                    <input id="prov" type="text" wire:model="company_provincia" class="sh-input" placeholder="MI" maxlength="2">
                                    @error('company_provincia') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="sh-card">
                            <h3 class="sh-card-title">Contatti</h3>
                            <div class="sh-grid sh-grid--2">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="pec">PEC aziendale</label>
                                    <input id="pec" type="email" wire:model="company_pec" class="sh-input" placeholder="azienda@pec.it">
                                    @error('company_pec') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="email">Email ordinaria</label>
                                    <input id="email" type="email" wire:model="company_email" class="sh-input" placeholder="info@azienda.it">
                                    @error('company_email') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="codice-sdi">Codice SDI</label>
                                    <input id="codice-sdi" type="text" wire:model="company_codice_sdi" class="sh-input" placeholder="0000000" maxlength="7">
                                    @error('company_codice_sdi') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="tel">Telefono</label>
                                    <input id="tel" type="tel" wire:model="company_telefono" class="sh-input" placeholder="+39 02 1234567">
                                    @error('company_telefono') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="sh-card">
                            <h3 class="sh-card-title">Numerazione fatture</h3>
                            <div class="sh-grid sh-grid--full">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="formato-fattura">Formato numero fattura</label>
                                    <input id="formato-fattura" type="text" wire:model.live="company_formato_numerazione_fattura" class="sh-input" placeholder="FT-{YEAR}-{COUNTER:3}">
                                    @error('company_formato_numerazione_fattura') <p class="sh-field-error">{{ $message }}</p> @enderror
                                    <p class="sh-field-hint">
                                        Token disponibili: <code>{YEAR}</code>, <code>{YEAR_SHORT}</code>, <code>{MONTH}</code>, <code>{COUNTER}</code>, <code>{COUNTER:N}</code> (es. <code>{COUNTER:3}</code>).
                                    </p>
                                </div>
                                <div class="sh-field">
                                    <span class="sh-field-label">Prossimo numero</span>
                                    <p class="sh-preview-numero"><strong>{{ $this->prossimoNumeroFattura }}</strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="sh-form-footer">
                            <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled" wire:loading.class="sh-btn--loading">
                                <span wire:loading.remove>Salva dati azienda</span>
                                <span wire:loading>Salvataggio…</span>
                            </button>
                        </div>
                    </form>
                </section>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 2: RENTRI
            ════════════════════════════════════════════════════════════════ --}}
            @elseif ($activeTab === 'rentri')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Configurazione RENTRI</h2>
                        <p class="sh-section-desc">Wizard di onboarding RENTRI, certificati mTLS e xFIR, modalità live/sandbox.</p>
                    </div>

                    {{-- Status bar --}}
                    <div class="sh-card sh-card--status-bar">
                        <div class="sh-status-items">
                            <div class="sh-status-item">
                                <span class="sh-status-label">Modalità API</span>
                                @if ($isLiveRentri)
                                    <span class="sh-badge sh-badge--green">LIVE</span>
                                @else
                                    <span class="sh-badge sh-badge--yellow">Sandbox</span>
                                @endif
                            </div>

                            <div class="sh-status-item">
                                <span class="sh-status-label">Cert mTLS</span>
                                @if ($certDays === null)
                                    <span class="sh-badge sh-badge--gray">Non caricato</span>
                                @elseif ($certDays < 0)
                                    <span class="sh-badge sh-badge--red">Scaduto</span>
                                @elseif ($certDays < 30)
                                    <span class="sh-badge sh-badge--yellow">{{ $certDays }} giorni</span>
                                @else
                                    <span class="sh-badge sh-badge--green">{{ $certDays }} giorni</span>
                                @endif
                            </div>

                            <div class="sh-status-item">
                                <span class="sh-status-label">Health check</span>
                                @if ($healthOk)
                                    <span class="sh-badge sh-badge--green">OK</span>
                                @elseif ($healthStatus === 'unknown')
                                    <span class="sh-badge sh-badge--gray">—</span>
                                @else
                                    <span class="sh-badge sh-badge--red">{{ $healthStatus }}</span>
                                @endif
                                @if ($rentriSettings->last_health_check_at)
                                    <span class="sh-status-time">{{ $rentriSettings->last_health_check_at->diffForHumans() }}</span>
                                @endif
                            </div>

                            <div class="sh-status-item">
                                <span class="sh-status-label">Ambiente</span>
                                <span class="sh-badge sh-badge--blue">{{ $rentriSettings->ambiente ?? 'sandbox' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Quick actions --}}
                    <div class="sh-card">
                        <h3 class="sh-card-title">Azioni rapide</h3>
                        <div class="sh-action-row">
                            <a href="{{ Route::has('segreteria.codici-cer.index') ? route('segreteria.codici-cer.index') : '#' }}" class="sh-btn sh-btn--secondary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.85"/></svg>
                                Sincronizza CER da RENTRI
                            </a>
                            <a href="{{ Route::has('segreteria.fir.blocchi') ? route('segreteria.fir.blocchi') : '#' }}" class="sh-btn sh-btn--secondary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.85"/></svg>
                                Sincronizza Blocchi FIR
                            </a>
                        </div>
                    </div>

                    {{-- Embedded RENTRI settings wizard (iframe-like redirect) --}}
                    <div class="sh-card sh-card--inset">
                        <div class="sh-inset-header">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            <h3 class="sh-card-title" style="margin:0;">Wizard onboarding RENTRI</h3>
                        </div>
                        <p class="sh-text-muted" style="margin: 0.5rem 0 1rem;">
                            Il wizard RENTRI completo (certificati, operatore, test connessione, modalità live) è disponibile nella pagina dedicata.
                            Le modifiche vengono salvate nel database e sono riflesse nel widget di stato sopra.
                        </p>
                        <a
                            href="{{ Route::has('segreteria.impostazioni.rentri') ? route('segreteria.impostazioni.rentri') : '/segreteria/impostazioni/rentri' }}"
                            class="sh-btn sh-btn--primary"
                        >
                            Apri wizard RENTRI completo →
                        </a>
                    </div>
                </section>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 3: PAGAMENTI
            ════════════════════════════════════════════════════════════════ --}}
            @elseif ($activeTab === 'pagamenti')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Pagamenti Stripe</h2>
                        <p class="sh-section-desc">Chiavi API Stripe, modalità live/sandbox, metodi di pagamento abilitati.</p>
                    </div>

                    <form wire:submit="savePagamenti" class="sh-form">
                        {{-- Mode toggle --}}
                        <div class="sh-card">
                            <div class="sh-toggle-row">
                                <div>
                                    <span class="sh-field-label">Modalità Stripe</span>
                                    <p class="sh-field-hint">In modalità live le transazioni sono reali. Usare test mode durante sviluppo.</p>
                                </div>
                                <label class="sh-toggle" aria-label="Attiva modalità live">
                                    <input type="checkbox" wire:model="stripe_live_mode" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $stripe_live_mode ? 'Live' : 'Test' }}</span>
                                </label>
                            </div>

                            @if ($stripe_live_mode)
                                <div class="sh-alert sh-alert--warning" role="alert">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    Modalità live attiva — le transazioni saranno addebitate su carte reali.
                                </div>
                            @endif
                        </div>

                        {{-- API Keys --}}
                        <div class="sh-card">
                            <h3 class="sh-card-title">Chiavi API</h3>
                            <p class="sh-field-hint" style="margin-bottom: 1rem;">Le chiavi vengono cifrate nel database. Una volta salvate non sono più visibili per intero.</p>

                            <div class="sh-grid sh-grid--1">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="sk-key">Publishable Key (pk_…)</label>
                                    <div class="sh-secret-field">
                                        @if (! $showStripeKey)
                                            <input type="text" value="••••••••••••••••••••••••" readonly class="sh-input sh-input--masked" id="sk-key-masked">
                                            <button type="button" wire:click="$set('showStripeKey', true)" class="sh-btn sh-btn--ghost sh-btn--sm">Modifica</button>
                                        @else
                                            <input id="sk-key" type="text" wire:model="stripe_key" class="sh-input" placeholder="pk_live_..." autocomplete="off">
                                            <button type="button" wire:click="$set('showStripeKey', false)" class="sh-btn sh-btn--ghost sh-btn--sm">Annulla</button>
                                        @endif
                                    </div>
                                    @error('stripe_key') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="sh-field">
                                    <label class="sh-field-label" for="sk-secret">Secret Key (sk_…)</label>
                                    <div class="sh-secret-field">
                                        @if (! $showStripeSecret)
                                            <input type="text" value="••••••••••••••••••••••••" readonly class="sh-input sh-input--masked" id="sk-secret-masked">
                                            <button type="button" wire:click="$set('showStripeSecret', true)" class="sh-btn sh-btn--ghost sh-btn--sm">Modifica</button>
                                        @else
                                            <input id="sk-secret" type="password" wire:model="stripe_secret" class="sh-input" placeholder="sk_live_..." autocomplete="new-password">
                                            <button type="button" wire:click="$set('showStripeSecret', false)" class="sh-btn sh-btn--ghost sh-btn--sm">Annulla</button>
                                        @endif
                                    </div>
                                    @error('stripe_secret') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="sh-field">
                                    <label class="sh-field-label" for="sk-whsec">Webhook Secret (whsec_…)</label>
                                    <div class="sh-secret-field">
                                        @if (! $showStripeWebhookSecret)
                                            <input type="text" value="••••••••••••••••••••••••" readonly class="sh-input sh-input--masked">
                                            <button type="button" wire:click="$set('showStripeWebhookSecret', true)" class="sh-btn sh-btn--ghost sh-btn--sm">Modifica</button>
                                        @else
                                            <input id="sk-whsec" type="password" wire:model="stripe_webhook_secret" class="sh-input" placeholder="whsec_..." autocomplete="new-password">
                                            <button type="button" wire:click="$set('showStripeWebhookSecret', false)" class="sh-btn sh-btn--ghost sh-btn--sm">Annulla</button>
                                        @endif
                                    </div>
                                    @error('stripe_webhook_secret') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>

                                {{-- Webhook URL read-only --}}
                                <div class="sh-field">
                                    <label class="sh-field-label">Webhook URL (read-only)</label>
                                    <div class="sh-copy-field">
                                        <input type="text" value="{{ $webhookUrl }}" readonly class="sh-input sh-input--readonly" id="webhook-url">
                                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url').value)" class="sh-btn sh-btn--ghost sh-btn--sm">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                            Copia
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment methods + dispute stub --}}
                        <div class="sh-card">
                            <h3 class="sh-card-title">Metodi di pagamento</h3>
                            <div class="sh-check-list">
                                <label class="sh-check-item">
                                    <input type="checkbox" wire:model="stripe_payment_card">
                                    <span>
                                        <strong>Carta di credito/debito</strong>
                                        <span class="sh-field-hint">Visa, Mastercard, Amex</span>
                                    </span>
                                </label>
                                <label class="sh-check-item">
                                    <input type="checkbox" wire:model="stripe_payment_sepa">
                                    <span>
                                        <strong>SEPA Debit</strong>
                                        <span class="sh-field-hint">Addebito diretto su conto corrente europeo</span>
                                    </span>
                                </label>
                            </div>

                            <div class="sh-toggle-row" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--color-border-light);">
                                <div>
                                    <span class="sh-field-label">Dispute stub mode</span>
                                    <p class="sh-field-hint">In stub mode le dispute non vengono processate; utile in sviluppo.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="stripe_dispute_stub" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $stripe_dispute_stub ? 'Stub' : 'Live' }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Test connection result --}}
                        @if ($stripeTestResult !== null)
                            <div class="sh-alert {{ $stripeTestOk ? 'sh-alert--success' : 'sh-alert--danger' }}" role="{{ $stripeTestOk ? 'status' : 'alert' }}">
                                {{ $stripeTestResult }}
                            </div>
                        @endif

                        <div class="sh-form-footer">
                            <button type="button" wire:click="testStripeConnection" class="sh-btn sh-btn--secondary" wire:loading.attr="disabled">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 11.08 22 12 12 22 2 12 12 2 22 11.08"/></svg>
                                Testa connessione
                            </button>
                            <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="savePagamenti">Salva impostazioni Stripe</span>
                                <span wire:loading wire:target="savePagamenti">Salvataggio…</span>
                            </button>
                        </div>
                    </form>
                </section>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 4: EMAIL & NOTIFICHE
            ════════════════════════════════════════════════════════════════ --}}
            @elseif ($activeTab === 'email')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Email & Notifiche</h2>
                        <p class="sh-section-desc">Configurazione SMTP, preferenze di notifica per evento, invio email di test.</p>
                    </div>

                    {{-- SMTP config --}}
                    <form wire:submit="saveEmail" class="sh-form">
                        <div class="sh-card">
                            <h3 class="sh-card-title">Configurazione SMTP</h3>

                            <div class="sh-toggle-row" style="margin-bottom: 1.25rem;">
                                <div>
                                    <span class="sh-field-label">Modalità invio email</span>
                                    <p class="sh-field-hint">
                                        <strong>Stub</strong>: log interno, nessun SMTP reale. &nbsp;
                                        <strong>Live</strong>: invio effettivo via SMTP.
                                    </p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="notifications_live" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $notifications_live ? 'Live' : 'Stub' }}</span>
                                </label>
                            </div>

                            <div class="sh-grid sh-grid--2">
                                <div class="sh-field" style="grid-column: span 2">
                                    <label class="sh-field-label" for="mail-host">Host SMTP</label>
                                    <input id="mail-host" type="text" wire:model="mail_host" class="sh-input" placeholder="smtp.mailgun.org">
                                    @error('mail_host') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-port">Porta</label>
                                    <input id="mail-port" type="number" wire:model="mail_port" class="sh-input" placeholder="587">
                                    @error('mail_port') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-enc">Cifratura</label>
                                    <select id="mail-enc" wire:model="mail_encryption" class="sh-input sh-select">
                                        <option value="tls">TLS (raccomandato)</option>
                                        <option value="ssl">SSL</option>
                                        <option value="null">Nessuna</option>
                                    </select>
                                    @error('mail_encryption') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-user">Username SMTP</label>
                                    <input id="mail-user" type="text" wire:model="mail_username" class="sh-input" placeholder="apikey">
                                    @error('mail_username') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-pass">Password SMTP</label>
                                    <div class="sh-secret-field">
                                        @if (! $showMailPassword)
                                            <input type="text" value="••••••••••••••" readonly class="sh-input sh-input--masked">
                                            <button type="button" wire:click="$set('showMailPassword', true)" class="sh-btn sh-btn--ghost sh-btn--sm">Modifica</button>
                                        @else
                                            <input id="mail-pass" type="password" wire:model="mail_password" class="sh-input" placeholder="password..." autocomplete="new-password">
                                            <button type="button" wire:click="$set('showMailPassword', false)" class="sh-btn sh-btn--ghost sh-btn--sm">Annulla</button>
                                        @endif
                                    </div>
                                    @error('mail_password') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="sh-grid sh-grid--2" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border-light);">
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-from-name">Nome mittente</label>
                                    <input id="mail-from-name" type="text" wire:model="mail_from_name" class="sh-input" placeholder="ERP VFU">
                                    @error('mail_from_name') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sh-field">
                                    <label class="sh-field-label" for="mail-from-addr">Email mittente</label>
                                    <input id="mail-from-addr" type="email" wire:model="mail_from_address" class="sh-input" placeholder="noreply@azienda.it">
                                    @error('mail_from_address') <p class="sh-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="sh-form-footer">
                            <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveEmail">Salva configurazione email</span>
                                <span wire:loading wire:target="saveEmail">Salvataggio…</span>
                            </button>
                        </div>
                    </form>

                    {{-- Test email --}}
                    <div class="sh-card" style="margin-top: 1.5rem;">
                        <h3 class="sh-card-title">Invia email di test</h3>
                        <p class="sh-text-muted">Verifica configurazione SMTP o registrazione log in modalità stub.</p>
                        <div class="sh-inline-form">
                            <div class="sh-field" style="flex: 1;">
                                <label class="sh-field-label" for="test-recipient">Destinatario</label>
                                <input id="test-recipient" type="email" wire:model="testEmailRecipient" class="sh-input" placeholder="ops@example.it">
                                @error('testEmailRecipient') <p class="sh-field-error">{{ $message }}</p> @enderror
                            </div>
                            <button
                                type="button"
                                wire:click="sendTestEmail"
                                class="sh-btn sh-btn--secondary"
                                wire:loading.attr="disabled"
                                style="align-self: flex-end;"
                            >
                                <span wire:loading.remove wire:target="sendTestEmail">Invia email di test</span>
                                <span wire:loading wire:target="sendTestEmail">Invio…</span>
                            </button>
                        </div>
                        @if (! $mailPreflightOk && $notifications_live)
                            <div class="sh-alert sh-alert--warning" role="alert" style="margin-top: 1rem;">
                                SMTP non completamente configurato — verificare host, porta e credenziali.
                            </div>
                        @endif
                    </div>

                    {{-- Notification event preferences --}}
                    <form wire:submit="saveNotifiche" style="margin-top: 1.5rem;">
                        <div class="sh-card">
                            <h3 class="sh-card-title">Preferenze notifiche per evento</h3>
                            <p class="sh-text-muted">Attiva o disattiva le notifiche per tipo di evento. In modalità stub vengono registrate nel log.</p>

                            <ul class="sh-event-list">
                                @foreach ($events as $event)
                                    <li class="sh-event-item">
                                        <div>
                                            <strong>{{ $event->label() }}</strong>
                                            <span class="sh-text-muted sh-text-sm">Modulo {{ $event->module() }} · {{ $event->value }}</span>
                                        </div>
                                        <label class="sh-toggle sh-toggle--sm" aria-label="Abilita {{ $event->label() }}">
                                            <input type="checkbox" wire:model="notifToggles.{{ str_replace('.', '__', $event->value) }}" role="switch">
                                            <span class="sh-toggle-track"></span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="sh-form-footer" style="border-top: none; padding-top: 1rem;">
                                <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="saveNotifiche">Salva preferenze</span>
                                    <span wire:loading wire:target="saveNotifiche">Salvataggio…</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 5: INTEGRAZIONI
            ════════════════════════════════════════════════════════════════ --}}
            @elseif ($activeTab === 'integrazioni')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Integrazioni esterne</h2>
                        <p class="sh-section-desc">GPS tracking trasporti, MUD telematico e future integrazioni (SDI/FatturaPA, PEC).</p>
                    </div>

                    <form wire:submit="saveIntegrazioni" class="sh-form">
                        {{-- GPS --}}
                        <div class="sh-card">
                            <h3 class="sh-card-title">GPS Tracking Trasporti</h3>

                            <div class="sh-toggle-row" style="margin-bottom: 1rem;">
                                <div>
                                    <span class="sh-field-label">Stub mode GPS</span>
                                    <p class="sh-field-hint">In stub mode le posizioni GPS non vengono recuperate dal provider esterno.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="gps_stub_mode" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $gps_stub_mode ? 'Stub' : 'Live' }}</span>
                                </label>
                            </div>

                            <div class="sh-field">
                                <label class="sh-field-label" for="gps-url">Provider URL</label>
                                <input id="gps-url" type="url" wire:model="gps_provider_url" class="sh-input" placeholder="https://gps-provider.example.com/api/v1">
                                @error('gps_provider_url') <p class="sh-field-error">{{ $message }}</p> @enderror
                            </div>

                            @if ($gpsTestResult !== null)
                                <div class="sh-alert {{ $gpsTestOk ? 'sh-alert--success' : 'sh-alert--danger' }}" role="{{ $gpsTestOk ? 'status' : 'alert' }}" style="margin-top: 0.75rem;">
                                    {{ $gpsTestResult }}
                                </div>
                            @endif

                            <button type="button" wire:click="testGpsConnection" class="sh-btn sh-btn--secondary" style="margin-top: 0.75rem;" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="testGpsConnection">Testa connessione GPS</span>
                                <span wire:loading wire:target="testGpsConnection">Test…</span>
                            </button>
                        </div>

                        {{-- MUD --}}
                        <div class="sh-card">
                            <h3 class="sh-card-title">MUD Telematico</h3>

                            <div class="sh-toggle-row" style="margin-bottom: 1rem;">
                                <div>
                                    <span class="sh-field-label">Stub mode MUD</span>
                                    <p class="sh-field-hint">In stub mode le dichiarazioni MUD non vengono trasmesse al gateway MASE.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="mud_stub_mode" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $mud_stub_mode ? 'Stub' : 'Live' }}</span>
                                </label>
                            </div>

                            <div class="sh-field">
                                <label class="sh-field-label" for="mud-endpoint">Endpoint base (lasciare vuoto per default RENTRI)</label>
                                <input id="mud-endpoint" type="url" wire:model="mud_endpoint" class="sh-input" placeholder="https://demoapi.rentri.gov.it">
                                @error('mud_endpoint') <p class="sh-field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Shop pubblico --}}
                        <div class="sh-card">
                            <h3 class="sh-card-title">Shop pubblico ricambi</h3>

                            <div class="sh-toggle-row">
                                <div>
                                    <span class="sh-field-label">Shop abilitato</span>
                                    <p class="sh-field-hint">Espone il catalogo ricambi su <code>/shop</code> (checkout guest). Disabilitato di default.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="shop_enabled" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $shop_enabled ? 'Attivo' : 'Off' }}</span>
                                </label>
                            </div>
                        </div>

                        {{-- Future integrations placeholder --}}
                        <div class="sh-card sh-card--future">
                            <h3 class="sh-card-title" style="color: var(--color-text-secondary);">Integrazioni future</h3>
                            <div class="sh-future-list">
                                <div class="sh-future-item">
                                    <span class="sh-badge sh-badge--gray">Presto</span>
                                    <div>
                                        <strong>SDI / FatturaPA</strong>
                                        <p class="sh-field-hint">Invio fatture elettroniche al Sistema di Interscambio.</p>
                                    </div>
                                </div>
                                <div class="sh-future-item">
                                    <span class="sh-badge sh-badge--gray">Presto</span>
                                    <div>
                                        <strong>PEC aziendale</strong>
                                        <p class="sh-field-hint">Ricezione e invio PEC direttamente dall'ERP.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sh-form-footer">
                            <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveIntegrazioni">Salva integrazioni</span>
                                <span wire:loading wire:target="saveIntegrazioni">Salvataggio…</span>
                            </button>
                        </div>
                    </form>
                </section>

            {{-- ═══════════════════════════════════════════════════════════════
                 TAB 6: SISTEMA
            ════════════════════════════════════════════════════════════════ --}}
            @elseif ($activeTab === 'sistema')
                <section class="sh-section">
                    <div class="sh-section-header">
                        <h2 class="sh-section-title">Sistema</h2>
                        <p class="sh-section-desc">Ambiente, log, cache, migrazioni e comandi artisan di manutenzione.</p>
                    </div>

                    {{-- Read-only info --}}
                    <div class="sh-card">
                        <h3 class="sh-card-title">Ambiente (read-only)</h3>
                        <div class="sh-info-grid">
                            <div class="sh-info-item">
                                <span class="sh-info-label">Ambiente app</span>
                                <span class="sh-badge {{ match($appEnv) { 'production' => 'sh-badge--green', 'staging' => 'sh-badge--yellow', default => 'sh-badge--blue' } }}">
                                    {{ $appEnv }}
                                </span>
                            </div>
                            <div class="sh-info-item">
                                <span class="sh-info-label">Queue connection</span>
                                <code class="sh-code">{{ $queueConnection }}</code>
                            </div>
                            <div class="sh-info-item">
                                <span class="sh-info-label">Queue worker</span>
                                <span class="sh-badge {{ match($queueWorkerStatus['status']) {
                                    'running' => 'sh-badge--green',
                                    'sync' => 'sh-badge--blue',
                                    'stopped' => 'sh-badge--red',
                                    default => 'sh-badge--yellow',
                                } }}">
                                    {{ $queueWorkerStatus['label'] }}
                                </span>
                                @if ($queueWorkerStatus['pending'] > 0)
                                    <span class="sh-status-time">{{ $queueWorkerStatus['pending'] }} job in coda</span>
                                @endif
                            </div>
                            <div class="sh-info-item">
                                <span class="sh-info-label">APP_DEBUG</span>
                                <span class="sh-badge {{ $appDebug ? 'sh-badge--red' : 'sh-badge--green' }}">
                                    {{ $appDebug ? 'true' : 'false' }}
                                </span>
                            </div>
                            <div class="sh-info-item">
                                <span class="sh-info-label">PHP</span>
                                <code class="sh-code">{{ PHP_VERSION }}</code>
                            </div>
                            <div class="sh-info-item">
                                <span class="sh-info-label">Laravel</span>
                                <code class="sh-code">{{ app()->version() }}</code>
                            </div>
                        </div>
                    </div>

                    {{-- Configurable --}}
                    <form wire:submit="saveSistema" class="sh-form" style="margin-top: 1.5rem;">
                        <div class="sh-card">
                            <div class="sh-toggle-row">
                                <div>
                                    <span class="sh-field-label">Modalità demo / palestra</span>
                                    <p class="sh-field-hint">Isola i dati di produzione. Utilizzare per formazione e demo live.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="demo_mode" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $demo_mode ? 'Attivo' : 'Off' }}</span>
                                </label>
                            </div>

                            <div class="sh-toggle-row" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--color-border-light);">
                                <div>
                                    <span class="sh-field-label">APP_DEBUG</span>
                                    <p class="sh-field-hint">Mostra stack trace e dettagli errore. In produzione deve essere <strong>disattivato</strong>.</p>
                                </div>
                                <label class="sh-toggle">
                                    <input type="checkbox" wire:model="app_debug" role="switch">
                                    <span class="sh-toggle-track"></span>
                                    <span class="sh-toggle-label">{{ $app_debug ? 'true' : 'false' }}</span>
                                </label>
                            </div>

                            @if ($appEnv === 'production' && $app_debug)
                                <div class="sh-alert sh-alert--warning" role="alert" style="margin-top: 1rem;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    APP_DEBUG è attivo in ambiente <strong>production</strong> — rischio esposizione dati sensibili.
                                </div>
                            @endif

                            <div class="sh-field" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--color-border-light);">
                                <label class="sh-field-label" for="log-level">Log level</label>
                                <select id="log-level" wire:model="log_level" class="sh-input sh-select">
                                    @foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level)
                                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                                    @endforeach
                                </select>
                                <p class="sh-field-hint">Produzione: <code>warning</code> o <code>error</code>. Sviluppo: <code>debug</code>.</p>
                                @error('log_level') <p class="sh-field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="sh-form-footer">
                            <button type="submit" class="sh-btn sh-btn--primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveSistema">Salva impostazioni sistema</span>
                                <span wire:loading wire:target="saveSistema">Salvataggio…</span>
                            </button>
                        </div>
                    </form>

                    {{-- Cache & Artisan --}}
                    <div class="sh-card" style="margin-top: 1.5rem;">
                        <h3 class="sh-card-title">Gestione cache</h3>
                        <div class="sh-action-row">
                            <button type="button" wire:click="clearAppCache" class="sh-btn sh-btn--secondary" wire:loading.attr="disabled" wire:loading.class="sh-btn--loading">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.85"/></svg>
                                <span wire:loading.remove wire:target="clearAppCache">Svuota cache applicazione</span>
                                <span wire:loading wire:target="clearAppCache">Pulizia…</span>
                            </button>
                            <button type="button" wire:click="clearConfigCache" class="sh-btn sh-btn--secondary" wire:loading.attr="disabled">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.85"/></svg>
                                <span wire:loading.remove wire:target="clearConfigCache">Svuota cache configurazione</span>
                                <span wire:loading wire:target="clearConfigCache">Pulizia…</span>
                            </button>
                        </div>
                    </div>

                    <div class="sh-card" style="margin-top: 1.5rem;">
                        <h3 class="sh-card-title">Comandi Artisan</h3>
                        <div class="sh-action-row sh-action-row--wrap">
                            {{-- Migrations with confirm dialog --}}
                            @if (! $showMigrazioniConfirm)
                                <button type="button" wire:click="$set('showMigrazioniConfirm', true)" class="sh-btn sh-btn--secondary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                                    Esegui migrazioni (migrate --force)
                                </button>
                            @else
                                <div class="sh-confirm-inline" role="alert">
                                    <span>Confermi l'esecuzione delle migrazioni sul database di produzione?</span>
                                    <button type="button" wire:click="runMigrations" class="sh-btn sh-btn--danger sh-btn--sm" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="runMigrations">Sì, esegui</span>
                                        <span wire:loading wire:target="runMigrations">Esecuzione…</span>
                                    </button>
                                    <button type="button" wire:click="$set('showMigrazioniConfirm', false)" class="sh-btn sh-btn--ghost sh-btn--sm">Annulla</button>
                                </div>
                            @endif

                            <button type="button" wire:click="runPreflight" class="sh-btn sh-btn--secondary" wire:loading.attr="disabled">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <span wire:loading.remove wire:target="runPreflight">Controlla preflight RENTRI</span>
                                <span wire:loading wire:target="runPreflight">Verifica…</span>
                            </button>
                        </div>
                    </div>
                </section>
            @endif

        </main>
    </div>
</div>
