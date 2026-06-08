<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Alert serbatoio</title>
</head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">Alert soglia serbatoio</h1>
    <p>Notifica stub — nessun SMTP reale in demo.</p>

    <ul>
        <li><strong>CER:</strong> {{ $serbatoio['codice'] ?? '—' }}</li>
        <li><strong>Stato:</strong> {{ $statoLabel }}</li>
        <li><strong>Percentuale:</strong> {{ isset($serbatoio['percentuale']) ? number_format((float) $serbatoio['percentuale'], 1, ',', '.').'%' : '—' }}</li>
        <li><strong>Giacenza:</strong> {{ isset($serbatoio['quantita_attuale_kg']) ? number_format((float) $serbatoio['quantita_attuale_kg'], 0, ',', '.').' kg' : '—' }}</li>
        <li><strong>Limite:</strong> {{ isset($serbatoio['limite_kg']) ? number_format((float) $serbatoio['limite_kg'], 0, ',', '.').' kg' : '—' }}</li>
    </ul>

    <p style="color: #666; font-size: 0.875rem;">RENTRI CRM — alert magazzino centralizzato.</p>
</body>
</html>
