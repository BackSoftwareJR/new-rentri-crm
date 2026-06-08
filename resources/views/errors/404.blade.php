@extends('errors.layout')

@section('title', 'Pagina non trovata')

@section('icon')
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        <line x1="11" y1="8" x2="11" y2="11"/>
        <line x1="11" y1="14" x2="11.01" y2="14"/>
    </svg>
@endsection

@section('heading', 'Pagina non trovata')

@section('message')
    La pagina che stai cercando non esiste o è stata spostata.
@endsection

@section('action')
    <button type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/'; }"
            style="background:#007AFF;color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;">
        Torna indietro
    </button>
@endsection
