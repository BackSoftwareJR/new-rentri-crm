<?php

namespace App\Services\Rentri;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class RentriCertificateService implements RentriCertificateServiceInterface
{
    private const STORAGE_DIR = 'rentri/certificates';

    public function upload(UploadedFile $certificate, string $password): RentriSetting
    {
        $settings = RentriSetting::instance();
        $extension = strtolower($certificate->getClientOriginalExtension());

        if (! in_array($extension, ['p12', 'pfx'], true)) {
            throw new InvalidArgumentException('Formato non supportato. Caricare un file .p12 o .pfx.');
        }

        $storagePath = self::STORAGE_DIR.'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($storagePath, $certificate->get());

        $absolutePath = Storage::disk('local')->path($storagePath);
        $expiry = $this->extractExpiry($absolutePath, $password);

        if ($settings->cert_path_encrypted) {
            $this->deleteStoredCertificate($settings);
        }

        $settings->update([
            'cert_path_encrypted'     => Crypt::encryptString($storagePath),
            'cert_password_encrypted' => Crypt::encryptString($password),
            'cert_scadenza'           => $expiry->toDateString(),
        ]);

        return $settings->fresh();
    }

    public function validate(RentriSetting $settings): bool
    {
        if (blank($settings->cert_path_encrypted) || blank($settings->cert_password_encrypted)) {
            return false;
        }

        $path = $this->absolutePath($settings);

        return $path !== null && is_readable($path);
    }

    public function isExpired(RentriSetting $settings): bool
    {
        if ($settings->cert_scadenza === null) {
            return true;
        }

        return $settings->cert_scadenza->isPast();
    }

    public function signRequest(RentriSetting $settings, string $method, string $endpoint, array $payload = []): array
    {
        if (! $this->validate($settings)) {
            throw new RuntimeException('Certificato RENTRI non valido o non configurato.');
        }

        if ($this->usesMtls()) {
            return [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ];
        }

        return $this->stubSignatureHeaders($settings, $method, $endpoint, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function signRequestForMode(
        RentriSetting $settings,
        string $method,
        string $endpoint,
        array $payload,
        bool $apiStub,
    ): array {
        if ($this->validate($settings) && ! $this->isExpired($settings)) {
            return $this->signRequest($settings, $method, $endpoint, $payload);
        }

        if ($apiStub) {
            return $this->offlineStubHeaders($method, $endpoint);
        }

        throw new RuntimeException('Certificato RENTRI non valido o non configurato.');
    }

    /**
     * @return array<string, string>
     */
    public function offlineStubHeaders(string $method, string $endpoint): array
    {
        return [
            'Accept'                  => 'application/json',
            'Content-Type'            => 'application/json',
            'X-RENTRI-Timestamp'      => now()->toIso8601String(),
            'X-RENTRI-Cert-Id'        => 'offline-stub',
            'X-RENTRI-Signature'      => 'stub:offline',
            'X-RENTRI-Signature-Alg'  => 'STUB-OFFLINE',
            'X-RENTRI-Logical-Method' => strtoupper($method),
            'X-RENTRI-Logical-Path'   => $endpoint,
        ];
    }

    public function httpClientOptions(RentriSetting $settings): array
    {
        if (! $this->usesMtls() || ! $this->validate($settings)) {
            return [];
        }

        $path = $this->absolutePath($settings);
        $password = Crypt::decryptString($settings->cert_password_encrypted);

        return [
            'cert'   => [$path, $password],
            'verify' => (bool) config('services.rentri.verify_ssl', true),
        ];
    }

    public function absolutePath(RentriSetting $settings): ?string
    {
        if (blank($settings->cert_path_encrypted)) {
            return null;
        }

        $relative = Crypt::decryptString($settings->cert_path_encrypted);

        if (! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }

    private function usesMtls(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        return ! app(RentriRuntimeModeService::class)->isApiStub($settings)
            && config('services.rentri.auth_mode', 'mtls') === 'mtls';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function stubSignatureHeaders(RentriSetting $settings, string $method, string $endpoint, array $payload): array
    {
        $password = Crypt::decryptString($settings->cert_password_encrypted);
        $certId = basename(Crypt::decryptString($settings->cert_path_encrypted));
        $timestamp = now()->toIso8601String();
        $bodyHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $canonical = strtoupper($method)."\n".$endpoint."\n".$timestamp."\n".$bodyHash;
        $signature = hash_hmac('sha256', $canonical, $password);

        return [
            'Accept'                 => 'application/json',
            'Content-Type'           => 'application/json',
            'X-RENTRI-Timestamp'     => $timestamp,
            'X-RENTRI-Cert-Id'       => $certId,
            'X-RENTRI-Signature'     => 'stub:'.$signature,
            'X-RENTRI-Signature-Alg' => 'STUB-HMAC-SHA256',
        ];
    }

    private function extractExpiry(string $absolutePath, string $password): \Illuminate\Support\Carbon
    {
        if (! function_exists('openssl_pkcs12_read') || app()->environment('testing')) {
            return now()->addYear();
        }

        $content = file_get_contents($absolutePath);

        if ($content === false || ! openssl_pkcs12_read($content, $certs, $password)) {
            throw new InvalidArgumentException('File PKCS#12 non valido o password errata.');
        }

        $parsed = openssl_x509_parse($certs['cert'] ?? '');

        if (! is_array($parsed) || ! isset($parsed['validTo_time_t'])) {
            return now()->addYear();
        }

        return \Illuminate\Support\Carbon::createFromTimestamp((int) $parsed['validTo_time_t']);
    }

    private function deleteStoredCertificate(RentriSetting $settings): void
    {
        try {
            $relative = Crypt::decryptString($settings->cert_path_encrypted);
            Storage::disk('local')->delete($relative);
        } catch (\Throwable) {
            // Ignora path legacy non decifrabile o già rimosso.
        }
    }
}
