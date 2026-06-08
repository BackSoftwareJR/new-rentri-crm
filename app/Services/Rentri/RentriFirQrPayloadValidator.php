<?php

namespace App\Services\Rentri;

use App\Services\Rentri\Exceptions\RentriApiException;
use App\Services\Rentri\Exceptions\RentriFirQrValidationException;

class RentriFirQrPayloadValidator
{
    /** @var list<string> */
    private const REQUIRED = [
        'versione',
        'numero_fir',
        'codice_blocco',
        'progressivo',
        'protocollo',
        'transazione_id',
        'qr_code',
    ];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(array $payload): void
    {
        $errors = [];

        foreach (self::REQUIRED as $field) {
            if (! $this->isPresent($payload[$field] ?? null)) {
                $errors[] = $this->label($field).' obbligatorio nel payload QR ministeriale.';
            }
        }

        if (($payload['versione'] ?? null) !== null && (string) $payload['versione'] !== '1.0') {
            $errors[] = 'Versione payload QR non supportata: attesa 1.0.';
        }

        $progressivo = $payload['progressivo'] ?? null;
        if ($progressivo !== null && (int) $progressivo < 1) {
            $errors[] = 'Progressivo FIR non valido nel payload QR.';
        }

        $qrCode = $payload['qr_code'] ?? null;
        if (is_string($qrCode) && trim($qrCode) === '') {
            $errors[] = 'Codice QR ministeriale mancante o vuoto.';
        }

        if ($errors !== []) {
            throw new RentriFirQrValidationException($errors);
        }
    }

    public function isValid(array $payload): bool
    {
        try {
            $this->validate($payload);

            return true;
        } catch (RentriFirQrValidationException) {
            return false;
        }
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    private function label(string $field): string
    {
        return match ($field) {
            'versione'        => 'Versione',
            'numero_fir'      => 'Numero FIR',
            'codice_blocco'   => 'Codice blocco',
            'progressivo'     => 'Progressivo',
            'protocollo'      => 'Protocollo RENTRI',
            'transazione_id'  => 'Transazione RENTRI',
            'qr_code'         => 'Codice QR',
            default           => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
