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
use OpenSSLAsymmetricKey;
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

        $agidJwt = $this->buildAgidJwtSignature($settings);

        if ($this->usesMtls()) {
            return [
                'Accept'              => 'application/json',
                'Content-Type'        => 'application/json',
                'Agid-JWT-Signature'  => 'Bearer '.$agidJwt,
            ];
        }

        return array_merge(
            $this->stubSignatureHeaders($settings, $method, $endpoint, $payload),
            ['Agid-JWT-Signature' => 'Bearer '.$agidJwt],
        );
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
            'Agid-JWT-Signature'      => 'Bearer stub.offline.agid-jwt',
        ];
    }

    public function httpClientOptions(RentriSetting $settings): array
    {
        if (! $this->usesMtls() || ! $this->validate($settings)) {
            return [];
        }

        $path     = $this->absolutePath($settings);
        $password = Crypt::decryptString($settings->cert_password_encrypted);

        [$pemCertPath, $pemKeyPath] = $this->ensurePemFiles((string) $path, $password);

        $options = ['verify' => (bool) config('services.rentri.verify_ssl', true)];

        if ($pemCertPath !== null && $pemKeyPath !== null) {
            $options['cert']    = $pemCertPath;
            $options['ssl_key'] = $pemKeyPath;
        } else {
            // Fallback: pass .p12 directly (curl may support it on some builds)
            $options['cert'] = [$path, $password];
        }

        return $options;
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

    /**
     * Builds a self-signed RS256 JWT per AgID ID_AUTH_REST_02 (Agid-JWT-Signature).
     * Claims: iss/sub = CF operatore, aud = RENTRI API base URL, iat/exp/jti standard.
     * Returns a stub token when running in test environment or when the .p12 is a fake.
     */
    private function buildAgidJwtSignature(RentriSetting $settings): string
    {
        if (app()->environment('testing')) {
            return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.stub.agid_jwt_stub';
        }

        $path     = $this->absolutePath($settings);
        $password = Crypt::decryptString($settings->cert_password_encrypted);
        $content  = $path !== null ? @file_get_contents($path) : false;

        if ($content === false || ! @openssl_pkcs12_read($content, $certs, $password)) {
            throw new RuntimeException('Impossibile leggere il certificato PKCS#12 per la firma AgID JWT.');
        }

        /** @var OpenSSLAsymmetricKey|false $privateKey */
        $privateKey = openssl_pkey_get_private($certs['pkey']);

        if ($privateKey === false) {
            throw new RuntimeException('Chiave privata PKCS#12 non leggibile per la firma AgID JWT.');
        }

        $baseUrl = $settings->ambiente === 'produzione'
            ? rtrim((string) config('services.rentri.base_url_production', 'https://api.rentri.gov.it'), '/')
            : rtrim((string) config('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it'), '/');

        $cf  = (string) ($settings->cf_operatore ?? '');
        $iat = time();
        $exp = $iat + 300;
        $jti = (string) Str::uuid();

        $header  = $this->jwtBase64EncodeJson(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = $this->jwtBase64EncodeJson([
            'iss' => $cf,
            'sub' => $cf,
            'aud' => $baseUrl,
            'iat' => $iat,
            'exp' => $exp,
            'jti' => $jti,
        ]);

        $signingInput = $header.'.'.$payload;

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput.'.'.$this->jwtBase64EncodeBinary($signature);
    }

    private function jwtBase64EncodeJson(array $data): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }

    private function jwtBase64EncodeBinary(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Extracts PEM cert and key from a PKCS#12 file and writes them alongside the .p12.
     * Returns [certPemPath, keyPemPath] or [null, null] on failure.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function ensurePemFiles(string $p12AbsolutePath, string $password): array
    {
        if (app()->environment('testing')) {
            return [null, null];
        }

        $dir     = dirname($p12AbsolutePath);
        $base    = pathinfo($p12AbsolutePath, PATHINFO_FILENAME);
        $certPem = $dir.'/'.$base.'-cert.pem';
        $keyPem  = $dir.'/'.$base.'-key.pem';

        if (! is_readable($certPem) || ! is_readable($keyPem)) {
            $content = @file_get_contents($p12AbsolutePath);

            if ($content === false || ! @openssl_pkcs12_read($content, $certs, $password)) {
                return [null, null];
            }

            file_put_contents($certPem, $certs['cert']);
            file_put_contents($keyPem, $certs['pkey']);
            chmod($certPem, 0600);
            chmod($keyPem, 0600);
        }

        return [$certPem, $keyPem];
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

            // Also remove derived PEM files (from ensurePemFiles)
            $abs  = Storage::disk('local')->path($relative);
            $base = pathinfo($abs, PATHINFO_FILENAME);
            $dir  = dirname($abs);

            foreach (['-cert.pem', '-key.pem'] as $suffix) {
                $pem = $dir.'/'.$base.$suffix;
                if (is_file($pem)) {
                    @unlink($pem);
                }
            }
        } catch (\Throwable) {
            // Ignora path legacy non decifrabile o già rimosso.
        }
    }
}
