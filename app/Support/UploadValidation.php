<?php

namespace App\Support;

final class UploadValidation
{
    /**
     * @return list<string|array<int, string>>
     */
    public static function pdfRules(int $maxKb = 10240): array
    {
        return [
            'required',
            'file',
            'mimes:pdf',
            'mimetypes:application/pdf',
            'max:'.$maxKb,
        ];
    }

    /**
     * @return list<string|array<int, string>>
     */
    public static function certificateRules(int $maxKb = 5120): array
    {
        return [
            'required',
            'file',
            'max:'.$maxKb,
            'extensions:p12,pfx',
            'mimetypes:application/x-pkcs12,application/x-pkcs12-certificates,application/octet-stream,application/pkcs12',
        ];
    }

    /**
     * @return list<string|array<int, string>>
     */
    public static function productImageRules(int $maxKb = 2048): array
    {
        return [
            'required',
            'file',
            'max:'.$maxKb,
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
        ];
    }

    /**
     * @return list<string|array<int, string>>
     */
    public static function vfuAllegatoRules(int $maxKb = 5120): array
    {
        return [
            'required',
            'file',
            'max:'.$maxKb,
            'mimes:pdf,jpg,jpeg,png,webp',
            'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
        ];
    }

    /**
     * @return list<string|array<int, string>>
     */
    public static function smontaggioPhotoRules(int $maxKb = 2048): array
    {
        return [
            'nullable',
            'file',
            'max:'.$maxKb,
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
        ];
    }

    /**
     * @return list<string|array<int, string>>
     */
    public static function csvRules(int $maxKb = 2048): array
    {
        return [
            'required',
            'file',
            'max:'.$maxKb,
            'mimes:csv,txt',
            'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel',
        ];
    }
}
