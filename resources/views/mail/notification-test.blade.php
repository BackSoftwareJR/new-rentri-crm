<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Test notifiche</title>
</head>
<body>
    <p>Email di test inviata dal hub notifiche {{ config('app.name') }}.</p>
    <p>Richiesta da: {{ $sentBy }}</p>
    <p>Modalità: {{ app(\App\Domain\Notifications\MailTransportRuntimeService::class)->modeDisplayLabel() }}</p>
</body>
</html>
