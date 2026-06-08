<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Support\Demo\DemoContext;

class RentriRuntimeModeService
{
    public function isApiStub(?RentriSetting $settings = null): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        $settings ??= RentriSetting::instance();

        if (DemoContext::isSessionDemoActive() && blank($settings->cert_path_encrypted)) {
            return true;
        }

        if ($settings->live_mode_enabled_at !== null) {
            return false;
        }

        return (bool) config('services.rentri.api_stub', true);
    }

    public function isFirmaStub(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        if ($settings->firma_live_enabled_at !== null) {
            return false;
        }

        return (bool) config('services.rentri.firma_stub', true);
    }

    public function isLiveEnabled(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        return $settings->live_mode_enabled_at !== null;
    }

    public function apiModeLabel(?RentriSetting $settings = null): string
    {
        return $this->isApiStub($settings) ? 'stub' : 'live';
    }

    public function apiModeDisplayLabel(?RentriSetting $settings = null): string
    {
        return match ($this->apiModeKind($settings)) {
            'offline' => 'demo offline',
            'stub'    => 'stub sandbox',
            default   => 'RENTRI live',
        };
    }

    public function apiModeDisplayVariant(?RentriSetting $settings = null): string
    {
        return match ($this->apiModeKind($settings)) {
            'offline' => 'warning',
            'stub'    => 'info',
            default   => 'success',
        };
    }

    public function apiModeDisplayLabelFromApiMode(string $apiMode, ?RentriSetting $settings = null): string
    {
        if ($apiMode === 'live') {
            return 'RENTRI live';
        }

        return $this->apiModeDisplayLabel($settings);
    }

    /**
     * @return 'offline'|'stub'|'live'
     */
    public function apiModeKind(?RentriSetting $settings = null): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        return $this->isApiStub($settings) ? 'stub' : 'live';
    }
}
