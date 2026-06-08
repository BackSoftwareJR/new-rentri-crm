<?php

namespace App\Domain\Dashboard;

use App\Support\Demo\DemoContext;
use Illuminate\Support\Facades\Cache;

class KpiRedisCacheService
{
    /**
     * @return array{kpi: array<string, mixed>, cache: array{hit: bool, enabled: bool, driver: string, ttl_seconds: int, key: string}}
     */
    public function aggregate(DashboardKpiService $kpi): array
    {
        if (! $this->enabled()) {
            return $this->wrap($kpi->aggregate(), [
                'hit'         => false,
                'enabled'     => false,
                'driver'      => 'none',
                'ttl_seconds' => 0,
                'key'         => '',
            ]);
        }

        $key = $this->cacheKey();
        $store = $this->storeName();
        $cache = Cache::store($store);
        $hit = $cache->has($key);

        if ($hit) {
            /** @var array<string, mixed> $data */
            $data = $cache->get($key);
        } else {
            $data = $kpi->aggregate();
            $cache->put($key, $data, $this->ttlSeconds());
        }

        return $this->wrap($data, [
            'hit'         => $hit,
            'enabled'     => true,
            'driver'      => $store,
            'ttl_seconds' => $this->ttlSeconds(),
            'key'         => $key,
        ]);
    }

    public function forget(): void
    {
        app(DashboardKpiService::class)->forgetCache();

        if (! $this->enabled()) {
            return;
        }

        Cache::store($this->storeName())->forget($this->cacheKey());
    }

    public function enabled(): bool
    {
        return (bool) config('dashboard.kpi_cache.enabled', true);
    }

    public function ttlSeconds(): int
    {
        return max(30, (int) config('dashboard.kpi_cache.ttl_seconds', 300));
    }

    public function storeName(): string
    {
        $store = config('dashboard.kpi_cache.store');

        if (is_string($store) && $store !== '') {
            return $store;
        }

        return (string) config('cache.default', 'array');
    }

    public function cacheKey(): string
    {
        $scope = DemoContext::isActive() ? 'demo' : 'prod';

        return 'dashboard:kpi:'.$scope;
    }

    /**
     * @param  array<string, mixed>  $kpi
     * @param  array{hit: bool, enabled: bool, driver: string, ttl_seconds: int, key: string}  $cache
     * @return array{kpi: array<string, mixed>, cache: array{hit: bool, enabled: bool, driver: string, ttl_seconds: int, key: string}}
     */
    private function wrap(array $kpi, array $cache): array
    {
        return [
            'kpi'   => $kpi,
            'cache' => $cache,
        ];
    }
}
