<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Pratica VFU — Consegna ad agenzia</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; color: #1a1a1a; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 1.5rem;">

    <h1 style="font-size: 1.1rem; margin: 0 0 0.25rem;">Pratica VFU trasmessa ad agenzia</h1>
    <p style="margin: 0 0 1.5rem; color: #555; font-size: 0.875rem;">
        Data invio: {{ now()->format('d/m/Y \a\l\l\e H:i') }}
    </p>

    {{-- Riepilogo veicolo --}}
    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 1.25rem;">
        <thead>
            <tr style="background: #f4f4f5;">
                <th colspan="2" style="text-align: left; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #555;">
                    Dati del veicolo
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
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Detentore / Proprietario</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $vfu->proprietario ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Data accettazione</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $vfu->data_accettazione?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Data consegna</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $vfu->data_consegna?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Stato pratica</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $vfu->stato->label() }}</td>
            </tr>
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">CER destinazione</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-family: monospace;">16 01 04* — Veicoli fuori uso</td>
            </tr>
        </tbody>
    </table>

    {{-- Agenzia destinataria --}}
    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 1.5rem;">
        <thead>
            <tr style="background: #f4f4f5;">
                <th colspan="2" style="text-align: left; padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #555;">
                    Agenzia destinataria
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600; width: 40%;">Ragione Sociale</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $agenzia->ragione_sociale }}</td>
            </tr>
            @if($agenzia->piva)
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">P.IVA</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-family: monospace;">{{ $agenzia->piva }}</td>
            </tr>
            @endif
            @if($agenzia->email)
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Email</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $agenzia->email }}</td>
            </tr>
            @endif
            @if($agenzia->telefono)
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Telefono</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $agenzia->telefono }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <p style="font-size: 0.875rem; color: #374151;">
        In allegato il <strong>certificato di rottamazione</strong> in formato PDF.
        Si prega di conservarne copia ai fini della pratica.
    </p>

    <p style="color: #6b7280; font-size: 0.8rem; border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 1.5rem;">
        {{ config('app.name') }} — notifica automatica modulo VFU.
    </p>

</body>
</html>
