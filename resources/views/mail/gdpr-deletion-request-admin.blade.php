<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Richiesta cancellazione GDPR</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h1 style="font-size: 18px;">Richiesta cancellazione account (GDPR)</h1>

    <p>Un utente ha richiesto la cancellazione del proprio account personale.</p>

    <table style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: 600;">Utente</td>
            <td>{{ $user->name }} &lt;{{ $user->email }}&gt;</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: 600;">ID</td>
            <td>{{ $user->id }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: 600;">Eliminazione prevista</td>
            <td>{{ $scheduledAt->format('d/m/Y H:i') }} ({{ $scheduledAt->diffForHumans() }})</td>
        </tr>
    </table>

    <p style="font-weight: 600;">Motivazione:</p>
    <p style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px;">{{ $reason }}</p>

    <p style="font-size: 13px; color: #64748b;">
        L'account è stato disattivato immediatamente. Dopo 30 giorni verrà eliminato automaticamente dal job <code>gdpr:process-deletions</code>.
    </p>
</body>
</html>
