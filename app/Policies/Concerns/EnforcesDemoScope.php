<?php

namespace App\Policies\Concerns;

use App\Support\Demo\DemoContext;
use Illuminate\Database\Eloquent\Model;

trait EnforcesDemoScope
{
    protected function demoScopeAllows(?Model $model): bool
    {
        if ($model === null) {
            return true;
        }

        if (! array_key_exists('is_demo', $model->getAttributes())) {
            return true;
        }

        return (bool) $model->is_demo === DemoContext::isActive();
    }

    /**
     * @param  mixed  ...$models
     */
    protected function rejectCrossDemoScope(...$models): ?bool
    {
        foreach ($models as $model) {
            if ($model instanceof Model && ! $this->demoScopeAllows($model)) {
                return false;
            }
        }

        return null;
    }
}
