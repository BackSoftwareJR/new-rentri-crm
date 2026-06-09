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

        if (DemoContext::usesLiveSandboxApi()) {
            return false;
        }

        $settings ??= RentriSetting::instance();

        if ($settings->live_mode_enabled_at !== null) {
            return false;
        }

        return (bool) config('services.rentri.api_stub', true);
    }

    public function isFirmaStub(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        if (DemoContext::usesLiveSandboxApi() && ! blank($settings->firma_cert_path_encrypted)) {
            return false;
        }

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
            'offline'       => 'demo offline',
            'sandbox_live'  => 'RENTRI sandbox live',
            'stub'          => 'stub sandbox',
            default         => 'RENTRI live',
        };
    }

    public function apiModeDisplayVariant(?RentriSetting $settings = null): string
    {
        return match ($this->apiModeKind($settings)) {
            'offline'      => 'warning',
            'sandbox_live' => 'success',
            'stub'         => 'info',
            default        => 'success',
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
     * @return 'offline'|'sandbox_live'|'stub'|'live'
     */
    public function apiModeKind(?RentriSetting $settings = null): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        if (DemoContext::usesLiveSandboxApi()) {
            return 'sandbox_live';
        }

        return $this->isApiStub($settings) ? 'stub' : 'live';
    }
}
