<?php

namespace App\Domain\Mud;

/**
 * Normalizza il body invio telematico MUD per la trasmissione ministeriale.
 *
 * @see docs/SPRINT-95-AUDIT-NOTES.md
 */
class MudTelematicoTransmissionMapper
{
    /** @var list<string> */
    private const MASE_BODY_KEYS = [
        'anno_riferimento',
        'xml',
        'xml_encoding',
        'schema_version',
    ];

    /** @var list<string> */
    private const CRM_METADATA_KEYS = [
        'dichiarazione_id',
        'totali',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function forTransmission(array $payload): array
    {
        $body = [];

        foreach (self::MASE_BODY_KEYS as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $body[$key] = $payload[$key];
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
}
