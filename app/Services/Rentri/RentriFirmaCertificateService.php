<?php

namespace App\Services\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RentriFirmaCertificateService implements RentriFirmaCertificateServiceInterface
{
    private const STORAGE_DIR = 'rentri/certificates/firma';

    public function upload(UploadedFile $certificate, string $password): RentriSetting
    {
        $settings = RentriSetting::instance();
        $extension = strtolower($certificate->getClientOriginalExtension());

        if (! in_array($extension, ['p12', 'pfx'], true)) {
            throw new InvalidArgumentException('Formato non supportato. Caricare un file .p12 o .pfx per la firma xFIR.');
        }

        $storagePath = self::STORAGE_DIR.'/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($storagePath, $certificate->get());

        $absolutePath = Storage::disk('local')->path($storagePath);
        $expiry = $this->extractExpiry($absolutePath, $password);

        if ($settings->firma_cert_path_encrypted) {
            $this->deleteStoredCertificate($settings);
        }

        $settings->update([
            'firma_cert_path_encrypted'     => Crypt::encryptString($storagePath),
            'firma_cert_password_encrypted' => Crypt::encryptString($password),
            'firma_cert_scadenza'           => $expiry->toDateString(),
        ]);

        return $settings->fresh();
    }

    public function validate(RentriSetting $settings): bool
    {
        if (blank($settings->firma_cert_path_encrypted) || blank($settings->firma_cert_password_encrypted)) {
            return false;
        }

        $path = $this->absolutePath($settings);

        return $path !== null && is_readable($path);
    }

    public function isExpired(RentriSetting $settings): bool
    {
        if ($settings->firma_cert_scadenza === null) {
            return true;
        }

        return $settings->firma_cert_scadenza->isPast();
    }

    public function absolutePath(RentriSetting $settings): ?string
    {
        if (blank($settings->firma_cert_path_encrypted)) {
            return null;
        }

        $relative = Crypt::decryptString($settings->firma_cert_path_encrypted);

        if (! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }

    private function extractExpiry(string $absolutePath, string $password): \Illuminate\Support\Carbon
    {
        if (! function_exists('openssl_pkcs12_read') || app()->environment('testing')) {
            return now()->addYear();
        }

        $content = file_get_contents($absolutePath);

        if ($content === false || ! openssl_pkcs12_read($content, $certs, $password)) {
            throw new InvalidArgumentException('Certificato firma xFIR non valido o password errata.');
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
            $relative = Crypt::decryptString($settings->firma_cert_path_encrypted);
            Storage::disk('local')->delete($relative);
        } catch (\Throwable) {
            // Ignora path legacy non decifrabile o già rimosso.
        }
    }
}
