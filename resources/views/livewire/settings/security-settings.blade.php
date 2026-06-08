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
        <div class="seg-card">
            <p><span class="seg-badge seg-badge-success">2FA attivo</span></p>
            <p class="seg-text-muted">Il tuo account richiede un codice TOTP ad ogni accesso.</p>

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
</div>
