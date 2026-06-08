@extends('layouts.guest')

@section('content')
    <div class="gest-login-main">
        <div class="gest-login-brand">
            <div class="gest-login-brand-logo" aria-hidden="true">V</div>
            <h1>ERP VFU</h1>
            <p>Nuova password</p>
        </div>

        <div class="gest-login-card">
            <h2 class="gest-login-title">Reimposta password</h2>
            <p class="gest-login-lead">Scegli una password sicura per il tuo account.</p>

            @if ($errors->any())
                <div class="form-feedback error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" x-data="{ pwd: '' }">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" class="input" value="{{ old('email', $email) }}" required autocomplete="email" />
                </div>

                <div class="form-group">
                    <label for="password">Nuova password</label>
                    <input id="password" type="password" name="password" class="input" x-model="pwd" required autocomplete="new-password" />
                </div>

                <div style="margin-bottom: 1rem;" aria-live="polite">
                    <div style="display: flex; gap: 4px; margin-bottom: 6px;">
                        <template x-for="i in 4" :key="i">
                            <div :style="'flex:1;height:4px;border-radius:2px;background:' + ((pwd.length >= 8 ? 1 : 0) + (/[A-Z]/.test(pwd) ? 1 : 0) + (/[0-9]/.test(pwd) ? 1 : 0) + (/[^A-Za-z0-9]/.test(pwd) ? 1 : 0) >= i ? '#16a34a' : '#e2e8f0')"></div>
                        </template>
                    </div>
                    <ul style="margin: 0; padding: 0; list-style: none; font-size: 12px; color: #64748b;">
                        <li :style="pwd.length >= 8 ? 'color:#16a34a' : ''">Almeno 8 caratteri</li>
                        <li :style="/[A-Z]/.test(pwd) ? 'color:#16a34a' : ''">Una lettera maiuscola</li>
                        <li :style="/[0-9]/.test(pwd) ? 'color:#16a34a' : ''">Un numero</li>
                        <li :style="/[^A-Za-z0-9]/.test(pwd) ? 'color:#16a34a' : ''">Un carattere speciale</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Conferma password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password" />
                </div>

                <button type="submit" class="seg-btn seg-btn-primary" style="width: 100%; margin-top: 1rem;">Salva password</button>
            </form>

            <p style="margin-top: 1rem; text-align: center;">
                <a href="{{ route('login') }}">Torna al login</a>
            </p>
        </div>
    </div>
@endsection
