<?php

namespace App\Services\Rentri\Contracts;

use App\Models\RentriSetting;
use Illuminate\Http\UploadedFile;

interface RentriCertificateServiceInterface
{
    public function upload(UploadedFile $certificate, string $password): RentriSetting;

    public function validate(RentriSetting $settings): bool;

    public function isExpired(RentriSetting $settings): bool;

    /**
     * Header HTTP per la richiesta (mTLS in live, HMAC stub in dev legacy).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function signRequest(RentriSetting $settings, string $method, string $endpoint, array $payload = []): array;

    /**
     * Opzioni Guzzle per mTLS (certificato interoperabilità).
     *
     * @return array<string, mixed>
     */
    public function httpClientOptions(RentriSetting $settings): array;

    public function absolutePath(RentriSetting $settings): ?string;
}
