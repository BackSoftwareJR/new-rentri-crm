<?php

namespace App\Models;

use App\Domain\Azienda\AziendaSettingService;

/**
 * Facade for company invoicing settings stored in {@see CompanySetting}.
 */
class AziendaSetting
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return app(AziendaSettingService::class)->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        app(AziendaSettingService::class)->set($key, $value);
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return app(AziendaSettingService::class)->all();
    }
}
