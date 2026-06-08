<?php

namespace Tests\Support;

use App\Models\RentriSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

trait SeedsRentriCertificate
{
    protected function seedRentriCertificate(array $overrides = []): RentriSetting
    {
        $storagePath = 'rentri/certificates/test-operatore.p12';
        Storage::disk('local')->put($storagePath, 'fake-pkcs12-content-for-tests');

        $settings = RentriSetting::instance();
        $settings->update(array_merge([
            'ambiente'                  => 'sandbox',
            'cf'                        => '12345678901',
            'cf_operatore'              => 'RSSMRA80A01H501Z',
            'num_iscr_sito'             => 'SITE-TEST',
            'cert_path_encrypted'       => Crypt::encryptString($storagePath),
            'cert_password_encrypted'   => Crypt::encryptString('secret-cert-pass'),
            'cert_scadenza'             => now()->addYear()->toDateString(),
            'onboarding_step_completed' => 3,
        ], $overrides));

        return $settings->fresh();
    }

    protected function seedRentriFirmaCertificate(array $overrides = []): RentriSetting
    {
        $storagePath = 'rentri/certificates/firma/test-firma.p12';
        Storage::disk('local')->put($storagePath, 'fake-firma-pkcs12-content');

        $settings = $this->seedRentriCertificate($overrides);
        $settings->update(array_merge([
            'firma_cert_path_encrypted'     => Crypt::encryptString($storagePath),
            'firma_cert_password_encrypted' => Crypt::encryptString('secret-firma-pass'),
            'firma_cert_scadenza'           => now()->addYear()->toDateString(),
        ], $overrides));

        return $settings->fresh();
    }

    protected function seedRentriCertificateFromSandboxPath(): RentriSetting
    {
        $path = config('services.rentri.sandbox_cert_path');
        $password = (string) config('services.rentri.sandbox_cert_password', '');

        if (blank($path) || ! is_readable($path)) {
            throw new \RuntimeException('RENTRI_SANDBOX_CERT_PATH non leggibile.');
        }

        $storagePath = 'rentri/certificates/integration-sandbox.p12';
        Storage::disk('local')->put($storagePath, (string) file_get_contents($path));

        $settings = RentriSetting::instance();
        $settings->update([
            'ambiente'                  => 'sandbox',
            'cf_operatore'              => $settings->cf_operatore ?: 'RSSMRA80A01H501Z',
            'num_iscr_sito'             => $settings->num_iscr_sito ?: 'SITE-SANDBOX',
            'cert_path_encrypted'       => Crypt::encryptString($storagePath),
            'cert_password_encrypted'   => Crypt::encryptString($password),
            'cert_scadenza'             => now()->addYear()->toDateString(),
            'onboarding_step_completed' => 3,
        ]);

        return $settings->fresh();
    }

    protected function seedRentriCertificateFromProductionPath(): RentriSetting
    {
        $path = config('services.rentri.production_cert_path');
        $password = (string) config('services.rentri.production_cert_password', '');

        if (blank($path) || ! is_readable($path)) {
            throw new \RuntimeException('RENTRI_PRODUCTION_CERT_PATH non leggibile.');
        }

        $storagePath = 'rentri/certificates/integration-production.p12';
        Storage::disk('local')->put($storagePath, (string) file_get_contents($path));

        $firmaStoragePath = 'rentri/certificates/integration-production-firma.p12';
        Storage::disk('local')->put($firmaStoragePath, (string) file_get_contents($path));

        $settings = RentriSetting::instance();
        $settings->update([
            'ambiente'                      => 'produzione',
            'cf_operatore'                  => $settings->cf_operatore ?: 'RSSMRA80A01H501Z',
            'num_iscr_sito'                 => $settings->num_iscr_sito ?: 'SITE-PROD',
            'piva'                          => $settings->piva ?: '12345678903',
            'cert_path_encrypted'           => Crypt::encryptString($storagePath),
            'cert_password_encrypted'       => Crypt::encryptString($password),
            'cert_scadenza'                 => now()->addYear()->toDateString(),
            'firma_cert_path_encrypted'     => Crypt::encryptString($firmaStoragePath),
            'firma_cert_password_encrypted' => Crypt::encryptString($password),
            'firma_cert_scadenza'           => now()->addYear()->toDateString(),
            'onboarding_step_completed'     => 3,
            'last_health_status'            => ['status' => 'ok', 'message' => 'OK'],
            'last_health_check_at'          => now(),
        ]);

        return $settings->fresh();
    }
}
