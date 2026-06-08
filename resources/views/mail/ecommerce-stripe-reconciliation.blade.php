<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Riconciliazione Stripe</title>
</head>
<body>
    <p>Pagamento Stripe riconciliato per ordine #{{ $ordine->id }}.</p>
    <p>Evento: {{ $webhookEvent->stripe_event_id }} ({{ $webhookEvent->event_type }})</p>
    <p>Sessione: {{ $webhookEvent->checkout_session_id }}</p>
    <p>Totale: {{ number_format((float) $ordine->totale, 2, ',', '.') }} €</p>
    <p>Ambiente: {{ $reconciliation['environment'] ?? '—' }}</p>
</body>
</html>
