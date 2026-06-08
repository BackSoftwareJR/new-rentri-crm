@extends('errors.layout')

@section('title', 'Manutenzione in corso')

@section('icon')
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
    </svg>
@endsection

@section('heading', 'Manutenzione in corso')

@section('message')
    @php
        $seconds = (int) ($retryAfter ?? 1800);
        $minutes = max(1, (int) ceil($seconds / 60));
        $timeLabel = $minutes >= 60
            ? 'circa ' . (int) ceil($minutes / 60) . ' ' . ((int) ceil($minutes / 60) === 1 ? 'ora' : 'ore')
            : 'circa ' . $minutes . ' ' . ($minutes === 1 ? 'minuto' : 'minuti');
    @endphp
    Stiamo aggiornando il sistema per offrirti un servizio migliore. Tempo stimato: <strong>{{ $timeLabel }}</strong>.
@endsection

@section('action')
    <button type="button" onclick="window.location.reload()"
            style="background:#007AFF;color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;">
        Ricontrolla stato
    </button>
@endsection
