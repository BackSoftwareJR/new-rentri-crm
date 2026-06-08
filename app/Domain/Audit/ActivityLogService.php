<?php

namespace App\Domain\Audit;

use App\Models\User;
use App\Support\Demo\DemoContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /** @var list<string> */
    public const MODULI = ['rentri', 'ecommerce', 'mud', 'legacy', 'audit'];

    public function record(
        string $modulo,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null,
    ): void {
        if (! config('activitylog.enabled')) {
            return;
        }

        if (! array_key_exists('demo_mode', $properties)) {
            $properties['demo_mode'] = DemoContext::isActive();
        }

        $logger = activity($modulo)->withProperties($properties);

        $resolvedUserId = $userId ?? auth()->id();
        if ($resolvedUserId) {
            $logger->causedBy(User::query()->find($resolvedUserId));
        }

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }

    /**
     * @param  array{
     *   modulo?: string|null,
     *   user_id?: int|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(50, (int) ($filters['per_page'] ?? 20)));

        return $this->query($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array{modulo?: string|null, user_id?: int|null, data_da?: string|null, data_a?: string|null}  $filters
     * @return array{totale: int}
     */
    public function contatori(array $filters = []): array
    {
        return [
            'totale' => $this->query($filters)->count(),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function utentiConAttivita(): array
    {
        return Activity::query()
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id')
            ->map(fn (int $id) => User::query()->find($id))
            ->filter()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @param  array{modulo?: string|null, user_id?: int|null, data_da?: string|null, data_a?: string|null}  $filters
     */
    public function exportQuery(array $filters = []): Builder
    {
        return $this->query($filters)->orderBy('created_at');
    }

    /**
     * @return list<string>
     */
    public function csvHeader(): array
    {
        return ['id', 'log_name', 'description', 'causer_id', 'causer_name', 'created_at', 'properties'];
    }

    /**
     * @return list<string>
     */
    public function csvRowFor(Activity $activity): array
    {
        return [
            (string) $activity->id,
            (string) $activity->log_name,
            (string) $activity->description,
            $activity->causer_id !== null ? (string) $activity->causer_id : '',
            (string) ($activity->causer?->name ?? ''),
            $activity->created_at?->format('Y-m-d H:i:s') ?? '',
            json_encode($activity->properties ?? [], JSON_UNESCAPED_UNICODE) ?: '{}',
        ];
    }

    public function moduloLabel(string $modulo): string
    {
        return match ($modulo) {
            'rentri'    => 'RENTRI',
            'ecommerce' => 'E-commerce',
            'mud'       => 'MUD',
            'legacy'    => 'Migrazione legacy',
            'audit'     => 'Audit export',
            default     => ucfirst($modulo),
        };
    }

    public function legacyImportDetail(Activity $activity): ?string
    {
        if ($activity->log_name !== 'legacy') {
            return null;
        }

        $props = $activity->properties;

        return sprintf(
            '%s · imp: %d · skp: %d · dry-run: %s',
            (string) $props->get('entity', '—'),
            (int) $props->get('imported', 0),
            (int) $props->get('skipped', 0),
            $props->get('dry_run') ? 'sì' : 'no',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = Activity::query()->with('causer:id,name,email');

        if (! empty($filters['modulo'])) {
            $query->where('log_name', $filters['modulo']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('causer_type', User::class)
                ->where('causer_id', (int) $filters['user_id']);
        }

        if (! empty($filters['data_da'])) {
            $query->whereDate('created_at', '>=', $filters['data_da']);
        }

        if (! empty($filters['data_a'])) {
            $query->whereDate('created_at', '<=', $filters['data_a']);
        }

        if (DemoContext::isActive()) {
            $query->where('properties->demo_mode', true);
        }

        return $query;
    }
}
