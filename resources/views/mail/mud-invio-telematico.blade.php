<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>MUD inviato</title>
</head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">Invio telematico MUD completato (stub)</h1>
    <p>Notifica stub ministeriale — nessun invio reale.</p>

    <ul>
        <li><strong>Anno riferimento:</strong> {{ $dichiarazione->anno_riferimento }}</li>
        <li><strong>Protocollo:</strong> {{ $protocollo }}</li>
        <li><strong>Stato:</strong> {{ $dichiarazione->stato->value ?? $dichiarazione->stato }}</li>
        <li><strong>Inviata il:</strong> {{ $dichiarazione->inviata_at?->format('d/m/Y H:i') ?? '—' }}</li>
    </ul>

    <p style="color: #666; font-size: 0.875rem;">RENTRI CRM — notifica MUD centralizzata.</p>
</body>
</html>
