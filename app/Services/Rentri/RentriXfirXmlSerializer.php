<?php

namespace App\Services\Rentri;

use DOMDocument;
use DOMElement;

/**
 * Serializza il payload xFIR JSON in XML conforme allo schema MASE v1.0.
 */
class RentriXfirXmlSerializer
{
    public const NAMESPACE = 'https://www.rentri.gov.it/schema/xfir/v1.0';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromPayload(array $payload): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $root = $dom->createElementNS(self::NAMESPACE, 'xfir');
        $dom->appendChild($root);

        $this->appendText($dom, $root, 'versione', (string) ($payload['versione'] ?? ''));
        $this->appendText($dom, $root, 'numero_fir', (string) ($payload['numero_fir'] ?? ''));
        $this->appendText($dom, $root, 'codice_blocco', (string) ($payload['codice_blocco'] ?? ''));
        $this->appendText($dom, $root, 'progressivo', (string) ((int) ($payload['progressivo'] ?? 0)));
        $this->appendText($dom, $root, 'identificativo', (string) ($payload['identificativo'] ?? ''));
        $this->appendText($dom, $root, 'num_iscr_sito', (string) ($payload['num_iscr_sito'] ?? ''));
        $this->appendText($dom, $root, 'data_vidimazione', (string) ($payload['data_vidimazione'] ?? ''));

        if (isset($payload['peso_partenza_kg']) && $payload['peso_partenza_kg'] !== null && $payload['peso_partenza_kg'] !== '') {
            $this->appendText($dom, $root, 'peso_partenza_kg', $this->formatDecimal($payload['peso_partenza_kg']));
        }

        foreach (['protocollo_rentri', 'transazione_id', 'qr_code'] as $optional) {
            if ($this->isPresent($payload[$optional] ?? null)) {
                $this->appendText($dom, $root, $optional, (string) $payload[$optional]);
            }
        }

        /** @var array<string, mixed> $trasporto */
        $trasporto = is_array($payload['trasporto'] ?? null) ? $payload['trasporto'] : [];
        $trasportoEl = $dom->createElementNS(self::NAMESPACE, 'trasporto');
        $root->appendChild($trasportoEl);

        if (isset($trasporto['id'])) {
            $this->appendText($dom, $trasportoEl, 'id', (string) ((int) $trasporto['id']));
        }

        $this->appendText($dom, $trasportoEl, 'codice_cer', (string) ($trasporto['codice_cer'] ?? ''));
        $this->appendText($dom, $trasportoEl, 'quantita_kg', $this->formatDecimal($trasporto['quantita_kg'] ?? 0));

        if ($this->isPresent($trasporto['destinatario'] ?? null)) {
            $this->appendText($dom, $trasportoEl, 'destinatario', (string) $trasporto['destinatario']);
        }

        return $dom->saveXML() ?: '';
    }

    private function appendText(DOMDocument $dom, DOMElement $parent, string $name, string $value): void
    {
        $element = $dom->createElementNS(self::NAMESPACE, $name);
        $element->appendChild($dom->createTextNode($value));
        $parent->appendChild($element);
    }

    private function formatDecimal(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0';
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }
}
