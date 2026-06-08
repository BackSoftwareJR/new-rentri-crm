<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>KPI business breach</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">KPI business — sotto soglia critica</h1>
    <p>Periodo: <strong>{{ $comparison['label'] }}</strong></p>

    <ul>
        @foreach ($breaches as $breach)
            <li>
                <strong>{{ $breach['label'] }}</strong> —
                valore {{ number_format($breach['value'], 2, ',', '.') }}
                (soglia alert {{ number_format($breach['alert'], 2, ',', '.') }})
            </li>
        @endforeach
    </ul>

    <p style="color: #6b7280; font-size: 0.875rem;">
        Verifica dashboard segreteria e <code>php artisan kpi:business-check</code>.
    </p>
</body>
</html>
