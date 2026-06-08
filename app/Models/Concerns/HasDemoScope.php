<?php

namespace App\Models\Concerns;

use App\Support\Demo\DemoContext;
use App\Support\Demo\DemoIsolationException;
use Illuminate\Database\Eloquent\Builder;

trait HasDemoScope
{
    protected static function bootHasDemoScope(): void
    {
        static::addGlobalScope('demo', function (Builder $builder): void {
            $builder->where(
                $builder->getModel()->qualifyColumn('is_demo'),
                DemoContext::isActive(),
            );
        });

        static::creating(function (self $model): void {
            if (! isset($model->attributes['is_demo'])) {
                $model->is_demo = DemoContext::isActive();
            }

            if ((bool) $model->is_demo !== DemoContext::isActive()) {
                throw DemoIsolationException::crossModeWrite((bool) $model->is_demo);
            }
        });

        static::updating(function (self $model): void {
            if ((bool) $model->is_demo !== DemoContext::isActive()) {
                throw DemoIsolationException::crossModeWrite((bool) $model->is_demo);
            }
        });
    }

    public function scopeIncludingAllDemoModes(Builder $query): Builder
    {
        return $query->withoutGlobalScope('demo');
    }
}
