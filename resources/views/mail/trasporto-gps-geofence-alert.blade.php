<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Alert geofencing GPS</title>
</head>
<body>
    <p>Il trasporto #{{ $trasporto->id }} si trova a <strong>{{ number_format($distanceKm, 1, ',', '.') }} km</strong> dalla destinazione stub (soglia {{ number_format($radiusKm, 0) }} km).</p>
    <p>Posizione: {{ $position['latitude'] }}, {{ $position['longitude'] }}</p>
    <p>Destinazione stub: {{ $destination['latitude'] }}, {{ $destination['longitude'] }}</p>
    <p>Destinatario: {{ $trasporto->destinatario?->ragione_sociale ?? '—' }}</p>
</body>
</html>
