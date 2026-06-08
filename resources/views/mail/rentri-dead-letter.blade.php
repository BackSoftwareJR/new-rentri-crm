<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>RENTRI dead-letter</title>
</head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">Transazione RENTRI in dead-letter</h1>
    <p>Notifica stub — richiede intervento manuale.</p>

    <ul>
        <li><strong>Transazione ID:</strong> #{{ $transazioneId }}</li>
        <li><strong>Codice errore:</strong> {{ $codiceErrore ?? '—' }}</li>
        <li><strong>Errore:</strong> {{ $errore }}</li>
    </ul>

    <p style="color: #666; font-size: 0.875rem;">RENTRI CRM — hub notifiche centralizzato.</p>
</body>
</html>
