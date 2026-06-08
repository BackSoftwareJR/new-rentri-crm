<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#007AFF">
    <title>Offline — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body style="min-height:100dvh;display:flex;align-items:center;justify-content:center;background:#F2F2F7;font-family:-apple-system,BlinkMacSystemFont,'SF Pro Text','Helvetica Neue',sans-serif;">
    <div style="text-align:center;max-width:320px;padding:24px;">
        <div style="width:64px;height:64px;border-radius:16px;background:#007AFF;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"/>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0 1 22.56 9"/>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </div>
        <h1 style="font-size:22px;font-weight:700;letter-spacing:-.4px;margin:0 0 10px;color:#1c1c1e;">Sei offline</h1>
        <p style="font-size:15px;color:#636366;line-height:1.5;margin:0 0 24px;">
            Questa pagina non è disponibile offline. Verifica la connessione e riprova.
        </p>
        <button onclick="window.location.reload()"
                style="background:#007AFF;color:#fff;border:none;border-radius:12px;padding:13px 24px;font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;">
            Riprova
        </button>
    </div>
    <script>
        window.addEventListener('online', function () { window.location.reload(); });
    </script>
</body>
</html>
