@extends('layouts.guest')

@section('content')
    <div class="gest-login-main">
        <div class="gest-login-brand">
            <div class="gest-login-brand-logo" aria-hidden="true">V</div>
            <h1>ERP VFU</h1>
            <p>Gestionale demolizioni e RENTRI</p>
        </div>

        <div class="gest-login-card">
            <h2 class="gest-login-title">Accedi</h2>
            <p class="gest-login-lead">Inserisci le credenziali per accedere al gestionale.</p>

            @if ($errors->any())
                <div class="form-feedback error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ Route::has('login') ? route('login') : url('/login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="input"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nome@azienda.it"
                    />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="input"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="display:inline-flex;align-items:center;gap:8px;font-weight:500;">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
                        Ricordami
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Accedi</button>
            </form>

            <p class="gest-login-footer">
                <span style="color:#64748b;">Sprint 0 — interfaccia CRM RENTRI</span>
            </p>
        </div>
    </div>
@endsection
