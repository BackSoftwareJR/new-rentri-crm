<?php

namespace App\Services\Rentri\Exceptions;

use App\Services\Rentri\RentriXfirValidationMessageMapper;
use LibXMLError;
use RuntimeException;

class RentriXfirValidationException extends RuntimeException
{
    /**
     * @param  list<string>  $italianMessages
     */
    public function __construct(
        string $message,
        public readonly array $italianMessages = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  list<LibXMLError>  $errors
     */
    public static function fromLibxmlErrors(array $errors, RentriXfirValidationMessageMapper $mapper): self
    {
        $italian = array_values(array_filter(array_map(
            static fn (LibXMLError $error) => $mapper->translate($error),
            $errors,
        )));

        if ($italian === []) {
            $italian = ['Validazione XSD xFIR fallita: struttura XML non conforme allo schema MASE v1.0.'];
        }

        return new self('Validazione XSD xFIR fallita.', $italian);
    }

    public static function fromField(string $italianMessage): self
    {
        return new self($italianMessage, [$italianMessage]);
    }
}
