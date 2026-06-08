<?php

namespace App\Services\Rentri;

/**
 * Normalizza il body vidima FIR per la trasmissione MASE.
 * Rimuove metadati CRM (trasporto_id, codice_blocco duplicato) dal body HTTP.
 *
 * @see docs/SPRINT-94-AUDIT-NOTES.md
 */
class RentriFirVidimaTransmissionMapper
{
    /** @var list<string> */
    private const MASE_BODY_KEYS = ['num_iscr_sito', 'progressivo'];

    /** @var list<string> */
    private const CRM_METADATA_KEYS = ['trasporto_id', 'codice_blocco'];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function forTransmission(string $numIscrSito, array $payload): array
    {
        $merged = array_merge(['num_iscr_sito' => $numIscrSito], $payload);
        $body = [];

        foreach (self::MASE_BODY_KEYS as $key) {
            if (array_key_exists($key, $merged) && $merged[$key] !== null && $merged[$key] !== '') {
                $body[$key] = $key === 'progressivo'
                    ? (int) $merged[$key]
                    : $merged[$key];
            }
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function crmAuditOnly(array $payload): array
    {
        $audit = [];

        foreach (self::CRM_METADATA_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $audit[$key] = $payload[$key];
            }
        }

        return $audit;
    }

    /**
     * @return list<string>
     */
    public static function maseBodyKeys(): array
    {
        return self::MASE_BODY_KEYS;
    }

    /**
     * @return list<string>
     */
    public static function crmMetadataKeys(): array
    {
        return self::CRM_METADATA_KEYS;
    }
}
