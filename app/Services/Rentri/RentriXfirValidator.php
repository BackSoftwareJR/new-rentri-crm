<?php

namespace App\Services\Rentri;

use App\Services\Rentri\Exceptions\RentriXfirValidationException;
use DOMDocument;

/**
 * Validazione payload xFIR — campi obbligatori + schema XSD ministeriale RENTRI v1.0.
 */
class RentriXfirValidator
{
    /** @var list<string> */
    private const REQUIRED_ROOT = [
        'versione',
        'numero_fir',
        'codice_blocco',
        'progressivo',
        'identificativo',
        'num_iscr_sito',
        'data_vidimazione',
        'trasporto',
    ];

    /** @var list<string> */
    private const REQUIRED_TRASPORTO = [
        'codice_cer',
        'quantita_kg',
    ];

    public function __construct(
        private RentriXfirXmlSerializer $xmlSerializer,
        private RentriXfirValidationMessageMapper $messageMapper,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(array $payload): void
    {
        $this->validateRequiredFields($payload);
        $this->validateXml($this->xmlSerializer->fromPayload($payload));
    }

    public function validateXml(string $xml): void
    {
        $schemaPath = $this->schemaPath();

        if (! is_readable($schemaPath)) {
            throw RentriXfirValidationException::fromField(
                'Schema XSD xFIR MASE non disponibile nel repository applicativo.',
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            if ($dom->loadXML($xml) === false) {
                throw RentriXfirValidationException::fromField('Documento xFIR XML malformato.');
            }

            if ($dom->schemaValidate($schemaPath) === false) {
                throw RentriXfirValidationException::fromLibxmlErrors(
                    libxml_get_errors(),
                    $this->messageMapper,
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateRequiredFields(array $payload): void
    {
        foreach (self::REQUIRED_ROOT as $field) {
            if (! $this->isPresent($payload[$field] ?? null)) {
                throw RentriXfirValidationException::fromField("Campo xFIR obbligatorio mancante: {$field}.");
            }
        }

        if (($payload['versione'] ?? '') !== '1.0') {
            throw RentriXfirValidationException::fromField('Versione xFIR non supportata: ammessa solo 1.0.');
        }

        if (! is_array($payload['trasporto'] ?? null)) {
            throw RentriXfirValidationException::fromField('Sezione trasporto xFIR non valida.');
        }

        foreach (self::REQUIRED_TRASPORTO as $field) {
            if (! $this->isPresent($payload['trasporto'][$field] ?? null)) {
                throw RentriXfirValidationException::fromField("Campo xFIR trasporto obbligatorio mancante: {$field}.");
            }
        }

        if ((float) ($payload['trasporto']['quantita_kg'] ?? 0) <= 0) {
            throw RentriXfirValidationException::fromField('Quantità trasporto xFIR deve essere maggiore di zero.');
        }
    }

    private function schemaPath(): string
    {
        return (string) config('services.rentri.xfir_schema_path', resource_path('schemas/rentri/xfir-v1.0.xsd'));
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
