<?php

namespace App\Services\Rentri;

use LibXMLError;

/**
 * Traduce errori libxml/XSD in messaggi operativi in italiano.
 */
class RentriXfirValidationMessageMapper
{
    /** @var array<string, string> */
    private const ELEMENT_LABELS = [
        'versione'          => 'Versione xFIR',
        'numero_fir'        => 'Numero FIR',
        'codice_blocco'     => 'Codice blocco',
        'progressivo'       => 'Progressivo formulario',
        'identificativo'    => 'Identificativo operatore',
        'num_iscr_sito'     => 'Numero iscrizione sito',
        'data_vidimazione'  => 'Data vidimazione',
        'peso_partenza_kg'  => 'Peso partenza (kg)',
        'protocollo_rentri' => 'Protocollo RENTRI',
        'transazione_id'    => 'ID transazione RENTRI',
        'qr_code'           => 'Codice QR',
        'trasporto'         => 'Sezione trasporto',
        'codice_cer'        => 'Codice CER',
        'quantita_kg'       => 'Quantità trasporto (kg)',
        'destinatario'      => 'Destinatario',
        'xfir'              => 'Formulario xFIR',
    ];

    public function translate(LibXMLError $error): string
    {
        $raw = trim($error->message);
        $line = $error->line > 0 ? " (riga {$error->line})" : '';

        if (preg_match("/Element '\\{[^}]+\\}([^']+)'/", $raw, $matches) === 1) {
            $element = self::ELEMENT_LABELS[$matches[1]] ?? $matches[1];

            if (str_contains($raw, 'Missing child element')) {
                return "Elemento obbligatorio mancante: {$element}{$line}.";
            }

            if (str_contains($raw, 'not expected')) {
                return "Elemento non ammesso nello schema: {$element}{$line}.";
            }

            if (str_contains($raw, 'is not a valid value')) {
                return "Valore non valido per {$element}{$line}.";
            }
        }

        if (str_contains($raw, 'failed to load external entity')) {
            return 'Schema XSD xFIR MASE non trovato o non leggibile.';
        }

        if (str_contains($raw, 'Document is empty')) {
            return 'Documento xFIR XML vuoto.';
        }

        if (str_contains($raw, 'minExclusive')) {
            return "Quantità o peso deve essere maggiore di zero{$line}.";
        }

        if (str_contains($raw, 'minLength')) {
            return "Campo obbligatorio vuoto nello schema xFIR{$line}.";
        }

        if (str_contains($raw, 'enumeration')) {
            return "Versione xFIR non supportata: ammessa solo 1.0{$line}.";
        }

        return "Errore validazione XSD xFIR{$line}: {$raw}";
    }
}
