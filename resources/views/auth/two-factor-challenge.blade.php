@extends('layouts.guest')

@section('content')
    <div class="gest-login-main">
        <div class="gest-login-brand">
            <div class="gest-login-brand-logo" aria-hidden="true">V</div>
            <h1>ERP VFU</h1>
            <p>Verifica a due fattori</p>
        </div>

        <div class="gest-login-card">

            {{-- Tab switcher --}}
            <div id="tfa-tabs" style="display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.25rem; gap: 0;">
                <button
                    type="button"
                    id="tab-totp"
                    onclick="tfaShowTab('totp')"
                    style="padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; border: none; background: none; cursor: pointer; border-bottom: 2px solid #2563eb; margin-bottom: -2px; color: #2563eb;"
                    aria-selected="true"
                >
                    Codice authenticator
                </button>
                <button
                    type="button"
                    id="tab-recovery"
                    onclick="tfaShowTab('recovery')"
                    style="padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; border: none; background: none; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #64748b;"
                    aria-selected="false"
                >
                    Codice di recupero
                </button>
            </div>

            @if ($errors->any())
                <div class="form-feedback error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- TOTP panel --}}
            <div id="panel-totp">
                <h2 class="gest-login-title">Codice authenticator</h2>
                <p class="gest-login-lead">Inserisci il codice a 6 cifre dalla tua app authenticator.</p>

                <form method="POST" action="{{ route('two-factor.challenge.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="code">Codice TOTP</label>
                        <input
                            id="code"
                            type="text"
                            name="code"
                            class="input"
                            inputmode="numeric"
                            maxlength="6"
                            autofocus
                            autocomplete="one-time-code"
                            placeholder="000000"
                        />
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Verifica</button>
                </form>
            </div>

            {{-- Recovery code panel --}}
            <div id="panel-recovery" style="display: none;">
                <h2 class="gest-login-title">Codice di recupero</h2>
                <p class="gest-login-lead">
                    Usa uno dei tuoi codici di recupero monouso (formato <code>XXXX-XXXX-XXXX</code>).
                    Il codice viene invalidato dopo l'uso.
                </p>

                <form method="POST" action="{{ route('two-factor.challenge.store') }}">
                    @csrf

                    <div class="form-group">
                        <label for="recovery_code">Codice di recupero</label>
                        <input
                            id="recovery_code"
                            type="text"
                            name="recovery_code"
                            class="input"
                            autocomplete="off"
                            placeholder="XXXX-XXXX-XXXX"
                            style="letter-spacing: 0.05em; font-family: 'Courier New', monospace;"
                        />
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Accedi con codice di recupero</button>
                </form>
            </div>

            <p class="gest-login-footer">
                <a href="{{ route('login') }}" style="color:#64748b;">Torna al login</a>
            </p>
        </div>
    </div>

    <script>
        function tfaShowTab(tab) {
            var panels  = { totp: document.getElementById('panel-totp'),    recovery: document.getElementById('panel-recovery') };
            var tabs    = { totp: document.getElementById('tab-totp'),       recovery: document.getElementById('tab-recovery')   };
            var active  = { borderBottom: '2px solid #2563eb', color: '#2563eb' };
            var inactive= { borderBottom: '2px solid transparent', color: '#64748b' };

            Object.keys(panels).forEach(function(k) {
                panels[k].style.display = (k === tab) ? '' : 'none';
                Object.assign(tabs[k].style, (k === tab) ? active : inactive);
                tabs[k].setAttribute('aria-selected', k === tab ? 'true' : 'false');
            });

            var input = panels[tab].querySelector('input[type=text]');
            if (input) input.focus();
        }

        // Auto-switch to recovery tab if there's a recovery_code error
        @if ($errors->has('recovery_code'))
            tfaShowTab('recovery');
        @endif
    </script>
@endsection
