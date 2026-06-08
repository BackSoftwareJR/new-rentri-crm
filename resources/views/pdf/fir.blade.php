<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Formulario FIR — {{ $numeroFir }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.4;
            padding: 28px 36px;
        }

        .fir-header {
            border-top: 3px solid #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
            padding: 10px 0 8px;
            margin-bottom: 10px;
        }
        .fir-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .fir-subtitle {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
        }
        .fir-meta {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
        }
        .fir-meta td {
            vertical-align: top;
            padding: 2px 6px 2px 0;
            font-size: 9px;
        }
        .fir-meta .label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
        }
        .fir-meta .value {
            font-size: 10px;
            font-weight: 600;
        }
        .fir-meta .value.mono {
            font-family: DejaVu Sans Mono, monospace;
        }

        .fir-section {
            border: 1px solid #ccc;
            margin-top: 8px;
            padding: 6px 8px 7px;
            page-break-inside: avoid;
        }
        .fir-section-title {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #333;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ddd;
        }

        .fir-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .fir-grid td {
            width: 50%;
            vertical-align: top;
            padding: 2px 8px 6px 0;
        }
        .fir-grid.full td { width: 100%; }
        .field-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
            margin-bottom: 1px;
        }
        .field-value {
            font-size: 10px;
            border-bottom: 1px solid #bbb;
            min-height: 14px;
            padding-bottom: 1px;
        }
        .field-value.em { font-weight: 600; }
        .field-value.mono { font-family: DejaVu Sans Mono, monospace; }

        .fir-firma-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .fir-firma-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 6px 0 0;
        }
        .fir-firma-box {
            border: 1px dashed #aaa;
            min-height: 56px;
            padding: 4px 6px;
            font-size: 7px;
            color: #555;
        }
        .fir-firma-label {
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #555;
            margin-bottom: 3px;
        }

        .qr-placeholder {
            border: 1px dashed #888;
            width: 90px;
            height: 90px;
            text-align: center;
            font-size: 7px;
            color: #666;
            padding-top: 34px;
            margin-top: 4px;
        }

        .fir-footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            font-size: 7px;
            color: #666;
        }
        .fir-footer table { width: 100%; }
        .fir-footer td { vertical-align: bottom; }
        .fir-footer .right { text-align: right; }
    </style>
</head>
<body>

    <header class="fir-header">
        <div class="fir-title">Formulario di identificazione rifiuti (FIR)</div>
        <div class="fir-subtitle">Ai sensi del D.M. 1 aprile 1998, n. 145 e successive modificazioni — RENTRI</div>
        <table class="fir-meta">
            <tr>
                <td style="width: 33%;">
                    <div class="label">Numero FIR</div>
                    <div class="value mono">{{ $numeroFir }}</div>
                </td>
                <td style="width: 33%;">
                    <div class="label">Data</div>
                    <div class="value">{{ $dataFir }}</div>
                </td>
                <td style="width: 34%;">
                    <div class="label">Progressivo</div>
                    <div class="value mono">{{ str_pad((string) $progressivo, 4, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
        </table>
    </header>

    <section class="fir-section">
        <h2 class="fir-section-title">1. Produttore / Detentore</h2>
        <table class="fir-grid">
            <tr>
                <td>
                    <div class="field-label">Ragione sociale / Denominazione</div>
                    <div class="field-value em">{{ $produttoreNome }}</div>
                </td>
                <td>
                    <div class="field-label">N. iscrizione Albo Gestori Amb.</div>
                    <div class="field-value mono">{{ $produttoreAlbo }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-label">Codice fiscale</div>
                    <div class="field-value mono">{{ $produttoreCf }}</div>
                </td>
                <td>
                    <div class="field-label">Indirizzo sede</div>
                    <div class="field-value">{{ $produttoreIndirizzo }}</div>
                </td>
            </tr>
        </table>
    </section>

    <section class="fir-section">
        <h2 class="fir-section-title">2. Trasportatore</h2>
        <table class="fir-grid">
            <tr>
                <td>
                    <div class="field-label">Ragione sociale</div>
                    <div class="field-value em">{{ $trasportatoreNome }}</div>
                </td>
                <td>
                    <div class="field-label">N. iscrizione Albo Trasportatori</div>
                    <div class="field-value mono">{{ $trasportatoreAlbo }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="field-label">Targa veicolo</div>
                    <div class="field-value mono em">{{ strtoupper($targaVeicolo) }}</div>
                </td>
            </tr>
        </table>
    </section>

    <section class="fir-section">
        <h2 class="fir-section-title">3. Destinatario</h2>
        <table class="fir-grid">
            <tr>
                <td>
                    <div class="field-label">Ragione sociale</div>
                    <div class="field-value em">{{ $destinatarioNome }}</div>
                </td>
                <td>
                    <div class="field-label">N. iscrizione Albo / Autorizzazione</div>
                    <div class="field-value mono">{{ $destinatarioAlbo }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="field-label">Indirizzo impianto di destinazione</div>
                    <div class="field-value">{{ $destinatarioIndirizzo }}</div>
                </td>
            </tr>
        </table>
    </section>

    <section class="fir-section">
        <h2 class="fir-section-title">4. Descrizione del rifiuto</h2>
        <table class="fir-grid">
            <tr>
                <td>
                    <div class="field-label">Codice CER</div>
                    <div class="field-value mono em">{{ $cerCodice }}</div>
                </td>
                <td>
                    <div class="field-label">Stato fisico</div>
                    <div class="field-value">{{ $statoFisico }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="field-label">Denominazione</div>
                    <div class="field-value">{{ $cerDescrizione }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-label">Quantità prevista (kg)</div>
                    <div class="field-value em">{{ $quantitaKg }} kg</div>
                </td>
                <td>
                    <div class="field-label">Data inizio trasporto</div>
                    <div class="field-value em">{{ $dataInizioTrasporto }}</div>
                </td>
            </tr>
        </table>
    </section>

    <section class="fir-section">
        <h2 class="fir-section-title">5. Vidimazione RENTRI</h2>
        <table class="fir-grid">
            <tr>
                <td>
                    <div class="field-label">Numero vidimazione / Protocollo</div>
                    <div class="field-value mono em">{{ $vidimazioneNumero }}</div>
                </td>
                <td>
                    <div class="field-label">Data vidimazione</div>
                    <div class="field-value em">{{ $vidimazioneData }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="field-label">N. iscrizione sito RENTRI</div>
                    <div class="field-value mono">{{ $numIscrSito }}</div>
                </td>
                <td>
                    <div class="field-label">Codice QR xFIR</div>
                    <div class="qr-placeholder">
                        @if ($qrPayload)
                            <span style="font-size:6px;word-break:break-all;">{{ \Illuminate\Support\Str::limit((string) $qrPayload, 80) }}</span>
                        @else
                            Area QR xFIR
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="fir-section">
        <h2 class="fir-section-title">6. Firme</h2>
        <table class="fir-firma-grid">
            <tr>
                <td>
                    <div class="fir-firma-label">Firma del produttore / detentore</div>
                    <div class="fir-firma-box">&nbsp;</div>
                    <div style="font-size:8px;margin-top:4px;">Data: _____ / _____ / _________</div>
                </td>
                <td>
                    <div class="fir-firma-label">Firma del trasportatore</div>
                    <div class="fir-firma-box">&nbsp;</div>
                    <div style="font-size:8px;margin-top:4px;">Data: _____ / _____ / _________</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top:8px;">
                    <div class="fir-firma-label">Firma del destinatario (all'arrivo)</div>
                    <div class="fir-firma-box" style="min-height:48px;">&nbsp;</div>
                    <div style="font-size:8px;margin-top:4px;">Data: _____ / _____ / _________</div>
                </td>
            </tr>
        </table>
    </section>

    <footer class="fir-footer">
        <table>
            <tr>
                <td>{{ $produttoreNome }} — FIR {{ $numeroFir }}</td>
                <td class="right">Generato il {{ now()->format('d/m/Y H:i') }} — RENTRI CRM</td>
            </tr>
        </table>
    </footer>

</body>
</html>
