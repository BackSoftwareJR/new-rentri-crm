<?php

namespace App\Traits;

use App\Models\Sito;
use App\Support\Sito\SitoContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSito
{
    public function sito(): BelongsTo
    {
        return $this->belongsTo(Sito::class);
    }

    public function scopeForActiveSito(Builder $query): Builder
    {
        $sitoId = SitoContext::active()?->id;

        if ($sitoId !== null) {
            $query->where($query->getModel()->qualifyColumn('sito_id'), $sitoId);
        }

        return $query;
    }

    protected static function bootBelongsToSito(): void
    {
        static::creating(function (self $model): void {
            if ($model->sito_id === null) {
                $model->sito_id = SitoContext::active()?->id;
            }
        });
    }
}
