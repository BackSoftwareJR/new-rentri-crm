<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Certificato di rottamazione — {{ $vfu->targa }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            max-width: 740px;
            margin: 2rem auto;
            padding: 0 1.75rem 2.5rem;
            color: #111;
            line-height: 1.45;
            font-size: 0.875rem;
        }

        /* BOZZA watermark */
        @if($isBozza)
        body::before {
            content: 'BOZZA';
            position: fixed;
            top: 38%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-40deg);
            font-size: 7rem;
            font-weight: 900;
            color: rgba(0,0,0,0.07);
            pointer-events: none;
            z-index: 0;
            letter-spacing: 0.15em;
            white-space: nowrap;
        }
        @endif

        .cert-wrap { position: relative; z-index: 1; }

        /* Header */
        .cert-header {
            border-top: 3px solid #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
            padding: 0.75rem 0;
            margin-bottom: 0.1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .cert-title {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 0.2rem;
        }
        .cert-subtitle {
            font-size: 0.75rem;
            color: #555;
            margin: 0;
        }
        .cert-num {
            text-align: right;
            font-size: 0.8rem;
        }
        .cert-num strong { display: block; font-size: 0.95rem; }

        /* Sections */
        .cert-section {
            border: 1px solid #ccc;
            margin-top: 0.65rem;
            padding: 0.5rem 0.75rem 0.6rem;
        }
        .cert-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #333;
            margin: 0 0 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid #ddd;
        }

        /* Field grid */
        .cert-fields {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.3rem 1.5rem;
        }
        .cert-fields.full { grid-template-columns: 1fr; }
        .cert-field { display: flex; flex-direction: column; }
        .cert-field-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
            margin-bottom: 0.1rem;
        }
        .cert-field-value {
            font-size: 0.875rem;
            border-bottom: 1px solid #bbb;
            min-height: 1.3rem;
            padding-bottom: 0.05rem;
        }
        .cert-field-value.mono { font-family: 'Courier New', monospace; font-size: 0.9rem; }
        .cert-field-value.em { font-weight: 600; }

        /* Dichiarazione */
        .cert-dichiarazione {
            font-size: 0.815rem;
            line-height: 1.55;
        }

        /* Firma */
        .cert-firma-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .cert-firma-box {
            border: 1px dashed #aaa;
            min-height: 80px;
            padding: 0.4rem 0.5rem;
            font-size: 0.7rem;
            color: #555;
        }
        .cert-firma-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #555;
            margin-bottom: 0.35rem;
        }
        .cert-firma-line {
            border-bottom: 1px solid #888;
            margin-top: 1.5rem;
        }
        .cert-firma-date {
            font-size: 0.8rem;
            margin-top: 0.4rem;
        }

        /* Footer */
        .cert-footer {
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #ccc;
            font-size: 0.7rem;
            color: #666;
            display: flex;
            justify-content: space-between;
        }

        /* Print */
        @media print {
            body { margin: 0; max-width: none; font-size: 0.8rem; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="cert-wrap">

    {{-- Header --}}
    <header class="cert-header">
        <div>
            <h1 class="cert-title">Certificato di rottamazione</h1>
            <p class="cert-subtitle">
                Ai sensi del D.Lgs. 24 giugno 2003, n.&nbsp;209 e successive modificazioni
            </p>
        </div>
        <div class="cert-num">
            <strong>N.&nbsp;{{ $numeroCertificato }}</strong>
            <span>Emesso il {{ now()->format('d/m/Y') }}</span>
        </div>
    </header>

    {{-- Sezione 1: Dati del detentore --}}
    <section class="cert-section">
        <h2 class="cert-section-title">1. Dati del detentore / cedente</h2>
        <div class="cert-fields">
            <div class="cert-field">
                <span class="cert-field-label">Cognome e Nome</span>
                <span class="cert-field-value em">{{ $vfu->proprietario ?: (trim(($vfu->cognome ?? '').' '.($vfu->nome ?? '')) ?: '—') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Codice Fiscale</span>
                <span class="cert-field-value mono">{{ strtoupper($vfu->codice_fiscale ?: '—') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Data di nascita</span>
                <span class="cert-field-value">{{ $vfu->data_nascita?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Luogo di nascita</span>
                <span class="cert-field-value">{{ $vfu->luogo_nascita ?: '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Indirizzo residenza</span>
                <span class="cert-field-value">{{ $vfu->indirizzo ?: '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Comune / Provincia</span>
                <span class="cert-field-value">{{ $vfu->comune ? $vfu->comune.($vfu->provincia ? ' ('.$vfu->provincia.')' : '') : '—' }}</span>
            </div>
        </div>
    </section>

    {{-- Sezione 2: Dati del veicolo --}}
    <section class="cert-section">
        <h2 class="cert-section-title">2. Dati del veicolo</h2>
        <div class="cert-fields">
            <div class="cert-field">
                <span class="cert-field-label">Targa</span>
                <span class="cert-field-value mono em">{{ strtoupper($vfu->targa) }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Numero di telaio (VIN)</span>
                <span class="cert-field-value mono">{{ strtoupper($vfu->telaio ?: '—') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Marca / Modello</span>
                <span class="cert-field-value">{{ trim(($vfu->marca ?: '') . ' ' . ($vfu->modello ?: '')) ?: '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Tipo veicolo</span>
                <span class="cert-field-value">{{ $vfu->tipo_veicolo ?: '—' }}</span>
            </div>
            @if($vfu->codice_motore)
            <div class="cert-field">
                <span class="cert-field-label">Codice motore</span>
                <span class="cert-field-value mono">{{ $vfu->codice_motore }}</span>
            </div>
            @endif
            @if($vfu->peso_kg)
            <div class="cert-field">
                <span class="cert-field-label">Peso (kg)</span>
                <span class="cert-field-value">{{ number_format((float)$vfu->peso_kg, 2, ',', '.') }}</span>
            </div>
            @endif
            <div class="cert-field">
                <span class="cert-field-label">Nazione immatricolazione</span>
                <span class="cert-field-value">{{ $vfu->nazione ?: 'Italia' }}</span>
            </div>
        </div>
    </section>

    {{-- Sezione 3: Dati dell'autodemolitore --}}
    <section class="cert-section">
        <h2 class="cert-section-title">3. Dati dell'autodemolitore</h2>
        <div class="cert-fields">
            <div class="cert-field">
                <span class="cert-field-label">Ragione Sociale</span>
                <span class="cert-field-value em">{{ $settings->ragione_sociale ?: config('app.name') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">N. Iscrizione Albo Gestori Amb.</span>
                <span class="cert-field-value mono">{{ $settings->num_iscr_sito ?: '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Codice Fiscale</span>
                <span class="cert-field-value mono">{{ strtoupper($settings->cf ?: '—') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Partita IVA</span>
                <span class="cert-field-value mono">{{ $settings->piva ?: '—' }}</span>
            </div>
        </div>
    </section>

    {{-- Sezione 4: Data e luogo di consegna --}}
    <section class="cert-section">
        <h2 class="cert-section-title">4. Data e luogo di consegna / accettazione</h2>
        <div class="cert-fields">
            <div class="cert-field">
                <span class="cert-field-label">Data consegna del veicolo</span>
                <span class="cert-field-value em">{{ $vfu->data_consegna?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Data accettazione</span>
                <span class="cert-field-value em">{{ $vfu->data_accettazione?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Luogo di consegna (sede impianto)</span>
                <span class="cert-field-value">{{ config('app.name') }}</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Stato pratica</span>
                <span class="cert-field-value">{{ $vfu->stato->label() }}</span>
            </div>
        </div>
    </section>

    {{-- Sezione 5: Dichiarazione di avvenuta accettazione --}}
    <section class="cert-section">
        <h2 class="cert-section-title">5. Dichiarazione di avvenuta accettazione</h2>
        <p class="cert-dichiarazione">
            Si dichiara che il veicolo fuori uso sopra identificato
            (targa&nbsp;<strong>{{ strtoupper($vfu->targa) }}</strong>,
            telaio&nbsp;<strong>{{ strtoupper($vfu->telaio ?: '—') }}</strong>)
            è stato consegnato dal detentore
            <strong>{{ $vfu->proprietario ?: '—' }}</strong>
            ed accettato dal presente autodemolitore per la demolizione definitiva,
            ai sensi del D.Lgs. 24 giugno 2003 n.&nbsp;209 e successive modificazioni
            (Attuazione della Direttiva 2000/53/CE sui veicoli fuori uso),
            del D.M. 460/1999 e della normativa vigente in materia.
            Il veicolo sarà avviato al recupero e/o smaltimento nel rispetto
            della gerarchia dei rifiuti di cui al D.Lgs. 152/2006.
        </p>
    </section>

    {{-- Sezione 6: Codice CER di destinazione --}}
    <section class="cert-section">
        <h2 class="cert-section-title">6. Codice CER di destinazione</h2>
        <div class="cert-fields">
            <div class="cert-field">
                <span class="cert-field-label">Codice CER</span>
                <span class="cert-field-value mono em">16 01 04*</span>
            </div>
            <div class="cert-field">
                <span class="cert-field-label">Descrizione</span>
                <span class="cert-field-value">Veicoli fuori uso (pericoloso)</span>
            </div>
        </div>
    </section>

    {{-- Sezione 7: Firma e timbro --}}
    <section class="cert-section">
        <h2 class="cert-section-title">7. Firma e timbro dell'autodemolitore</h2>
        <div class="cert-firma-grid">
            <div>
                <p class="cert-firma-label">Firma del Responsabile</p>
                <div class="cert-firma-box">&nbsp;</div>
                <p class="cert-firma-date">Data: _____ / _____ / _________</p>
            </div>
            <div>
                <p class="cert-firma-label">Timbro Aziendale</p>
                <div class="cert-firma-box">&nbsp;</div>
            </div>
        </div>
    </section>

    <footer class="cert-footer">
        <span>
            {{ $settings->ragione_sociale ?: config('app.name') }}
            @if($settings->piva) — P.IVA {{ $settings->piva }}@endif
        </span>
        <span>Generato il {{ now()->format('d/m/Y \a\l\l\e H:i') }} — RENTRI CRM</span>
    </footer>

</div>
</body>
</html>
