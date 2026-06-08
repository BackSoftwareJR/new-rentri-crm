<?php

namespace App\Domain\Rentri;

use App\Models\RentriTransazione;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RentriTransazioneService
{
    public function __construct(
        private RentriTransazioneRetryService $retryService,
    ) {}

    /** @var list<string> */
    public const TIPI_API = ['registro', 'fir', 'xfir', 'health', 'codifiche', 'generic'];

    /** @var list<string> */
    public const STATI = ['in_corso', 'completata', 'errore'];

    /**
     * @param  array{
     *   tipo_api?: string|null,
     *   stato?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 25)));

        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @param  array{tipo_api?: string|null, stato?: string|null, data_da?: string|null, data_a?: string|null}  $filters
     * @return array{totale: int, completate: int, errori: int, in_corso: int, dead_letter: int, retry_pianificati: int}
     */
    public function contatori(array $filters = []): array
    {
        $base = $this->query(array_diff_key($filters, ['stato' => null]));

        return [
            'totale'            => (clone $base)->count(),
            'completate'        => (clone $base)->where('stato', 'completata')->count(),
            'errori'            => (clone $base)->where('stato', 'errore')->whereNull('dead_letter_at')->count(),
            'in_corso'          => (clone $base)->where('stato', 'in_corso')->count(),
            'dead_letter'       => (clone $base)->whereNotNull('dead_letter_at')->count(),
            'retry_pianificati' => (clone $base)->where('stato', 'errore')->whereNotNull('next_retry_at')->whereNull('dead_letter_at')->count(),
        ];
    }

    public function endpointDisplay(RentriTransazione $transazione): string
    {
        return (string) ($transazione->request_json['endpoint'] ?? '—');
    }

    public function methodDisplay(RentriTransazione $transazione): string
    {
        return strtoupper((string) ($transazione->request_json['method'] ?? '—'));
    }

    public function tipoApiLabel(string $tipo): string
    {
        return match ($tipo) {
            'registro'  => 'Registro',
            'fir'       => 'FIR vidima',
            'xfir'      => 'xFIR firmato',
            'health'    => 'Health check',
            'codifiche' => 'Codifiche CER',
            'generic'   => 'Generico',
            default     => ucfirst($tipo),
        };
    }

    public function statoBadgeVariant(string $stato): string
    {
        return match ($stato) {
            'completata' => 'success',
            'errore'     => 'danger',
            default      => 'warning',
        };
    }

    public function retryBadgeVariant(RentriTransazione $transazione): ?string
    {
        if ($this->retryService->isDeadLetter($transazione)) {
            return 'danger';
        }

        if ($this->retryService->hasPendingRetry($transazione)) {
            return 'warning';
        }

        if ($transazione->retry_count > 0 && $transazione->stato === 'errore') {
            return 'warning';
        }

        return null;
    }

    public function retryStatusLabel(RentriTransazione $transazione): ?string
    {
        if ($this->retryService->isDeadLetter($transazione)) {
            return 'Dead-letter';
        }

        if ($this->retryService->hasPendingRetry($transazione)) {
            return 'Retry '.($transazione->next_retry_at?->format('d/m H:i') ?? 'pianificato');
        }

        if ($transazione->retry_count > 0 && $transazione->stato === 'errore') {
            return 'Retry fallito ('.$transazione->retry_count.')';
        }

        return null;
    }

    public function canRetryNow(RentriTransazione $transazione): bool
    {
        return $this->retryService->canRetryNow($transazione);
    }

    public function statoLabel(string $stato): string
    {
        return match ($stato) {
            'completata' => 'Completata',
            'errore'     => 'Errore',
            'in_corso'   => 'In corso',
            default      => ucfirst($stato),
        };
    }

    public function formatJson(?array $data): string
    {
        if ($data === null || $data === []) {
            return '{}';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = RentriTransazione::query();

        if (! empty($filters['tipo_api']) && in_array($filters['tipo_api'], self::TIPI_API, true)) {
            $query->where('tipo_api', $filters['tipo_api']);
        }

        if (! empty($filters['stato']) && in_array($filters['stato'], self::STATI, true)) {
            $query->where('stato', $filters['stato']);
        }

        if (! empty($filters['data_da'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['data_da'])->startOfDay());
        }

        if (! empty($filters['data_a'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['data_a'])->endOfDay());
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
