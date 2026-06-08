<?php

namespace App\Support\Segreteria;

use App\Models\Fattura;

final class SegreteriaNavBadgeService
{
    /**
     * @return array{count: int, color: 'red'|'yellow'}|null
     */
    public function fatturazioneBadge(): ?array
    {
        $scadute = (int) Fattura::query()->scadute()->count();

        if ($scadute > 0) {
            return ['count' => $scadute, 'color' => 'red'];
        }

        $inScadenza = (int) Fattura::query()
            ->where('stato', 'emessa')
            ->whereNotNull('data_scadenza')
            ->whereBetween('data_scadenza', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        if ($inScadenza > 0) {
            return ['count' => $inScadenza, 'color' => 'yellow'];
        }

        return null;
    }
}
