@extends('errors.layout')

@section('title', 'Errore del server')

@section('icon')
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
@endsection

@section('heading', 'Errore del server')

@section('message')
    Si è verificato un problema imprevisto. Il nostro team è stato avvisato. Riprova tra qualche istante.
@endsection

@section('action')
    <button type="button" onclick="window.location.reload()"
            style="background:#007AFF;color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;">
        Riprova
    </button>
@endsection
