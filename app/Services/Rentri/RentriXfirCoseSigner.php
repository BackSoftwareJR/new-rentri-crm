<?php

namespace App\Services\Rentri;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Firma COSE_Sign1 su payload xFIR (stub HMAC in dev/test, PKCS#12 in live).
 */
class RentriXfirCoseSigner
{
    public function __construct(
        private RentriFirmaCertificateServiceInterface $firmaCertificates,
    ) {}

    /**
     * @param  array<string, mixed>  $xfirPayload
     * @return array<string, mixed>
     */
    public function sign(array $xfirPayload, RentriSetting $settings): array
    {
        $canonical = json_encode($xfirPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $payloadB64 = $this->base64UrlEncode($canonical);

        if ($this->usesStubSigner()) {
            return $this->stubCoseSign1($settings, $payloadB64, $canonical);
        }

        if (! $this->firmaCertificates->validate($settings)) {
            throw new RuntimeException('Certificato firma xFIR non configurato.');
        }

        if ($this->firmaCertificates->isExpired($settings)) {
            throw new RuntimeException('Certificato firma xFIR scaduto.');
        }

        return $this->liveCoseSign1($settings, $payloadB64, $canonical);
    }

    /**
     * @return array<string, mixed>
     */
    private function stubCoseSign1(RentriSetting $settings, string $payloadB64, string $canonical): array
    {
        $protected = ['alg' => 'STUB-HMAC-SHA256', 'typ' => 'COSE_Sign1'];
        $protectedB64 = $this->base64UrlEncode(json_encode($protected, JSON_THROW_ON_ERROR));
        $password = $settings->firma_cert_password_encrypted
            ? \Illuminate\Support\Facades\Crypt::decryptString($settings->firma_cert_password_encrypted)
            : 'stub-firma-pass';
        $signature = hash_hmac('sha256', $protectedB64.'.'.$payloadB64, $password);

        return [
            'typ'       => 'COSE_Sign1',
            'alg'       => 'STUB-HMAC-SHA256',
            'protected' => $protectedB64,
            'payload'   => $payloadB64,
            'signature' => 'stub:'.$signature,
            'stub'      => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveCoseSign1(RentriSetting $settings, string $payloadB64, string $canonical): array
    {
        $path = $this->firmaCertificates->absolutePath($settings);
        $password = \Illuminate\Support\Facades\Crypt::decryptString($settings->firma_cert_password_encrypted);

        if ($path === null || ! function_exists('openssl_pkcs12_read')) {
            throw new RuntimeException('OpenSSL non disponibile per firma xFIR live.');
        }

        $content = file_get_contents($path);

        if ($content === false || ! openssl_pkcs12_read($content, $certs, $password)) {
            throw new RuntimeException('Impossibile leggere certificato firma xFIR.');
        }

        $privateKey = openssl_pkey_get_private($certs['pkey'] ?? '');

        if ($privateKey === false) {
            throw new RuntimeException('Chiave privata firma xFIR non disponibile nel keystore.');
        }

        $keyDetails = openssl_pkey_get_details($privateKey);
        $isEc = ($keyDetails['type'] ?? null) === OPENSSL_KEYTYPE_EC;
        $alg = $isEc ? 'ES256' : 'RS256';
        $protected = ['alg' => $alg, 'typ' => 'COSE_Sign1'];
        $protectedB64 = $this->base64UrlEncode(json_encode($protected, JSON_THROW_ON_ERROR));
        $signingInput = $protectedB64.'.'.$payloadB64;
        $signatureRaw = '';

        if (! openssl_sign($signingInput, $signatureRaw, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Firma digitale xFIR fallita.');
        }

        return [
            'typ'       => 'COSE_Sign1',
            'alg'       => $alg,
            'protected' => $protectedB64,
            'payload'   => $payloadB64,
            'signature' => $this->base64UrlEncode($signatureRaw),
            'stub'      => false,
        ];
    }

    private function usesStubSigner(): bool
    {
        return app(RentriRuntimeModeService::class)->isFirmaStub();
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
