<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; color: #1e293b; background: #f8fafc; margin: 0; padding: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 24px; max-width: 560px; margin: 0 auto; }
        .label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
        .value { font-size: 15px; margin: 2px 0 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-warn { background: #fef9c3; color: #92400e; }
        .badge-ok   { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 16px 0; }
        h2 { margin: 0 0 16px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>
            @if ($eventType === 'charge.dispute.created')
                ⚠️ Nuova dispute Stripe ricevuta
            @elseif ($eventType === 'charge.dispute.closed')
                ✅ Dispute Stripe chiusa
            @else
                ℹ️ Dispute Stripe aggiornata
            @endif
        </h2>

        <div class="label">ID dispute</div>
        <div class="value">{{ $dispute->stripe_dispute_id }}</div>

        <div class="label">Stato</div>
        <div class="value">
            <span class="badge {{ $dispute->isOpen() ? 'badge-warn' : 'badge-ok' }}">
                {{ $dispute->status }}
            </span>
        </div>

        <div class="label">Importo contestato</div>
        <div class="value">{{ number_format($dispute->amountEur(), 2, ',', '.') }} {{ strtoupper($dispute->currency) }}</div>

        @if ($dispute->reason)
            <div class="label">Motivo</div>
            <div class="value">{{ $dispute->reason }}</div>
        @endif

        @if ($dispute->evidence_due_by)
            <div class="label">Scadenza invio prove</div>
            <div class="value">
                {{ $dispute->evidence_due_by->format('d/m/Y H:i') }}
                @if ($dispute->evidenceDueSoon())
                    <span class="badge badge-warn">‼ entro 3 gg</span>
                @endif
            </div>
        @endif

        @if ($dispute->ordine)
            <hr class="divider">
            <div class="label">Ordine CRM associato</div>
            <div class="value">#{{ $dispute->ordine->id }} — {{ number_format((float) $dispute->ordine->totale, 2, ',', '.') }} €</div>
        @endif

        <hr class="divider">
        <p style="font-size: 12px; color: #64748b; margin: 0;">
            Gestire la dispute direttamente dalla
            <a href="https://dashboard.stripe.com/disputes/{{ $dispute->stripe_dispute_id }}">Dashboard Stripe</a>.
            Ricevuto: {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>
