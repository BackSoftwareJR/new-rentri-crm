<?php

namespace App\Services\Rentri;

use App\Models\RentriSetting;

/**
 * Mapping endpoint logici (MVP) → path API RENTRI v1.0 (demo/produzione).
 *
 * @see https://demoapi.rentri.gov.it/docs
 */
class RentriEndpoints
{
    public const FIR_VIDIMazione = '/vidimazione-formulari/v1.0';

    public const CODIFICHE_CER = '/codifiche/v1.0/cer';

    public const REGISTRO_TRASMISSIONE = '/registro/v1.0/trasmissione';

    public const XFIR_TRASMISSIONE = '/vidimazione-formulari/v1.0/xfir/trasmissione';

    public static function registroTrasmissioneStatusPath(string $transazioneId): string
    {
        return '/registro/v1.0/'.rawurlencode($transazioneId).'/status';
    }

    public static function registroTrasmissioneResultPath(): string
    {
        return '/registro/v1.0/verifica/result';
    }

    /**
     * @return array<string, string>
     */
    public static function registroTrasmissioneResultQuery(string $transazioneId): array
    {
        return ['transazione_id' => $transazioneId];
    }

    /**
     * Endpoint logico usato internamente → path HTTP live.
     */
    public static function livePath(string $logicalEndpoint): string
    {
        return match ($logicalEndpoint) {
            '/codifiche/cer'      => self::CODIFICHE_CER,
            '/fir/vidima'         => self::FIR_VIDIMazione,
            '/registro/trasmetti' => self::REGISTRO_TRASMISSIONE,
            '/xfir/trasmetti'     => self::XFIR_TRASMISSIONE,
            default               => $logicalEndpoint,
        };
    }

    public static function firVidimaPath(string $codiceBlocco): string
    {
        return self::FIR_VIDIMazione.'/'.rawurlencode($codiceBlocco);
    }

    public static function firVidimaStatusPath(string $transazioneId): string
    {
        return self::FIR_VIDIMazione.'/'.rawurlencode($transazioneId).'/status';
    }

    public static function firVidimaResultPath(): string
    {
        return self::FIR_VIDIMazione.'/verifica/result';
    }

    /**
     * Query per health check live: elenco blocchi FIR (autenticazione mTLS).
     *
     * @return array<string, string>
     */
    public static function blocchiFirQuery(RentriSetting $settings): array
    {
        $identificativo = $settings->cf_operatore ?: $settings->cf ?: '';

        return array_filter([
            'identificativo' => $identificativo,
            'num_iscr_sito'  => $settings->num_iscr_sito,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function firVidimaResultQuery(string $transazioneId): array
    {
        return ['transazione_id' => $transazioneId];
    }

    public static function xfirTrasmissioneStatusPath(string $transazioneId): string
    {
        return '/vidimazione-formulari/v1.0/xfir/'.rawurlencode($transazioneId).'/status';
    }

    public static function xfirTrasmissioneResultPath(): string
    {
        return '/vidimazione-formulari/v1.0/xfir/verifica/result';
    }

    /**
     * @return array<string, string>
     */
    public static function xfirTrasmissioneResultQuery(string $transazioneId): array
    {
        return ['transazione_id' => $transazioneId];
    }
}
