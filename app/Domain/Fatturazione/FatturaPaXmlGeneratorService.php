<?php

namespace App\Domain\Fatturazione;

use App\Domain\Azienda\AziendaSettingService;
use App\Enums\SdiStato;
use App\Models\Fattura;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Storage;
use LogicException;

class FatturaPaXmlGeneratorService
{
    private const NS = 'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2';
    private const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct(
        private readonly AziendaSettingService $aziendaSettings,
    ) {}

    public function generate(Fattura $fattura): string
    {
        if (! in_array($fattura->stato, ['emessa', 'pagata', 'scaduta'], true)) {
            throw new LogicException('XML FatturaPA generabile solo per fatture emesse.');
        }

        $fattura->loadMissing(['anagrafica', 'righe']);

        if ($fattura->righe->isEmpty()) {
            throw new LogicException('Impossibile generare XML senza righe fattura.');
        }

        $azienda    = $this->aziendaSettings->all();
        $anagrafica = $fattura->anagrafica;

        if (blank($azienda['piva'] ?? null)) {
            throw new LogicException('P.IVA azienda non configurata nelle impostazioni.');
        }

        if (! $anagrafica) {
            throw new LogicException('Cliente (anagrafica) mancante sulla fattura.');
        }

        $formato       = $this->resolveFormatoTrasmissione($anagrafica->codice_sdi);
        $codiceDest    = $this->resolveCodiceDestinatario($anagrafica);
        $pecDest       = $this->resolvePecDestinatario($anagrafica, $codiceDest);
        $progressivo   = str_pad((string) $fattura->id, 5, '0', STR_PAD_LEFT);
        $tipoDocumento = $fattura->tipo === 'nota_credito' ? 'TD04' : 'TD01';

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::NS, 'p:FatturaElettronica');
        $root->setAttribute('versione', $formato);
        $root->setAttributeNS(self::NS_XSI, 'xsi:schemaLocation',
            self::NS.' http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd');
        $dom->appendChild($root);

        $header = $dom->createElement('FatturaElettronicaHeader');
        $root->appendChild($header);

        $datiTrasmissione = $dom->createElement('DatiTrasmissione');
        $header->appendChild($datiTrasmissione);

        $idTrasmittente = $dom->createElement('IdTrasmittente');
        $datiTrasmissione->appendChild($idTrasmittente);
        $idTrasmittente->appendChild($dom->createElement('IdPaese', 'IT'));
        $idTrasmittente->appendChild($dom->createElement('IdCodice', $this->sanitizeId($azienda['piva'])));

        $datiTrasmissione->appendChild($dom->createElement('ProgressivoInvio', $progressivo));
        $datiTrasmissione->appendChild($dom->createElement('FormatoTrasmissione', $formato));
        $datiTrasmissione->appendChild($dom->createElement('CodiceDestinatario', $codiceDest));

        if ($pecDest !== null) {
            $datiTrasmissione->appendChild($dom->createElement('PECDestinatario', $pecDest));
        }

        $header->appendChild($this->buildCedentePrestatore($dom, $azienda));
        $header->appendChild($this->buildCessionarioCommittente($dom, $anagrafica));

        $body = $dom->createElement('FatturaElettronicaBody');
        $root->appendChild($body);

        $datiGenerali = $dom->createElement('DatiGenerali');
        $body->appendChild($datiGenerali);

        $datiGeneraliDocumento = $dom->createElement('DatiGeneraliDocumento');
        $datiGenerali->appendChild($datiGeneraliDocumento);
        $datiGeneraliDocumento->appendChild($dom->createElement('TipoDocumento', $tipoDocumento));
        $datiGeneraliDocumento->appendChild($dom->createElement('Divisa', 'EUR'));
        $datiGeneraliDocumento->appendChild($dom->createElement('Data', $fattura->data_emissione->format('Y-m-d')));
        $datiGeneraliDocumento->appendChild($dom->createElement('Numero', $fattura->numero_fattura));

        if (filled($fattura->note)) {
            $causale = $dom->createElement('Causale', $this->truncate($fattura->note, 200));
            $datiGeneraliDocumento->appendChild($causale);
        }

        $body->appendChild($this->buildDatiBeniServizi($dom, $fattura));
        $body->appendChild($this->buildDatiPagamento($dom, $fattura));

        $xml = $dom->saveXML();

        if (! $this->validate($xml)) {
            throw new LogicException('XML FatturaPA generato non supera la validazione strutturale.');
        }

        $path = $this->storeXml($fattura, $xml);

        $fattura->update([
            'fattura_pa_xml_path' => $path,
            'sdi_stato'           => $fattura->sdi_stato ?? SdiStato::DaInviare->value,
        ]);

        return $xml;
    }

    public function validate(string $xml): bool
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml);

        if (! $loaded) {
            libxml_use_internal_errors($previous);

            return false;
        }

        $required = [
            '//p:FatturaElettronica',
            '//DatiTrasmissione/ProgressivoInvio',
            '//DatiTrasmissione/CodiceDestinatario',
            '//CedentePrestatore',
            '//CessionarioCommittente',
            '//DatiBeniServizi/DettaglioLinee',
            '//DatiBeniServizi/DatiRiepilogo',
            '//DatiPagamento',
        ];

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('p', self::NS);

        foreach ($required as $query) {
            if ($xpath->query($query)->length === 0) {
                libxml_use_internal_errors($previous);

                return false;
            }
        }

        libxml_use_internal_errors($previous);

        return true;
    }

    /** @param  array<string, mixed>  $azienda */
    private function buildCedentePrestatore(DOMDocument $dom, array $azienda): DOMElement
    {
        $cedente = $dom->createElement('CedentePrestatore');

        $datiAnagrafici = $dom->createElement('DatiAnagrafici');
        $cedente->appendChild($datiAnagrafici);

        $idFiscale = $dom->createElement('IdFiscaleIVA');
        $datiAnagrafici->appendChild($idFiscale);
        $idFiscale->appendChild($dom->createElement('IdPaese', 'IT'));
        $idFiscale->appendChild($dom->createElement('IdCodice', $this->sanitizeId((string) $azienda['piva'])));

        if (filled($azienda['codice_fiscale'] ?? null)) {
            $datiAnagrafici->appendChild($dom->createElement('CodiceFiscale', $this->sanitizeId((string) $azienda['codice_fiscale'])));
        }

        $anagrafica = $dom->createElement('Anagrafica');
        $datiAnagrafici->appendChild($anagrafica);
        $anagrafica->appendChild($dom->createElement('Denominazione', $this->truncate((string) ($azienda['ragione_sociale'] ?? config('app.name')), 80)));

        $datiAnagrafici->appendChild($dom->createElement('RegimeFiscale', 'RF01'));

        $sede = $dom->createElement('Sede');
        $cedente->appendChild($sede);
        $sede->appendChild($dom->createElement('Indirizzo', $this->truncate((string) ($azienda['indirizzo'] ?? 'N/D'), 60)));
        $sede->appendChild($dom->createElement('CAP', $this->sanitizeCap((string) ($azienda['cap'] ?? '00000'))));
        $sede->appendChild($dom->createElement('Comune', $this->truncate((string) ($azienda['comune'] ?? 'N/D'), 60)));
        $sede->appendChild($dom->createElement('Provincia', strtoupper(substr((string) ($azienda['provincia'] ?? 'XX'), 0, 2))));
        $sede->appendChild($dom->createElement('Nazione', 'IT'));

        return $cedente;
    }

    private function buildCessionarioCommittente(DOMDocument $dom, \App\Models\Anagrafica $anagrafica): DOMElement
    {
        $cessionario = $dom->createElement('CessionarioCommittente');

        $datiAnagrafici = $dom->createElement('DatiAnagrafici');
        $cessionario->appendChild($datiAnagrafici);

        if (filled($anagrafica->piva)) {
            $idFiscale = $dom->createElement('IdFiscaleIVA');
            $datiAnagrafici->appendChild($idFiscale);
            $idFiscale->appendChild($dom->createElement('IdPaese', 'IT'));
            $idFiscale->appendChild($dom->createElement('IdCodice', $this->sanitizeId($anagrafica->piva)));
        } elseif (filled($anagrafica->codice_fiscale)) {
            $datiAnagrafici->appendChild($dom->createElement('CodiceFiscale', $this->sanitizeId($anagrafica->codice_fiscale)));
        }

        $anagraficaEl = $dom->createElement('Anagrafica');
        $datiAnagrafici->appendChild($anagraficaEl);
        $anagraficaEl->appendChild($dom->createElement('Denominazione', $this->truncate($anagrafica->ragione_sociale, 80)));

        $sede = $dom->createElement('Sede');
        $cessionario->appendChild($sede);
        $sede->appendChild($dom->createElement('Indirizzo', $this->truncate((string) ($anagrafica->indirizzo ?? 'N/D'), 60)));
        $sede->appendChild($dom->createElement('CAP', $this->sanitizeCap((string) ($anagrafica->cap ?? '00000'))));
        $sede->appendChild($dom->createElement('Comune', $this->truncate((string) ($anagrafica->citta ?? 'N/D'), 60)));
        $sede->appendChild($dom->createElement('Provincia', strtoupper(substr((string) ($anagrafica->provincia ?? 'XX'), 0, 2))));
        $sede->appendChild($dom->createElement('Nazione', 'IT'));

        return $cessionario;
    }

    private function buildDatiBeniServizi(DOMDocument $dom, Fattura $fattura): DOMElement
    {
        $datiBeniServizi = $dom->createElement('DatiBeniServizi');

        foreach ($fattura->righe as $index => $riga) {
            $linea = $dom->createElement('DettaglioLinee');
            $datiBeniServizi->appendChild($linea);

            $linea->appendChild($dom->createElement('NumeroLinea', (string) ($index + 1)));
            $linea->appendChild($dom->createElement('Descrizione', $this->truncate($riga->descrizione, 1000)));
            $linea->appendChild($dom->createElement('Quantita', $this->formatDecimal((float) $riga->quantita, 3)));
            $linea->appendChild($dom->createElement('PrezzoUnitario', $this->formatDecimal((float) $riga->prezzo_unitario, 2)));
            $linea->appendChild($dom->createElement('PrezzoTotale', $this->formatDecimal((float) $riga->totale_riga, 2)));
            $linea->appendChild($dom->createElement('AliquotaIVA', $this->formatDecimal((float) $riga->iva_percentuale, 2)));
        }

        $riepilogo = $dom->createElement('DatiRiepilogo');
        $datiBeniServizi->appendChild($riepilogo);
        $riepilogo->appendChild($dom->createElement('AliquotaIVA', $this->formatDecimal((float) $fattura->iva_percentuale, 2)));
        $riepilogo->appendChild($dom->createElement('ImponibileImporto', $this->formatDecimal((float) $fattura->imponibile, 2)));
        $riepilogo->appendChild($dom->createElement('Imposta', $this->formatDecimal((float) $fattura->iva_importo, 2)));
        $riepilogo->appendChild($dom->createElement('EsigibilitaIVA', 'I'));

        return $datiBeniServizi;
    }

    private function buildDatiPagamento(DOMDocument $dom, Fattura $fattura): DOMElement
    {
        $datiPagamento = $dom->createElement('DatiPagamento');
        $datiPagamento->appendChild($dom->createElement('CondizioniPagamento', 'TP02'));

        $dettaglio = $dom->createElement('DettaglioPagamento');
        $datiPagamento->appendChild($dettaglio);

        $modalita = match (strtolower((string) $fattura->metodo_pagamento)) {
            'bonifico' => 'MP05',
            'carta', 'carta di credito', 'stripe' => 'MP08',
            'contanti' => 'MP01',
            'assegno' => 'MP02',
            default => 'MP05',
        };

        $dettaglio->appendChild($dom->createElement('ModalitaPagamento', $modalita));
        $dettaglio->appendChild($dom->createElement('DataScadenzaPagamento',
            ($fattura->data_scadenza ?? $fattura->data_emissione)->format('Y-m-d')));
        $dettaglio->appendChild($dom->createElement('ImportoPagamento', $this->formatDecimal((float) $fattura->totale, 2)));

        return $datiPagamento;
    }

    private function storeXml(Fattura $fattura, string $xml): string
    {
        $dir      = 'fatturepa/'.now()->year;
        $filename = strtolower(preg_replace('/[^A-Za-z0-9\-_]/', '_', $fattura->numero_fattura)).'.xml';
        $path     = $dir.'/'.$filename;

        Storage::disk('local')->put($path, $xml);

        return $path;
    }

    private function resolveFormatoTrasmissione(?string $codiceSdi): string
    {
        $codice = strtoupper(trim((string) $codiceSdi));

        if (strlen($codice) === 6 && $codice !== '000000') {
            return 'FPA12';
        }

        return 'FPR12';
    }

    private function resolveCodiceDestinatario(\App\Models\Anagrafica $anagrafica): string
    {
        $codice = strtoupper(trim((string) $anagrafica->codice_sdi));

        if (strlen($codice) === 7) {
            return $codice;
        }

        if (strlen($codice) === 6) {
            return $codice;
        }

        return '0000000';
    }

    private function resolvePecDestinatario(\App\Models\Anagrafica $anagrafica, string $codiceDest): ?string
    {
        if ($codiceDest !== '0000000') {
            return null;
        }

        return filled($anagrafica->pec) ? $anagrafica->pec : null;
    }

    private function sanitizeId(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $value) ?? '');
    }

    private function sanitizeCap(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return str_pad(substr($digits, 0, 5), 5, '0', STR_PAD_LEFT);
    }

    private function formatDecimal(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    private function truncate(string $value, int $max): string
    {
        $value = trim($value);

        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }
}
