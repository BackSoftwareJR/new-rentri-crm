<?php

namespace App\Services\Rentri;

use App\Models\Fir;
use App\Models\RentriSetting;

class RentriXfirPayloadBuilder
{
    /**
     * Costruisce payload xFIR ministeriale v1.0 da FIR vidimato + trasporto.
     *
     * @return array<string, mixed>
     */
    public function build(Fir $fir): array
    {
        $fir->loadMissing(['trasporto.codiceCer', 'trasporto.destinatario']);
        $settings = RentriSetting::instance();

        /** @var array<string, mixed> $qr */
        $qr = json_decode($fir->qr_payload ?? '{}', true) ?: [];

        $trasporto = $fir->trasporto;

        return [
            'versione'         => '1.0',
            'numero_fir'       => (string) $fir->numero_fir,
            'codice_blocco'    => (string) $fir->codice_blocco,
            'progressivo'      => (int) $fir->progressivo,
            'identificativo'   => (string) ($settings->cf_operatore ?: $settings->cf ?: ''),
            'num_iscr_sito'    => (string) ($settings->num_iscr_sito ?? ''),
            'data_vidimazione' => $fir->vidimato_at?->toDateString(),
            'peso_partenza_kg' => (float) $fir->peso_partenza_kg,
            'protocollo_rentri' => $qr['protocollo'] ?? null,
            'transazione_id'   => $qr['transazione_id'] ?? null,
            'qr_code'          => $qr['qr_code'] ?? null,
            'trasporto'        => $trasporto === null ? null : [
                'id'           => $trasporto->id,
                'codice_cer'   => $trasporto->codiceCer?->codice,
                'quantita_kg'  => (float) $trasporto->quantita_kg,
                'destinatario' => $trasporto->destinatario?->ragione_sociale,
            ],
        ];
    }
}
