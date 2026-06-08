<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Bonifica pericolosi completata</title>
</head>
<body style="font-family: system-ui, sans-serif; color: #1a1a1a; line-height: 1.5;">
    <h1 style="font-size: 1.125rem;">Bonifica pericolosi completata</h1>
    <p>Notifica stub MVP — nessun allegato PDF.</p>

    <ul>
        <li><strong>Targa:</strong> {{ $vfu->targa }}</li>
        <li><strong>Veicolo:</strong> {{ $vfu->marca }} {{ $vfu->modello }}</li>
        <li><strong>Telaio:</strong> {{ $vfu->telaio }}</li>
        <li><strong>Data accettazione:</strong> {{ $vfu->data_accettazione?->format('d/m/Y') ?? '—' }}</li>
        <li><strong>Scadenza pericolosi:</strong> {{ $deadline?->format('d/m/Y') ?? '—' }}</li>
        <li><strong>Completata entro scadenza:</strong> {{ $withinDeadline ? 'Sì' : 'No' }}</li>
        <li><strong>Completata il:</strong> {{ $vfu->bonifica_pericolosi_completata_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</li>
    </ul>

    <p style="color: #666; font-size: 0.875rem;">RENTRI CRM — notifica automatica fase pericolosi.</p>
</body>
</html>
