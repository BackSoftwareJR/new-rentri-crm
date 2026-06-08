@extends('layouts.guest')

@section('content')
    <div class="gest-login-main">
        <div class="gest-login-brand">
            <div class="gest-login-brand-logo" aria-hidden="true">V</div>
            <h1>ERP VFU</h1>
            <p>Verifica a due fattori</p>
        </div>

        <div class="gest-login-card">
            <h2 class="gest-login-title">Codice authenticator</h2>
            <p class="gest-login-lead">Inserisci il codice a 6 cifre dalla tua app authenticator.</p>

            @if ($errors->any())
                <div class="form-feedback error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

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
                        required
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="000000"
                    />
                </div>

                <button type="submit" class="btn btn-primary btn-block">Verifica</button>
            </form>

            <p class="gest-login-footer">
                <a href="{{ route('login') }}" style="color:#64748b;">Torna al login</a>
            </p>
        </div>
    </div>
@endsection
