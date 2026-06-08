<?php

namespace App\Support;

class ItalianFiscalValidator
{
    private const ODD_VALUES = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
        'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
        'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14,
        'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
    ];

    /** @var array<string, string> */
    private const OMOCODIA_TO_DIGIT = [
        'L' => '0', 'M' => '1', 'N' => '2', 'P' => '3', 'Q' => '4',
        'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9',
    ];

    public static function partitaIvaErrorMessage(): string
    {
        return 'La partita IVA non è valida (formato o checksum errato).';
    }

    public static function codiceFiscaleErrorMessage(): string
    {
        return 'Il codice fiscale non è valido (formato o carattere di controllo errato).';
    }

    public static function isValidPartitaIva(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        $digits = self::normalizePartitaIvaDigits($value);

        if ($digits === null) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 10; $i++) {
            $digit = (int) $digits[$i];
            $weight = ($i % 2 === 0) ? 1 : 2;
            $product = $digit * $weight;

            if ($product > 9) {
                $product = (int) floor($product / 10) + ($product % 10);
            }

            $sum += $product;
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $digits[10];
    }

    public static function isValidCodiceFiscale(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        $cf = strtoupper(preg_replace('/\s+/', '', $value) ?? '');

        if (! preg_match('/^[A-Z]{6}[0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{2}[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/', $cf)) {
            return false;
        }

        $body = substr($cf, 0, 15);
        $expectedCheck = $cf[15];
        $sum = 0;

        for ($i = 0; $i < 15; $i++) {
            $char = self::charForChecksum($body[$i], $i);

            if ($i % 2 === 0) {
                if (! isset(self::ODD_VALUES[$char])) {
                    return false;
                }
                $sum += self::ODD_VALUES[$char];
            } else {
                $sum += self::evenValue($char);
            }
        }

        $check = chr(ord('A') + ($sum % 26));

        return $check === $expectedCheck;
    }

    public static function normalizePartitaIvaDigits(string $value): ?string
    {
        $normalized = strtoupper(preg_replace('/[\s.\-]/', '', $value) ?? '');

        if (str_starts_with($normalized, 'IT')) {
            $normalized = substr($normalized, 2);
        }

        if (! preg_match('/^\d{11}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private static function charForChecksum(string $char, int $position): string
    {
        $upper = strtoupper($char);
        $omocodiaPositions = [6, 7, 9, 10, 12, 13, 14];

        if (in_array($position, $omocodiaPositions, true) && isset(self::OMOCODIA_TO_DIGIT[$upper])) {
            return self::OMOCODIA_TO_DIGIT[$upper];
        }

        return $upper;
    }

    private static function evenValue(string $char): int
    {
        if (ctype_digit($char)) {
            return (int) $char;
        }

        return ord($char) - ord('A');
    }
}
