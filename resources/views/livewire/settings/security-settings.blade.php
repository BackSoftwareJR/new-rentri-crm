<div>
    @if (session('success'))
        <div class="seg-alert seg-alert-success" role="status">{{ session('success') }}</div>
    @endif

    @php
        $enforcement = app(\App\Domain\Auth\TwoFactorEnforcementService::class);
        $user = auth()->user();
        $enforcedRole = $user && $enforcement->appliesTo($user);
    @endphp

    <div class="seg-card" style="margin-bottom: 1.5rem;">
        <h2 class="seg-card-title">Autenticazione a due fattori (TOTP)</h2>
        <p class="seg-text-muted" style="margin: 0;">
            @if ($enforcedRole && ! $enabled)
                Per gli account admin e segreteria l'attivazione 2FA è obbligatoria
                @if ($enforcement->isWithinGracePeriod())
                    (periodo di grazia fino al {{ $enforcement->graceUntilLabel() }}).
                @else
                    per accedere all'area segreteria.
                @endif
            @else
                Opt-in per admin e segreteria. Dopo l'attivazione, al login sarà richiesto un codice a 6 cifre
                dall'app authenticator (Google Authenticator, Authy, ecc.).
            @endif
        </p>
    </div>

    @if ($enabled)
        <div class="seg-card" style="margin-bottom: 1rem;">
            <p><span class="seg-badge seg-badge-success">2FA attivo</span></p>
            <p class="seg-text-muted">Il tuo account richiede un codice TOTP ad ogni accesso.</p>

            <p class="seg-text-muted" style="margin-top: 0.5rem; font-size: 0.875rem;">
                Codici di recupero disponibili: <strong>{{ $remainingCodes }}</strong> / {{ \App\Domain\Auth\TwoFactorService::RECOVERY_CODE_COUNT }}
            </p>

            <form wire:submit="disable" class="seg-form" style="margin-top: 1rem;">
                <div class="form-group">
                    <label for="disableCode">Codice per disattivare</label>
                    <input
                        id="disableCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        wire:model="disableCode"
                        class="input"
                        placeholder="000000"
                        autocomplete="one-time-code"
                    />
                    @error('disableCode')
                        <p class="form-feedback error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="seg-btn seg-btn-secondary">Disattiva 2FA</button>
            </form>
        </div>

        {{-- Regenerate recovery codes --}}
        <div class="seg-card">
            <h3 class="seg-card-title" style="font-size: 1rem;">Codici di recupero</h3>
            <p class="seg-text-muted" style="font-size: 0.875rem;">
                I codici di recupero ti permettono di accedere se perdi l'accesso all'app authenticator.
                Ogni codice è valido una sola volta.
            </p>

            @if (! $showRegenForm)
                <button
                    type="button"
                    wire:click="toggleRegenForm"
                    class="seg-btn seg-btn-secondary"
                    style="margin-top: 0.75rem;"
                >
                    Rigenera codici di recupero
                </button>
            @else
                <div
                    class="seg-card"
                    style="background: #fef3c7; border: 1px solid #f59e0b; margin-top: 1rem; padding: 1rem;"
                    role="alert"
                >
                    <p style="color: #92400e; font-weight: 600; margin: 0 0 0.5rem;">
                        ⚠ Attenzione: i vecchi codici verranno invalidati
                    </p>
                    <p style="color: #92400e; margin: 0 0 1rem; font-size: 0.875rem;">
                        Dopo la rigenerazione i codici precedenti non saranno più utilizzabili.
                        Inserisci la tua password corrente per confermare.
                    </p>

                    <form wire:submit="regenerateRecoveryCodes" class="seg-form">
                        <div class="form-group">
                            <label for="regenPassword">Password attuale</label>
                            <input
                                id="regenPassword"
                                type="password"
                                wire:model="regenPassword"
                                class="input"
                                autocomplete="current-password"
                                autofocus
                            />
                            @error('regenPassword')
                                <p class="form-feedback error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="submit" class="seg-btn seg-btn-primary">Conferma rigenerazione</button>
                            <button type="button" wire:click="toggleRegenForm" class="seg-btn seg-btn-ghost">Annulla</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

    @elseif ($setupMode)
        <div class="seg-card">
            <h3 class="seg-card-title">Configura authenticator</h3>
            <p class="seg-text-muted">Scansiona il QR con l'app authenticator (issuer: {{ $issuer }}).</p>

            @if ($qrSvg)
                <div style="margin: 1rem 0; max-width: 220px;" aria-hidden="true">
                    {!! $qrSvg !!}
                </div>
            @endif

            @if ($setupSecret)
                <p class="seg-text-muted" style="font-size: 0.875rem;">
                    Secret manuale: <code>{{ $setupSecret }}</code>
                </p>
            @endif

            <form wire:submit="confirmSetup" class="seg-form" style="margin-top: 1rem;">
                <div class="form-group">
                    <label for="confirmCode">Codice di verifica</label>
                    <input
                        id="confirmCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        wire:model="confirmCode"
                        class="input"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        autofocus
                    />
                    @error('confirmCode')
                        <p class="form-feedback error">{{ $message }}</p>
                    @enderror
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="seg-btn seg-btn-primary">Conferma attivazione</button>
                    <button type="button" wire:click="cancelSetup" class="seg-btn seg-btn-secondary">Annulla</button>
                </div>
            </form>
        </div>
    @else
        <div class="seg-card">
            <p><span class="seg-badge">2FA non attivo</span></p>
            <p class="seg-text-muted">Proteggi l'accesso con un secondo fattore TOTP.</p>
            <button type="button" wire:click="startSetup" class="seg-btn seg-btn-primary" style="margin-top: 1rem;">
                Abilita autenticazione a due fattori
            </button>
        </div>
    @endif

    {{-- Recovery codes modal (shown once after enable or regeneration) --}}
    @if ($showRecoveryModal && count($newRecoveryCodes) > 0)
        <div
            style="position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,0.6); display: flex; align-items: center; justify-content: center; padding: 1rem;"
            role="dialog"
            aria-modal="true"
            aria-labelledby="recovery-modal-title"
        >
            <div style="background: #fff; border-radius: 0.75rem; box-shadow: 0 25px 50px rgba(0,0,0,0.25); max-width: 520px; width: 100%; padding: 1.75rem;">
                <h2 id="recovery-modal-title" style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.25rem; color: #0f172a;">
                    Codici di recupero
                </h2>

                <div
                    style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem;"
                    role="alert"
                >
                    <p style="color: #991b1b; font-weight: 600; margin: 0 0 0.25rem; font-size: 0.875rem;">
                        ⚠ Questi codici non verranno mostrati di nuovo
                    </p>
                    <p style="color: #7f1d1d; margin: 0; font-size: 0.8125rem; line-height: 1.5;">
                        Salvali in un gestore di password o in un luogo sicuro. Usali se perdi l'accesso all'app authenticator.
                    </p>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; font-family: 'Courier New', monospace;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        @foreach ($newRecoveryCodes as $code)
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 0.375rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 600; color: #0f172a; letter-spacing: 0.05em; text-align: center;">
                                {{ $code }}
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Download button --}}
                <a
                    href="data:text/plain;charset=utf-8,{{ urlencode("Codici di recupero 2FA — ERP VFU\nGenerati il: " . now()->format('d/m/Y H:i') . "\n\nConserva questi codici in un luogo sicuro.\nOgni codice è valido una sola volta.\n\n" . implode("\n", $newRecoveryCodes)) }}"
                    download="codici-recupero-2fa.txt"
                    class="seg-btn seg-btn-secondary"
                    style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem;"
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Scarica come .txt
                </a>

                <label style="display: flex; align-items: flex-start; gap: 0.625rem; cursor: pointer; margin-bottom: 1.25rem;">
                    <input
                        type="checkbox"
                        wire:model="recoveryCodesAcknowledged"
                        style="margin-top: 0.2rem; width: 1rem; height: 1rem; flex-shrink: 0;"
                    />
                    <span style="font-size: 0.875rem; color: #374151; line-height: 1.5;">
                        Ho salvato i codici di recupero in un luogo sicuro e capisco che non verranno mostrati di nuovo.
                    </span>
                </label>

                @error('recoveryCodesAcknowledged')
                    <p class="form-feedback error" style="margin-bottom: 0.75rem;">{{ $message }}</p>
                @enderror

                <button
                    type="button"
                    wire:click="acknowledgeRecoveryCodes"
                    class="seg-btn seg-btn-primary"
                    style="width: 100%;"
                >
                    Ho salvato i codici, chiudi
                </button>
            </div>
        </div>
    @endif

    {{-- Password change --}}
    <div class="seg-card" style="margin-bottom: 1.5rem;">
        <h2 class="seg-card-title">Password</h2>
        <p class="seg-text-muted" style="margin-bottom: 1rem;">Cambia la password di accesso al gestionale.</p>

        <form wire:submit="changePassword" class="seg-form">
            <div class="form-group">
                <label for="currentPassword">Password attuale</label>
                <input id="currentPassword" type="password" wire:model="currentPassword" class="input" autocomplete="current-password" />
                @error('currentPassword') <p class="form-feedback error">{{ $message }}</p> @enderror
            </div>

            <x-password-strength-indicator>
                <div class="form-group">
                    <label for="newPassword">Nuova password</label>
                    <input id="newPassword" type="password" wire:model="newPassword" x-model="pwd" class="input" autocomplete="new-password" />
                    @error('newPassword') <p class="form-feedback error">{{ $message }}</p> @enderror
                </div>
            </x-password-strength-indicator>

            <div class="form-group">
                <label for="newPasswordConfirmation">Conferma nuova password</label>
                <input id="newPasswordConfirmation" type="password" wire:model="newPasswordConfirmation" class="input" autocomplete="new-password" />
                @error('newPasswordConfirmation') <p class="form-feedback error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled" wire:target="changePassword">
                Aggiorna password
            </button>
        </form>
    </div>

    {{-- GDPR --}}
    <div class="seg-card" style="margin-bottom: 1.5rem;">
        <h2 class="seg-card-title">Privacy e dati personali (GDPR)</h2>
        <p class="seg-text-muted" style="margin-bottom: 1rem;">
            Ai sensi del Regolamento UE 2016/679 e del D.lgs. 196/2003 puoi esportare i tuoi dati o richiedere la cancellazione dell'account.
        </p>

        @if ($deletionPending)
            <div class="seg-alert seg-alert-warning" role="status">
                Richiesta di cancellazione inviata.
                @if ($deletionScheduledAt)
                    Eliminazione prevista il {{ $deletionScheduledAt->format('d/m/Y H:i') }}.
                @endif
            </div>
        @else
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" wire:click="downloadMyData" class="seg-btn seg-btn-secondary" wire:loading.attr="disabled" wire:target="downloadMyData">
                    Scarica i miei dati
                </button>
                <button type="button" wire:click="openDeletionModal" class="seg-btn seg-btn-secondary">
                    Richiedi cancellazione account
                </button>
            </div>
        @endif
    </div>

    @if ($showDeletionModal)
        <div
            style="position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,0.6); display: flex; align-items: center; justify-content: center; padding: 1rem;"
            role="dialog"
            aria-modal="true"
            aria-labelledby="deletion-modal-title"
        >
            <div style="background: #fff; border-radius: 0.75rem; max-width: 520px; width: 100%; padding: 1.75rem;">
                <h2 id="deletion-modal-title" style="font-size: 1.25rem; font-weight: 700; margin: 0 0 1rem;">Conferma cancellazione account</h2>

                <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 1rem;">
                    L'account verrà disattivato immediatamente e eliminato definitivamente dopo 30 giorni.
                    Questa azione non può essere annullata dall'utente.
                </p>

                <form wire:submit="requestAccountDeletion" class="seg-form">
                    <div class="form-group">
                        <label for="deletionReason">Motivo della richiesta</label>
                        <textarea id="deletionReason" wire:model="deletionReason" class="input" rows="3" placeholder="Descrivi il motivo…"></textarea>
                        @error('deletionReason') <p class="form-feedback error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="deletionConfirmText">Digita ELIMINA per confermare</label>
                        <input id="deletionConfirmText" type="text" wire:model="deletionConfirmText" class="input" autocomplete="off" />
                        @error('deletionConfirmText') <p class="form-feedback error">{{ $message }}</p> @enderror
                    </div>

                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" wire:click="closeDeletionModal" class="seg-btn seg-btn-ghost">Annulla</button>
                        <button type="submit" class="seg-btn seg-btn-primary" style="background: #dc2626; border-color: #dc2626;">
                            Conferma cancellazione
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
