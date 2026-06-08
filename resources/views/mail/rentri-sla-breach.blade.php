<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>RENTRI SLA breach</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">RENTRI — SLA fuori soglia</h1>
    <p>Periodo valutazione: <strong>ultimi {{ $periodDays }} giorni</strong>.</p>

    <ul>
        @foreach ($breaches as $breach)
            <li>
                <strong>{{ $breach['label'] }}</strong> ({{ strtoupper($breach['status']) }}) —
                {{ $breach['message'] }}
            </li>
        @endforeach
    </ul>

    <p style="color: #6b7280; font-size: 0.875rem;">
        Transazioni nel periodo: {{ $metrics['totale'] ?? 0 }} ·
        Dead-letter: {{ $metrics['dead_letter']['count'] ?? 0 }}
        ({{ number_format($metrics['dead_letter']['rate_percent'] ?? 0, 2, ',', '.') }}%).
    </p>

    <p style="color: #6b7280; font-size: 0.875rem;">
        Verifica hub RENTRI e <code>php artisan rentri:sla-check</code>.
    </p>
</body>
</html>
