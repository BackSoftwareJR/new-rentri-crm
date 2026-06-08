<?php

namespace App\Domain\Logging;

use App\Models\ApplicationLog;
use App\Models\User;
use App\Support\Demo\DemoContext;
use App\Support\Logging\StructuredLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ApplicationLogQueryService
{
    public function __construct(
        private readonly StructuredLogService $structuredLog,
    ) {}

    /**
     * @param  array{
     *   module?: string|null,
     *   level?: string|null,
     *   trace_id?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   demo?: bool|null,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(50, (int) ($filters['per_page'] ?? 20)));

        return $this->query($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{totale: int}
     */
    public function contatori(array $filters = []): array
    {
        return [
            'totale' => $this->query($filters)->count(),
        ];
    }

    public function find(int $id): ?ApplicationLog
    {
        return ApplicationLog::query()->with('user:id,name,email')->find($id);
    }

    /**
     * @param  array<string, mixed>  $filters
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
        return [
            'id', 'trace_id', 'level', 'module', 'channel', 'action', 'message',
            'entity_type', 'entity_id', 'user_id', 'demo_mode', 'outcome',
            'duration_ms', 'created_at', 'context',
        ];
    }

    /**
     * @return list<string>
     */
    public function csvRowFor(ApplicationLog $log): array
    {
        return [
            (string) $log->id,
            $log->trace_id,
            $log->level,
            $log->module,
            $log->channel,
            $log->action,
            $log->message,
            (string) ($log->entity_type ?? ''),
            (string) ($log->entity_id ?? ''),
            $log->user_id !== null ? (string) $log->user_id : '',
            $log->demo_mode ? '1' : '0',
            (string) ($log->outcome ?? ''),
            $log->duration_ms !== null ? (string) $log->duration_ms : '',
            $log->created_at?->format('Y-m-d H:i:s') ?? '',
            json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE) ?: '{}',
        ];
    }

    public function purgeOlderThan(int $days): int
    {
        $cutoff = now()->subDays(max(1, $days));

        return ApplicationLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    public function lastCriticalError(): ?ApplicationLog
    {
        return ApplicationLog::query()
            ->whereIn('level', ['critical', 'alert', 'emergency', 'error'])
            ->orderByDesc('created_at')
            ->first();
    }

    public function moduloLabel(string $module): string
    {
        return $this->structuredLog->moduloLabel($module);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = ApplicationLog::query()->with('user:id,name,email');

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', strtolower((string) $filters['level']));
        }

        if (! empty($filters['trace_id'])) {
            $query->where('trace_id', (string) $filters['trace_id']);
        }

        if (! empty($filters['data_da'])) {
            $query->whereDate('created_at', '>=', $filters['data_da']);
        }

        if (! empty($filters['data_a'])) {
            $query->whereDate('created_at', '<=', $filters['data_a']);
        }

        if (array_key_exists('demo', $filters) && $filters['demo'] !== null && $filters['demo'] !== '') {
            $query->where('demo_mode', filter_var($filters['demo'], FILTER_VALIDATE_BOOL));
        } elseif (DemoContext::isActive()) {
            $query->where('demo_mode', true);
        }

        return $query;
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function utentiConLog(): array
    {
        return ApplicationLog::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn (int $id) => User::query()->find($id))
            ->filter()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function defaultExportFromDate(): Carbon
    {
        $days = (int) config('application_log.export_max_days', 30);

        return now()->subDays(max(1, $days))->startOfDay();
    }
}
