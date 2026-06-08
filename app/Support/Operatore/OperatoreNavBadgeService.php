<?php

namespace App\Support\Operatore;

use App\Domain\Bonifica\BonificaService;
use App\Domain\Vfu\SmontaggioService;

final class OperatoreNavBadgeService
{
    public function bonificaPendingCount(): int
    {
        return app(BonificaService::class)->queryVeicoliDaBonificare()->count();
    }

    public function smontaggioPendingCount(): int
    {
        return app(SmontaggioService::class)->queryVeicoliPerSmontaggio()->count();
    }
}
