<?php

namespace App\Domain\Dashboard;

use App\Models\CodiceCer;
use App\Models\EcommerceOrdine;
use App\Models\EcommerceProdotto;
use App\Models\Fattura;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\RentriTransazione;
use App\Models\Trasporto;
use App\Models\VfuRegistration;

class DashboardKpiCacheInvalidator
{
    /** @var list<class-string> */
    private const WATCHED_MODELS = [
        VfuRegistration::class,
        RegistroMovimento::class,
        RentriTransazione::class,
        MudDichiarazione::class,
        EcommerceOrdine::class,
        EcommerceProdotto::class,
        CodiceCer::class,
        Fattura::class,
        Trasporto::class,
        RentriSetting::class,
    ];

    public function __construct(
        private KpiRedisCacheService $cache,
    ) {}

    public function register(): void
    {
        foreach (self::WATCHED_MODELS as $model) {
            $model::saved(fn () => $this->cache->forget());
            $model::deleted(fn () => $this->cache->forget());
        }
    }
}
