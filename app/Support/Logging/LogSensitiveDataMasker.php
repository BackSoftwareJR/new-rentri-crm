<?php

namespace App\Support\Logging;

/**
 * Mascheramento PII / segreti prima della persistenza log.
 */
class LogSensitiveDataMasker
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'secret',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'webhook_secret',
        'cert_path',
        'private_key',
        'stripe-signature',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mask(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $masked[$key] = $this->maskScalar($value);

                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->mask($value);

                continue;
            }

            $masked[$key] = is_string($value) ? $this->maskStringValue($value) : $value;
        }

        return $masked;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_'], '', $key));

        foreach (self::SENSITIVE_KEYS as $needle) {
            if (str_contains($normalized, str_replace('_', '', $needle))) {
                return true;
            }
        }

        return false;
    }

    private function maskScalar(mixed $value): string
    {
        if (! is_scalar($value) || $value === '') {
            return '[REDACTED]';
        }

        return $this->maskStringValue((string) $value);
    }

    private function maskStringValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $value, $matches)) {
            return 'Bearer '.$this->partialMask($matches[1]);
        }

        if (str_contains($value, '@') && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        if (strlen($value) <= 8) {
            return '[REDACTED]';
        }

        return $this->partialMask($value);
    }

    private function partialMask(string $value): string
    {
        $len = strlen($value);

        if ($len <= 4) {
            return '****';
        }

        return substr($value, 0, 4).'…'.substr($value, -2);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        $maskedLocal = strlen($local) <= 2
            ? '**'
            : substr($local, 0, 1).'***';

        return $maskedLocal.'@'.$domain;
    }
}
