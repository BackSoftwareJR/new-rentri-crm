<?php

namespace App\Services\Rentri\Contracts;

use App\Models\RentriSetting;
use Illuminate\Http\UploadedFile;

interface RentriFirmaCertificateServiceInterface
{
    public function upload(UploadedFile $certificate, string $password): RentriSetting;

    public function validate(RentriSetting $settings): bool;

    public function isExpired(RentriSetting $settings): bool;

    public function absolutePath(RentriSetting $settings): ?string;
}
