<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $fattura->numero_fattura }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; background: #fff; padding: 32px 40px; }

    .header { display: flex; justify-content: space-between; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #111; }
    .header-left { display: flex; gap: 14px; align-items: flex-start; }
    .company-logo { max-height: 56px; max-width: 120px; object-fit: contain; }
    .company-name { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
    .company-sub { font-size: 10px; color: #6b7280; margin-top: 2px; line-height: 1.5; }
    .fattura-title { text-align: right; }
    .fattura-title h1 { font-size: 22px; font-weight: 700; color: #111; }
    .fattura-title .numero { font-size: 13px; color: #6b7280; margin-top: 2px; font-family: monospace; }

    .meta-grid { display: table; width: 100%; margin-bottom: 28px; }
    .meta-col { display: table-cell; width: 50%; vertical-align: top; }
    .meta-col:last-child { text-align: right; }
    .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; margin-bottom: 2px; }
    .meta-value { font-size: 12px; font-weight: 600; }
    .meta-block { margin-bottom: 14px; }

    .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }

    table.righe { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.righe th { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    table.righe th.num { text-align: right; }
    table.righe td { padding: 8px; font-size: 11px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    table.righe td.num { text-align: right; }
    table.righe tr:last-child td { border-bottom: none; }

    .totali { float: right; width: 260px; margin-top: 8px; }
    .totali-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; }
    .totali-row.total { font-size: 15px; font-weight: 700; border-top: 2px solid #111; padding-top: 8px; margin-top: 4px; }
    .totali-label { color: #6b7280; }

    .clearfix::after { content: ''; display: table; clear: both; }

    .note-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 12px; margin-top: 24px; font-size: 11px; }

    .footer { position: fixed; bottom: 24px; left: 40px; right: 40px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 9px; color: #9ca3af; display: flex; justify-content: space-between; }

    .stato-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
</style>
</head>
<body>

{{-- Company header --}}
<div class="header">
    <div class="header-left">
        @if (!empty($logoAbsolutePath))
            <img src="{{ $logoAbsolutePath }}" alt="Logo" class="company-logo">
        @endif
        <div>
            <div class="company-name">{{ $azienda->ragione_sociale ?: config('app.name', 'Autodemolitore') }}</div>
            <div class="company-sub">
                @if ($azienda->indirizzo)
                    {{ $azienda->indirizzo }}@if($azienda->cap || $azienda->comune), {{ $azienda->cap }} {{ $azienda->comune }}@if($azienda->provincia) ({{ $azienda->provincia }})@endif @endif<br>
                @endif
                @if ($azienda->piva)
                    P.IVA {{ $azienda->piva }}@if($azienda->codice_fiscale && $azienda->codice_fiscale !== $azienda->piva) · C.F. {{ $azienda->codice_fiscale }}@endif<br>
                @elseif ($azienda->codice_fiscale)
                    C.F. {{ $azienda->codice_fiscale }}<br>
                @endif
                @if ($azienda->codice_sdi)
                    Codice SDI {{ $azienda->codice_sdi }}@if($azienda->pec) · PEC {{ $azienda->pec }}@endif<br>
                @elseif ($azienda->pec)
                    PEC {{ $azienda->pec }}<br>
                @endif
                @if ($azienda->telefono)
                    Tel. {{ $azienda->telefono }}
                @endif
                @if ($azienda->albo_numero)
                    @if ($azienda->telefono)<br>@endif
                    Albo Gestori Amb. n. {{ $azienda->albo_numero }}
                @endif
            </div>
        </div>
    </div>
    <div class="fattura-title">
        <h1>{{ $fattura->tipoLabel() }}</h1>
        <div class="numero">{{ $fattura->numero_fattura }}</div>
        <div style="margin-top:6px;">
            <span class="stato-badge"
                  style="background:{{ $fattura->statoColor() }}22;color:{{ $fattura->statoColor() }};">
                {{ $fattura->statoLabel() }}
            </span>
        </div>
    </div>
</div>

{{-- Meta --}}
<div class="meta-grid">
    <div class="meta-col">
        <p class="section-title">Destinatario</p>
        <div class="meta-block">
            <div class="meta-label">Ragione sociale</div>
            <div class="meta-value">{{ $fattura->anagrafica?->ragione_sociale ?? '—' }}</div>
        </div>
        @if ($fattura->anagrafica?->piva)
        <div class="meta-block">
            <div class="meta-label">P.IVA</div>
            <div class="meta-value">{{ $fattura->anagrafica->piva }}</div>
        </div>
        @endif
        @if ($fattura->anagrafica?->indirizzo)
        <div class="meta-block">
            <div class="meta-label">Indirizzo</div>
            <div class="meta-value">{{ $fattura->anagrafica->indirizzo }},
                {{ $fattura->anagrafica->cap }} {{ $fattura->anagrafica->citta }} ({{ $fattura->anagrafica->provincia }})
            </div>
        </div>
        @endif
        @if ($fattura->anagrafica?->pec)
        <div class="meta-block">
            <div class="meta-label">PEC / SDI</div>
            <div class="meta-value">{{ $fattura->anagrafica->pec }}
                @if($fattura->anagrafica->codice_sdi) · {{ $fattura->anagrafica->codice_sdi }} @endif
            </div>
        </div>
        @endif
    </div>
    <div class="meta-col">
        <p class="section-title" style="text-align:right;">Dettaglio fattura</p>
        <div class="meta-block">
            <div class="meta-label">Data emissione</div>
            <div class="meta-value">{{ $fattura->data_emissione?->format('d/m/Y') }}</div>
        </div>
        @if ($fattura->data_scadenza)
        <div class="meta-block">
            <div class="meta-label">Scadenza</div>
            <div class="meta-value">{{ $fattura->data_scadenza->format('d/m/Y') }}</div>
        </div>
        @endif
        @if ($fattura->metodo_pagamento)
        <div class="meta-block">
            <div class="meta-label">Metodo pagamento</div>
            <div class="meta-value">{{ $fattura->metodo_pagamento }}</div>
        </div>
        @endif
        @if ($fattura->vfu)
        <div class="meta-block">
            <div class="meta-label">Riferimento VFU</div>
            <div class="meta-value">{{ $fattura->vfu->targa }} – {{ $fattura->vfu->marca }} {{ $fattura->vfu->modello }}</div>
        </div>
        @endif
    </div>
</div>

{{-- Line items --}}
<p class="section-title">Voci</p>
<table class="righe">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:45%;">Descrizione</th>
            <th class="num" style="width:10%;">Qtà</th>
            <th class="num" style="width:15%;">Prezzo unit.</th>
            <th class="num" style="width:10%;">IVA %</th>
            <th class="num" style="width:15%;">Totale</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($fattura->righe as $i => $riga)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $riga->descrizione }}</td>
            <td class="num">{{ number_format((float) $riga->quantita, 2, ',', '.') }}</td>
            <td class="num">€ {{ number_format((float) $riga->prezzo_unitario, 2, ',', '.') }}</td>
            <td class="num">{{ $riga->iva_percentuale }}%</td>
            <td class="num" style="font-weight:600;">€ {{ number_format((float) $riga->totale_riga, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<div class="clearfix">
    <div class="totali">
        <div class="totali-row">
            <span class="totali-label">Imponibile</span>
            <span>€ {{ number_format((float) $fattura->imponibile, 2, ',', '.') }}</span>
        </div>
        <div class="totali-row">
            <span class="totali-label">IVA {{ $fattura->iva_percentuale }}%</span>
            <span>€ {{ number_format((float) $fattura->iva_importo, 2, ',', '.') }}</span>
        </div>
        <div class="totali-row total">
            <span>Totale</span>
            <span>€ {{ number_format((float) $fattura->totale, 2, ',', '.') }}</span>
        </div>
        @if ($fattura->data_pagamento)
        <div class="totali-row" style="margin-top:8px;color:#16a34a;font-weight:600;">
            <span>Pagato il</span>
            <span>{{ $fattura->data_pagamento->format('d/m/Y') }}</span>
        </div>
        @endif
    </div>
</div>

@if ($fattura->note)
<div class="note-box" style="margin-top:80px;">
    <strong>Note:</strong> {{ $fattura->note }}
</div>
@endif

{{-- Footer --}}
<div class="footer">
    <span>{{ $azienda->ragione_sociale ?: config('app.name') }} · Documento generato il {{ now()->format('d/m/Y H:i') }}</span>
    <span>{{ $fattura->numero_fattura }}</span>
    <span>Documento informatico ai sensi del D.Lgs 82/2005</span>
</div>

</body>
</html>
