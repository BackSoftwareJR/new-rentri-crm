<?php

namespace App\Services\Rentri;

use App\Enums\FirStato;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\Fir;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RentriFirSigningService implements RentriFirSigningServiceInterface
{
    public function __construct(
        private RentriXfirPayloadBuilder $payloadBuilder,
        private RentriXfirValidator $validator,
        private RentriXfirCoseSigner $signer,
        private RentriFirQrPayloadValidator $qrPayloadValidator,
    ) {}

    public function sign(Fir $fir): Fir
    {
        if (! $this->canSign($fir)) {
            $reason = $this->signBlockReason($fir) ?? 'Il FIR non è idoneo alla firma xFIR.';

            throw new RuntimeException($reason);
        }

        $settings = RentriSetting::instance();

        $runtimeMode = app(RentriRuntimeModeService::class);

        if (! $runtimeMode->isFirmaStub($settings)) {
            $firmaCerts = app(\App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface::class);

            if (! $firmaCerts->validate($settings)) {
                throw new RuntimeException('Certificato firma remota RENTRI non configurato.');
            }

            if ($firmaCerts->isExpired($settings)) {
                throw new RuntimeException('Certificato firma remota RENTRI scaduto.');
            }
        }

        return DB::transaction(function () use ($fir, $settings) {
            $xfirPayload = $this->payloadBuilder->build($fir);
            $this->validator->validate($xfirPayload);

            $signed = $this->signer->sign($xfirPayload, $settings);
            $signed['api_mode'] = app(RentriRuntimeModeService::class)->isFirmaStub($settings) ? 'stub' : 'live';
            $signed['numero_fir'] = $fir->numero_fir;
            $signed['firmato_at'] = now()->toIso8601String();

            $fir->update([
                'xfir_payload'        => json_encode($xfirPayload, JSON_THROW_ON_ERROR),
                'xfir_signed_payload' => json_encode($signed, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                'firmato_at'          => now(),
                'stato'               => FirStato::Firmato,
            ]);

            return $fir->fresh();
        });
    }

    public function canSign(Fir $fir): bool
    {
        return $this->signBlockReason($fir) === null;
    }

    public function signBlockReason(Fir $fir): ?string
    {
        if ($fir->firmato_at !== null) {
            return 'Il FIR è già firmato digitalmente.';
        }

        if ($fir->stato !== FirStato::Vidimato || $fir->vidimato_at === null) {
            return 'Vidima il FIR presso MASE prima della firma xFIR.';
        }

        if (blank($fir->qr_payload)) {
            return 'Payload QR ministeriale assente: vidima incompleta o non valida.';
        }

        /** @var array<string, mixed> $qr */
        $qr = json_decode($fir->qr_payload, true) ?: [];

        if (! $this->qrPayloadValidator->isValid($qr)) {
            return 'Payload QR ministeriale incompleto: protocollo, transazione o codice QR mancanti.';
        }

        return null;
    }

    public function signedPayloadFilename(Fir $fir): string
    {
        $numero = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $fir->numero_fir) ?: 'fir';

        return 'xfir-'.$numero.'.json';
    }
}
