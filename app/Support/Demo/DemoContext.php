<?php

namespace App\Support\Demo;

class DemoContext
{
    public static function isActive(): bool
    {
        return self::isDeployDemo() || self::isSessionDemoActive();
    }

    public static function isDeployDemo(): bool
    {
        return (bool) config('demo.enabled', false);
    }

    public static function isSessionDemoActive(): bool
    {
        return (bool) session(config('demo.session.key', 'demo_mode_active'), false);
    }

    public static function offlineNoHttp(): bool
    {
        return self::isDeployDemo() && (bool) config('demo.rentri.offline_no_http', false);
    }

    public static function forceSandboxApi(): bool
    {
        if (self::isDeployDemo()) {
            return (bool) config('demo.rentri.force_sandbox_api', true);
        }

        if (self::isSessionDemoActive()) {
            return true;
        }

        return false;
    }

    /**
     * Palestra operativa: API RENTRI/FIR via demoapi (mTLS), non stub JSON locali.
     * Richiede certificato PKCS#12 sandbox caricato in Impostazioni RENTRI.
     */
    public static function usesLiveSandboxApi(): bool
    {
        if (! self::isActive()) {
            return false;
        }

        if (self::offlineNoHttp()) {
            return false;
        }

        return (bool) config('demo.rentri.live_sandbox', true);
    }

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function scopedModels(): array
    {
        return [
            \App\Models\Fir::class,
            \App\Models\FirBlocco::class,
            \App\Models\RegistroMovimento::class,
            \App\Models\RentriTransmissione::class,
            \App\Models\RentriTransazione::class,
            \App\Models\RentriSetting::class,
            \App\Models\MagazzinoSvuotamento::class,
            \App\Models\Trasporto::class,
            \App\Models\Anagrafica::class,
            \App\Models\VfuRegistration::class,
            \App\Models\EcommerceProdotto::class,
            \App\Models\EcommerceOrdine::class,
            \App\Models\MudDichiarazione::class,
        ];
    }
}
