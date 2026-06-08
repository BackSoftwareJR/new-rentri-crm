@extends('layouts.guest')

@section('content')
    <div class="gest-login-main">
        <div class="gest-login-brand">
            <div class="gest-login-brand-logo" aria-hidden="true">V</div>
            <h1>ERP VFU</h1>
            <p>Recupero password</p>
        </div>

        <div class="gest-login-card">
            <h2 class="gest-login-title">Password dimenticata</h2>
            <p class="gest-login-lead">Inserisci la tua email per ricevere il link di reset.</p>

            @if (session('status'))
                <div class="form-feedback" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="form-feedback error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="email" />
                </div>
                <button type="submit" class="seg-btn seg-btn-primary" style="width: 100%; margin-top: 1rem;">Invia link di reset</button>
            </form>

            <p style="margin-top: 1rem; text-align: center;">
                <a href="{{ route('login') }}">Torna al login</a>
            </p>
        </div>
    </div>
@endsection
