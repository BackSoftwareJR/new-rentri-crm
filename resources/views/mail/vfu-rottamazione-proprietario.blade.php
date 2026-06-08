<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Certificato di rottamazione — {{ strtoupper($vfu->targa) }}</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; color: #1a1a1a; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 1.5rem;">

    <h1 style="font-size: 1.1rem; margin: 0 0 0.25rem;">Rottamazione veicolo completata</h1>
    <p style="margin: 0 0 1.5rem; color: #555; font-size: 0.875rem;">
        Data rottamazione: {{ $vfu->rottamato_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}
    </p>

    <p style="font-size: 0.95rem; color: #374151; margin-bottom: 1.25rem;">
        Gentile {{ $vfu->proprietario ?: 'proprietario' }},
    </p>

    <p style="font-size: 0.95rem; color: #374151; margin-bottom: 1.25rem;">
        Il tuo veicolo <strong>{{ strtoupper($vfu->targa) }}</strong>
        @if($vfu->marca || $vfu->modello)
            ({{ trim(strtoupper($vfu->marca.' '.$vfu->modello)) }})
        @endif
        è stato correttamente rottamato in data
        <strong>{{ $vfu->rottamato_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong>.
        In allegato il <strong>certificato di rottamazione</strong> in formato PDF.
    </p>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 1.5rem;">
        <thead>
            <tr style="background: #f4f4f5;">
                <th colspan="2" style="text-align: left; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #555;">
                    Riepilogo veicolo
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600; width: 40%;">Targa</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-family: monospace;">{{ strtoupper($vfu->targa) }}</td>
            </tr>
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Telaio (VIN)</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-family: monospace;">{{ strtoupper($vfu->telaio ?: '—') }}</td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Marca / Modello</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ strtoupper($vfu->marca ?: '—') }} {{ strtoupper($vfu->modello ?: '') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="font-size: 0.875rem; color: #374151;">
        Si prega di conservare il certificato allegato ai fini della pratica.
    </p>

    <p style="color: #6b7280; font-size: 0.8rem; border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 1.5rem;">
        {{ config('app.name') }} — notifica automatica modulo VFU.
    </p>

</body>
</html>
