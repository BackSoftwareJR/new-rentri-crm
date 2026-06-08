@extends('errors.layout')

@section('title', 'Accesso negato')

@section('icon')
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
@endsection

@section('heading', 'Accesso negato')

@section('message')
    Non hai i permessi necessari per visualizzare questa pagina.
@endsection

@section('action')
    <a href="{{ url('/') }}"
       style="display:inline-block;background:#007AFF;color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:16px;font-weight:600;font-family:inherit;text-decoration:none;">
        Vai alla home
    </a>
@endsection
