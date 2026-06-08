<?php

namespace App\Services\Rentri;

/**
 * Normalizza il payload COSE_Sign1 per la trasmissione MASE xFIR.
 * Rimuove metadati CRM (api_mode, numero_fir, firmato_at, stub) dal body ministeriale.
 */
class RentriXfirCoseTransmissionMapper
{
    /** @var list<string> */
    private const MASE_COSE_KEYS = ['typ', 'alg', 'protected', 'payload', 'signature'];

    /** @var list<string> */
    private const CRM_METADATA_KEYS = ['api_mode', 'numero_fir', 'firmato_at', 'stub'];

    /**
     * @param  array<string, mixed>  $signedPayload
     * @return array<string, mixed>
     */
    public static function forTransmission(array $signedPayload): array
    {
        $transmission = [];

        foreach (self::MASE_COSE_KEYS as $key) {
            if (array_key_exists($key, $signedPayload)) {
                $transmission[$key] = $signedPayload[$key];
            }
        }

        $transmission['typ'] ??= 'COSE_Sign1';

        return $transmission;
    }

    /**
     * @return list<string>
     */
    public static function maseCoseKeys(): array
    {
        return self::MASE_COSE_KEYS;
    }

    /**
     * @return list<string>
     */
    public static function crmMetadataKeys(): array
    {
        return self::CRM_METADATA_KEYS;
    }
}
