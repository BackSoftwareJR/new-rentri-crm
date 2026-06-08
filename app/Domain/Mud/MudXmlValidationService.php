<?php

namespace App\Domain\Mud;

use App\Models\MudDichiarazione;
use App\Support\Logging\StructuredLogService;
use DOMDocument;

class MudXmlValidationService
{
    public const SCHEMA_VERSION = 'mud-v1';

    public function __construct(private readonly StructuredLogService $logger) {}

    /**
     * @return array{valid: bool, errors: list<string>, xml: string|null}
     */
    public function validate(MudDichiarazione $dichiarazione, ?MudService $mud = null): array
    {
        $mud ??= app(MudService::class);
        $xml = $this->buildXml($dichiarazione, $mud);
        $errors = $this->validateXmlString($xml, $dichiarazione->anno_riferimento);

        return [
            'valid'  => $errors === [],
            'errors' => $errors,
            'xml'    => $xml,
        ];
    }

    public function buildXml(MudDichiarazione $dichiarazione, MudService $mud): string
    {
        $payload = $dichiarazione->export_payload
            ?? $mud->buildExportPayload($dichiarazione);

        $isSimulazione = (bool) ($payload['simulazione'] ?? config('services.mud_telematico.stub', true));

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        if ($isSimulazione) {
            $doc->appendChild($doc->createComment(' SIMULAZIONE — file generato in modalità stub; non trasmettere ufficialmente. '));
        }

        $root = $doc->createElement('DichiarazioneMUD');
        $root->setAttribute('versione', self::SCHEMA_VERSION);
        $doc->appendChild($root);

        $anno = $doc->createElement('AnnoRiferimento', (string) ($payload['anno_riferimento'] ?? $dichiarazione->anno_riferimento));
        $root->appendChild($anno);

        $operatore = $doc->createElement('Operatore');
        $this->appendTextChild($doc, $operatore, 'RagioneSociale', (string) ($payload['operatore']['ragione_sociale'] ?? ''));
        $this->appendTextChild($doc, $operatore, 'UnitaOperativa', (string) ($payload['operatore']['unita_operativa'] ?? ''));
        $root->appendChild($operatore);

        $righeNode = $doc->createElement('Righe');
        $root->appendChild($righeNode);

        foreach ($payload['righe'] ?? [] as $riga) {
            $rigaEl = $doc->createElement('Riga');
            $rigaEl->setAttribute('codice_cer', (string) ($riga['codice_cer'] ?? $riga['codice'] ?? ''));
            $righeNode->appendChild($rigaEl);

            $this->appendTextChild($doc, $rigaEl, 'Descrizione', (string) ($riga['descrizione'] ?? ''));
            $this->appendTextChild($doc, $rigaEl, 'QuantitaKg', (string) ($riga['quantita_kg'] ?? $riga['carichi_kg'] ?? 0));
            $this->appendTextChild($doc, $rigaEl, 'UnitaMisura', (string) ($riga['unita_misura'] ?? 'kg'));
            $this->appendTextChild($doc, $rigaEl, 'OperazioneCarico', (string) ($riga['operazione_carico'] ?? 'R13'));
            $this->appendTextChild($doc, $rigaEl, 'OperazioneScarico', (string) ($riga['operazione_scarico'] ?? 'R13'));
            $this->appendTextChild($doc, $rigaEl, 'ImpiantoProvenienza', (string) ($riga['impianto_provenienza'] ?? ''));
            $this->appendTextChild($doc, $rigaEl, 'ImpiantoDestinazione', (string) ($riga['impianto_destinazione'] ?? ''));
            $this->appendTextChild($doc, $rigaEl, 'CarichiKg', (string) ($riga['carichi_kg'] ?? 0));
            $this->appendTextChild($doc, $rigaEl, 'ScarichiKg', (string) ($riga['scarichi_kg'] ?? 0));
            $this->appendTextChild($doc, $rigaEl, 'SaldoKg', (string) ($riga['saldo_kg'] ?? 0));
        }

        $totali = $payload['totali'] ?? [];
        $totaliEl = $doc->createElement('Totali');
        $root->appendChild($totaliEl);
        $this->appendTextChild($doc, $totaliEl, 'CarichiKg', (string) ($totali['carichi_kg'] ?? 0));
        $this->appendTextChild($doc, $totaliEl, 'ScarichiKg', (string) ($totali['scarichi_kg'] ?? 0));
        $this->appendTextChild($doc, $totaliEl, 'SaldoKg', (string) ($totali['saldo_kg'] ?? 0));
        $this->appendTextChild($doc, $totaliEl, 'CodiciCer', (string) ($totali['codici_cer'] ?? 0));

        return $doc->saveXML() ?: '';
    }

    /**
     * @return list<string>
     */
    private function validateXmlString(string $xml, int $annoRiferimento): array
    {
        $errors = [];

        if (trim($xml) === '') {
            return ['XML MUD vuoto.'];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (! $dom->loadXML($xml)) {
            foreach (libxml_get_errors() as $libError) {
                $errors[] = trim($libError->message);
            }
            libxml_clear_errors();

            return $errors !== [] ? $errors : ['XML MUD non valido.'];
        }

        libxml_clear_errors();

        $root = $dom->documentElement;
        if ($root === null || $root->nodeName !== 'DichiarazioneMUD') {
            $errors[] = 'Elemento radice DichiarazioneMUD mancante.';
        }

        if ($root?->getAttribute('versione') !== self::SCHEMA_VERSION) {
            $errors[] = 'Versione schema MUD non supportata.';
        }

        $annoNode = $dom->getElementsByTagName('AnnoRiferimento')->item(0);
        if ($annoNode === null) {
            $errors[] = 'AnnoRiferimento mancante.';
        } elseif ((int) $annoNode->textContent !== $annoRiferimento) {
            $errors[] = 'AnnoRiferimento non coerente con la dichiarazione.';
        }

        if ($dom->getElementsByTagName('Totali')->length === 0) {
            $errors[] = 'Sezione Totali mancante.';
        }

        if ($errors !== []) {
            $this->logger->warning('operatore', 'mud.xml.validation.failed', 'Validazione XML MUD fallita', [
                'entity_type'      => 'MudDichiarazione',
                'extra'            => [
                    'anno_riferimento' => $annoRiferimento,
                    'errors'           => $errors,
                ],
            ]);
        }

        return $errors;
    }

    private function appendTextChild(DOMDocument $doc, \DOMElement $parent, string $name, string $value): void
    {
        $parent->appendChild($doc->createElement($name, $value));
    }
}
