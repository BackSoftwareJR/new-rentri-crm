<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Certificato rottamazione — {{ $vfu->targa }}</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            max-width: 720px;
            margin: 2rem auto;
            padding: 0 1.5rem 2rem;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .cert-header {
            border-bottom: 2px solid #FF6B00;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        h1 { font-size: 1.5rem; margin: 0 0 0.25rem; }
        .stub {
            color: #64748b;
            font-size: 0.875rem;
            margin: 0;
        }
        .cert-meta {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 0.65rem 1rem;
            margin: 0;
        }
        .cert-meta dt { font-weight: 600; margin: 0; }
        .cert-meta dd { margin: 0; }
        .cert-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8125rem;
            color: #64748b;
        }
        @media print {
            body { margin: 0; max-width: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <header class="cert-header">
        <h1>Certificato di rottamazione</h1>
        <p class="stub no-print">Anteprima stub MVP — documento non ufficiale, senza firma digitale.</p>
    </header>

    <dl class="cert-meta">
        <dt>Targa</dt>
        <dd>{{ $vfu->targa }}</dd>
        <dt>Telaio</dt>
        <dd>{{ $vfu->telaio }}</dd>
        <dt>Marca / Modello</dt>
        <dd>{{ $vfu->marca }} {{ $vfu->modello }}</dd>
        <dt>Proprietario</dt>
        <dd>{{ $vfu->proprietario ?: '—' }}</dd>
        <dt>Stato pratica</dt>
        <dd>{{ $vfu->stato->label() }}</dd>
        <dt>Data emissione</dt>
        <dd>{{ now()->format('d/m/Y') }}</dd>
        @if ($vfu->data_accettazione)
            <dt>Data accettazione</dt>
            <dd>{{ $vfu->data_accettazione->format('d/m/Y') }}</dd>
        @endif
    </dl>

    <footer class="cert-footer">
        Generato da ERP VFU — {{ config('app.name') }}. Conservare copia cartacea solo a scopo interno.
    </footer>
</body>
</html>
