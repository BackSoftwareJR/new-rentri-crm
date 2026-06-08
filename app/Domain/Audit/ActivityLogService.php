<?php

namespace App\Domain\Audit;

use App\Models\User;
use App\Support\Demo\DemoContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /** @var list<string> */
    public const MODULI = ['auth', 'vfu', 'fatturazione', 'settings', 'rentri', 'ecommerce', 'mud', 'legacy', 'audit'];

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
            'auth'        => 'Autenticazione',
            'vfu'         => 'VFU',
            'fatturazione'=> 'Fatturazione',
            'settings'    => 'Impostazioni',
            'rentri'      => 'RENTRI',
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
     * @return Collection<int, Activity>
     */
    public function forSubject(string $subjectType, int $subjectId, int $limit = 50): Collection
    {
        return Activity::query()
            ->with('causer:id,name')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('created_at')
            ->limit(max(1, min(100, $limit)))
            ->get();
    }

    /**
     * @return array{icon: string, color: string, detail: ?string}
     */
    public function eventPresentation(Activity $activity): array
    {
        $description = strtolower((string) $activity->description);
        $modulo = (string) $activity->log_name;
        $props = $activity->properties?->toArray() ?? [];

        $color = match ($modulo) {
            'rentri'       => '#7c3aed',
            'fatturazione' => '#2563eb',
            'vfu'          => '#059669',
            'trasporti', 'trasporto' => '#0891b2',
            'ecommerce'    => '#db2777',
            default        => '#64748b',
        };

        $icon = '•';

        if (str_contains($description, 'email') || str_contains($description, 'pec')) {
            $icon = '✉';
            $color = '#2563eb';
        } elseif (str_contains($description, 'emessa') || str_contains($description, 'pagament')) {
            $icon = '€';
            $color = '#16a34a';
        } elseif (str_contains($description, 'annull') || str_contains($description, 'elimin')) {
            $icon = '✕';
            $color = '#dc2626';
        } elseif (str_contains($description, 'rottam') || str_contains($description, 'chius')) {
            $icon = '✓';
            $color = '#059669';
        } elseif ($modulo === 'rentri' || str_contains($description, 'rentri') || str_contains($description, 'xfir') || str_contains($description, 'fir')) {
            $icon = '↗';
            $color = '#7c3aed';
        } elseif (str_contains($description, 'creat')) {
            $icon = '+';
            $color = '#16a34a';
        } elseif (str_contains($description, 'aggiorn') || str_contains($description, 'modific')) {
            $icon = '↻';
            $color = '#ca8a04';
        }

        if (isset($props['stato'])) {
            $detail = 'Stato: '.$props['stato'];
        } elseif ($changed = $this->changedFieldsDetail($props)) {
            $detail = $changed;
        } elseif ($legacy = $this->legacyImportDetail($activity)) {
            $detail = $legacy;
        } else {
            $detail = null;
        }

        return [
            'icon'   => $icon,
            'color'  => $color,
            'detail' => $detail,
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function changedFieldsDetail(array $properties): ?string
    {
        $old = $properties['old'] ?? null;
        $attributes = $properties['attributes'] ?? null;

        if (! is_array($old) && ! is_array($attributes)) {
            return null;
        }

        $fields = array_unique(array_merge(
            is_array($old) ? array_keys($old) : [],
            is_array($attributes) ? array_keys($attributes) : [],
        ));

        if ($fields === []) {
            return null;
        }

        return 'Campi: '.implode(', ', array_slice($fields, 0, 5));
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
