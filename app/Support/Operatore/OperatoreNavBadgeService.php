<?php

namespace App\Support\Operatore;

use App\Domain\Bonifica\BonificaService;

final class OperatoreNavBadgeService
{
    public function bonificaPendingCount(): int
    {
        return app(BonificaService::class)->queryVeicoliDaBonificare()->count();
    }
}
