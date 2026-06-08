<?php

namespace Tests\Feature\Sprint42;

use App\Services\Rentri\Exceptions\RentriFirQrValidationException;
use App\Services\Rentri\RentriFirQrPayloadBuilder;
use App\Services\Rentri\RentriFirQrPayloadValidator;
use Tests\TestCase;

class RentriFirQrPayloadValidatorTest extends TestCase
{
    public function test_valid_ministerial_qr_payload_passes(): void
    {
        $payload = app(RentriFirQrPayloadBuilder::class)->build(
            ['protocollo' => 'RENTRI-QR-001', 'qr_code' => 'BASE64-QR-DATA'],
            'SITE-BLK-0001',
            'BLK-001',
            1,
            'SITE-001',
            'tx-uuid-001',
        );

        app(RentriFirQrPayloadValidator::class)->validate($payload);
        $this->assertSame('1.0', $payload['versione']);
        $this->assertSame('RENTRI-QR-001', $payload['protocollo']);
    }

    public function test_missing_protocollo_fails_validation(): void
    {
        $this->expectException(RentriFirQrValidationException::class);

        app(RentriFirQrPayloadValidator::class)->validate([
            'versione'       => '1.0',
            'numero_fir'     => 'FIR-1',
            'codice_blocco'  => 'BLK',
            'progressivo'    => 1,
            'transazione_id' => 'tx-1',
            'qr_code'        => 'QR',
        ]);
    }

    public function test_invalid_version_fails_validation(): void
    {
        $this->expectException(RentriFirQrValidationException::class);

        app(RentriFirQrPayloadValidator::class)->validate([
            'versione'       => '2.0',
            'numero_fir'     => 'FIR-1',
            'codice_blocco'  => 'BLK',
            'progressivo'    => 1,
            'protocollo'     => 'P',
            'transazione_id' => 'tx-1',
            'qr_code'        => 'QR',
        ]);
    }
}
