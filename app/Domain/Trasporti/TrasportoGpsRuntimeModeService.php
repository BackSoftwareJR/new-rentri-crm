<?php

namespace App\Domain\Trasporti;

use App\Support\Demo\DemoContext;

class TrasportoGpsRuntimeModeService
{
    public function isStub(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        return (bool) config('services.trasporto_gps.stub', true);
    }

    public function modeLabel(): string
    {
        return $this->isStub() ? 'stub' : 'live';
    }

    public function modeDisplayLabel(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'GPS demo offline',
            'stub'    => 'GPS stub',
            default   => 'GPS live',
        };
    }

    public function modeDisplayVariant(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'warning',
            'stub'    => 'info',
            default   => 'success',
        };
    }

    /**
     * @return 'offline'|'stub'|'live'
     */
    public function modeKind(): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        return $this->isStub() ? 'stub' : 'live';
    }

    public function prepLabel(): string
    {
        if ($this->isStub()) {
            return 'Integrazione GPS/ETA — prep stub (posizione simulata)';
        }

        $url = (string) config('services.trasporto_gps.provider_url', '');

        return $url !== ''
            ? 'Tracking GPS live — provider '.$url
            : 'Tracking GPS live — configurare TRASPORTO_GPS_PROVIDER_URL';
    }
}
