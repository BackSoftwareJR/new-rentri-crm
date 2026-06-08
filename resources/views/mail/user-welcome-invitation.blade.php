<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Benvenuto in RENTRI CRM</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a2e;
            font-size: 15px;
            line-height: 1.6;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: #1e40af;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        .header .subtitle {
            margin: 6px 0 0;
            color: #bfdbfe;
            font-size: 13px;
        }
        .body {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #111827;
        }
        .intro {
            color: #374151;
            margin-bottom: 28px;
        }
        .credentials-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px 24px;
            margin-bottom: 28px;
        }
        .credentials-box h2 {
            margin: 0 0 16px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }
        .cred-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 10px;
            gap: 8px;
        }
        .cred-row:last-child {
            margin-bottom: 0;
        }
        .cred-label {
            font-size: 13px;
            color: #6b7280;
            min-width: 100px;
            flex-shrink: 0;
        }
        .cred-value {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 14px;
            color: #1d4ed8;
            font-weight: 600;
            word-break: break-all;
        }
        .cta-wrap {
            text-align: center;
            margin-bottom: 28px;
        }
        .cta-button {
            display: inline-block;
            background: #1e40af;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .security-notice {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 28px;
            font-size: 13px;
            color: #92400e;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .security-notice .icon {
            flex-shrink: 0;
            margin-top: 1px;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            line-height: 1.5;
        }
        .footer strong {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>RENTRI CRM</h1>
            <p class="subtitle">Gestione rifiuti e formulari RENTRI</p>
        </div>

        <div class="body">
            <p class="greeting">Ciao, {{ $user->name }}!</p>
            <p class="intro">
                Il tuo account sul <strong>RENTRI CRM</strong> è stato creato da un amministratore.
                Puoi accedere subito utilizzando le credenziali temporanee riportate di seguito.
            </p>

            <div class="credentials-box">
                <h2>Le tue credenziali di accesso</h2>
                <div class="cred-row">
                    <span class="cred-label">Email</span>
                    <span class="cred-value">{{ $user->email }}</span>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Password temp.</span>
                    <span class="cred-value">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <div class="cta-wrap">
                <a href="{{ $loginUrl }}" class="cta-button">Accedi ora</a>
            </div>

            <div class="security-notice">
                <span class="icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <div>
                    <strong>Sicurezza:</strong> cambia la tua password al primo accesso.
                    La password temporanea è valida una sola volta e non deve essere condivisa.
                </div>
            </div>

            <p style="color: #6b7280; font-size: 13px; margin: 0;">
                Se non hai richiesto questo account o ritieni si tratti di un errore,
                contatta immediatamente il tuo amministratore di sistema.
            </p>
        </div>

        <div class="footer">
            <strong>RENTRI CRM</strong> — {{ config('app.name') }}<br>
            Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.
        </div>
    </div>
</body>
</html>
