<?php

namespace App\Support\Demo;

use App\Enums\RegistroMovimentoTipo;
use App\Models\RegistroMovimento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DemoTrainingScope
{
    /**
     * In palestra operativa il magazzino aggregato prod non va esposto:
     * mostra solo serbatoi collegati a CER usati in record demo scoped.
     */
    public static function applyMagazzinoCerFilter(Builder $query, bool $strictWhenEmpty = true): Builder
    {
        if (! DemoContext::isActive()) {
            return $query;
        }

        $demoCerIds = self::demoCerIds();

        if ($demoCerIds->isEmpty()) {
            return $strictWhenEmpty ? $query->whereRaw('0 = 1') : $query;
        }

        return $query->whereIn('id', $demoCerIds);
    }

    /**
     * Giacenza virtuale da movimenti registro scoped (evita kg prod su magazzino_rifiuti).
     */
    public static function resolveGiacenzaKg(int $codiceCerId): ?float
    {
        if (! DemoContext::isActive()) {
            return null;
        }

        if (! self::demoCerIds()->contains($codiceCerId)) {
            return 0.0;
        }

        $carichi = (float) RegistroMovimento::query()
            ->where('codice_cer_id', $codiceCerId)
            ->where('tipo', RegistroMovimentoTipo::Carico)
            ->sum('peso_kg');

        $scarichi = (float) RegistroMovimento::query()
            ->where('codice_cer_id', $codiceCerId)
            ->where('tipo', RegistroMovimentoTipo::Scarico)
            ->sum('peso_kg');

        return max(0.0, round($carichi - $scarichi, 4));
    }

    public static function skipsSharedStockMutations(): bool
    {
        return DemoContext::isActive();
    }

    /**
     * @return Collection<int, int>
     */
    public static function demoCerIds(): Collection
    {
        return \App\Models\RegistroMovimento::query()
            ->pluck('codice_cer_id')
            ->merge(\App\Models\Trasporto::query()->pluck('codice_cer_id'))
            ->merge(\App\Models\MagazzinoSvuotamento::query()->pluck('codice_cer_id'))
            ->filter()
            ->unique()
            ->values();
    }
}
