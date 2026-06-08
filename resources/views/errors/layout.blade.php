<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#007AFF">
    <title>@yield('title') — {{ config('app.name', 'ERP VFU') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body style="min-height:100dvh;display:flex;align-items:center;justify-content:center;background:#F2F2F7;font-family:-apple-system,BlinkMacSystemFont,'SF Pro Text','Helvetica Neue',sans-serif;">
    <div style="text-align:center;max-width:360px;padding:24px;">
        <div style="width:64px;height:64px;border-radius:16px;background:#007AFF;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            @yield('icon')
        </div>
        <p style="font-size:13px;font-weight:600;letter-spacing:.3px;text-transform:uppercase;color:#8E8E93;margin:0 0 8px;">
            {{ config('app.name', 'ERP VFU') }}
        </p>
        <h1 style="font-size:22px;font-weight:700;letter-spacing:-.4px;margin:0 0 10px;color:#1c1c1e;">
            @yield('heading')
        </h1>
        <p style="font-size:15px;color:#636366;line-height:1.5;margin:0 0 24px;">
            @yield('message')
        </p>
        @yield('action')
    </div>
</body>
</html>
