<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Fattura {{ $fattura->numero_fattura }}</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; color: #1a1a1a; line-height: 1.55; max-width: 640px; margin: 0 auto; padding: 1.5rem;">

    <h1 style="font-size: 1.1rem; margin: 0 0 0.25rem;">Fattura {{ $fattura->numero_fattura }}</h1>
    <p style="margin: 0 0 1.5rem; color: #555; font-size: 0.875rem;">
        Emessa il {{ $fattura->data_emissione?->format('d/m/Y') ?? '—' }}
    </p>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-bottom: 1.25rem;">
        <tbody>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600; width: 40%;">Cliente</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $fattura->anagrafica?->ragione_sociale ?? '—' }}</td>
            </tr>
            @if ($fattura->data_scadenza)
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Scadenza pagamento</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $fattura->data_scadenza->format('d/m/Y') }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Imponibile</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">€ {{ number_format((float) $fattura->imponibile, 2, ',', '.') }}</td>
            </tr>
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">IVA {{ $fattura->iva_percentuale }}%</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">€ {{ number_format((float) $fattura->iva_importo, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 700;">Totale</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 700;">€ {{ number_format((float) $fattura->totale, 2, ',', '.') }}</td>
            </tr>
            @if ($fattura->metodo_pagamento)
            <tr style="background: #fafafa;">
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; font-weight: 600;">Metodo pagamento</td>
                <td style="padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0;">{{ $fattura->metodo_pagamento }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <p style="font-size: 0.875rem; color: #374151; margin-bottom: 1.25rem;">
        In allegato trovi il PDF della fattura. Puoi scaricarlo anche dal pulsante qui sotto.
    </p>

    <p style="margin: 0 0 1.5rem;">
        <a href="{{ url('/segreteria/fatture/'.$fattura->id) }}"
           style="display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.875rem;">
            Scarica PDF
        </a>
    </p>

    <p style="color: #6b7280; font-size: 0.8rem; border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 1.5rem;">
        {{ config('app.name') }} — fatturazione elettronica.
    </p>

</body>
</html>
