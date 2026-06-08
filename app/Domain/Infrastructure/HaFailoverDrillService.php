<?php

namespace App\Domain\Infrastructure;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Esercitazione failover multi-istanza — health, switch traffic, recovery (Sprint 118).
 */
class HaFailoverDrillService
{
    public const RUNBOOK_DOC = 'docs/HA-FAILOVER-DRILL-RUNBOOK.md';

    public function __construct(
        private readonly HaBackupPreflightService $haPreflight,
    ) {}

    public function primaryAppUrl(): string
    {
        return rtrim((string) config('infrastructure.ha.primary_app_url', ''), '/');
    }

    public function secondaryAppUrl(): string
    {
        return rtrim((string) config('infrastructure.ha.secondary_app_url', ''), '/');
    }

    public function minAppInstances(): int
    {
        return (int) config('infrastructure.ha.min_app_instances', 2);
    }

    public function healthCheckPath(): string
    {
        return '/up';
    }

    public function lastFailoverDrillAt(): ?Carbon
    {
        $raw = config('infrastructure.ha.last_failover_drill_at');

        if (blank($raw)) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function unifiedChecklist(): array
    {
        $isProduction = $this->haPreflight->isProduction();
        $lastDrill = $this->lastFailoverDrillAt();
        $intervalMonths = (int) config('infrastructure.ha.failover_drill_months', 6);
        $drillDue = $lastDrill === null || $lastDrill->lt(now()->subMonths($intervalMonths));

        $items = [
            $this->item(
                'ha_preflight',
                'Preflight HA/backup completo (HaBackupPreflightService)',
                $this->haPreflight->isReadyForHaProduction() || ! $isProduction,
                'Completare checklist in /admin/ha-status prima del drill failover.',
                false,
                'preflight',
            ),
            $this->item(
                'min_instances',
                sprintf('Minimo %d istanze app (HA_MIN_APP_INSTANCES)', $this->minAppInstances()),
                $this->minAppInstances() >= 2 || ! $isProduction,
                'Load balancer con almeno 2 nodi applicativi.',
                false,
                'topology',
            ),
            $this->item(
                'primary_url',
                'URL nodo primario (HA_PRIMARY_APP_URL)',
                $this->primaryAppUrl() !== '' || ! $isProduction,
                'Base URL nodo primario per probe health /up.',
                false,
                'topology',
            ),
            $this->item(
                'secondary_url',
                'URL nodo secondario (HA_SECONDARY_APP_URL)',
                $this->secondaryAppUrl() !== '' || ! $isProduction,
                'Base URL nodo secondario per simulazione failover.',
                false,
                'topology',
            ),
            $this->item(
                'health_route',
                'Health check Laravel (/up) registrato',
                $this->healthRouteAvailable(),
                'Endpoint LB: GET /up — bootstrap/app.php health route.',
                false,
                'health',
            ),
            $this->item(
                'redis_session',
                'Sessioni Redis condivise (failover session-safe)',
                $this->haPreflight->isRedisSession() || ! $isProduction,
                'SESSION_DRIVER=redis — vedi REDIS-SESSION-PREP.md.',
                false,
                'health',
            ),
            $this->item(
                'queue_redis',
                'Queue Redis + Horizon multi-nodo',
                config('queue.default') === 'redis' || ! $isProduction,
                'QUEUE_CONNECTION=redis con worker su nodo sano.',
                false,
                'health',
            ),
            $this->item(
                'failover_runbook',
                'Runbook failover drill documentato',
                is_file(base_path(self::RUNBOOK_DOC)),
                self::RUNBOOK_DOC,
                false,
                'docs',
            ),
            $this->item(
                'failover_drill_recent',
                sprintf('Failover drill eseguito (< %d mesi)', $intervalMonths),
                ! $drillDue || ! $isProduction,
                $lastDrill
                    ? 'Ultimo drill: '.$lastDrill->format('d/m/Y').' — aggiornare HA_LAST_FAILOVER_DRILL_AT.'
                    : 'Impostare HA_LAST_FAILOVER_DRILL_AT dopo primo drill staging.',
                false,
                'drill',
            ),
        ];

        return $items;
    }

    public function canRunDrill(): bool
    {
        return collect($this->unifiedChecklist())
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    /**
     * @return array{
     *     ready: bool,
     *     ha_preflight_ready: bool,
     *     primary_url: string,
     *     secondary_url: string,
     *     last_drill: ?string,
     *     ok: int,
     *     total: int,
     *     optional_pending: int
     * }
     */
    public function summary(): array
    {
        $items = $this->unifiedChecklist();
        $required = collect($items)->reject(fn (array $i): bool => $i['optional']);

        return [
            'ready'              => $this->canRunDrill(),
            'ha_preflight_ready' => $this->haPreflight->isReadyForHaProduction(),
            'primary_url'        => $this->primaryAppUrl(),
            'secondary_url'      => $this->secondaryAppUrl(),
            'last_drill'         => $this->lastFailoverDrillAt()?->toDateString(),
            'ok'                 => $required->where('ok', true)->count(),
            'total'              => $required->count(),
            'optional_pending'   => collect($items)->filter(fn (array $i): bool => $i['optional'])->reject(fn (array $i): bool => $i['ok'])->count(),
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     checklist: list<array<string, mixed>>,
     *     probe: array<string, mixed>|null,
     *     summary: array<string, mixed>,
     *     phases: array<string, list<array<string, mixed>>>
     * }
     */
    public function dryRunReport(bool $withProbe = false): array
    {
        $probe = $withProbe ? $this->probeNodes() : null;
        $passed = $this->canRunDrill();

        if ($withProbe && $probe !== null) {
            $passed = $passed && $probe['passed'];
        }

        return [
            'passed'    => $passed,
            'checklist' => $this->unifiedChecklist(),
            'probe'     => $probe,
            'summary'   => $this->summary(),
            'phases'    => [
                'health'         => $this->healthPhaseSteps(),
                'traffic_switch' => $this->trafficSwitchSteps(),
                'recovery'       => $this->recoveryChecklist(),
            ],
        ];
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function healthPhaseSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Probe GET /up su nodo primario',
                'detail' => $this->primaryAppUrl() !== ''
                    ? $this->primaryAppUrl().$this->healthCheckPath()
                    : 'Configurare HA_PRIMARY_APP_URL.',
            ],
            [
                'step'   => 2,
                'action' => 'Probe GET /up su nodo secondario',
                'detail' => $this->secondaryAppUrl() !== ''
                    ? $this->secondaryAppUrl().$this->healthCheckPath()
                    : 'Configurare HA_SECONDARY_APP_URL.',
            ],
            [
                'step'   => 3,
                'action' => 'Verificare Redis session + queue raggiungibili',
                'detail' => 'Entrambi i nodi devono condividere stesso cluster Redis.',
            ],
            [
                'step'   => 4,
                'action' => 'Horizon attivo su almeno un nodo',
                'detail' => 'php artisan horizon:status — supervisor attivo.',
            ],
        ];
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function trafficSwitchSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Simulare nodo primario unhealthy',
                'detail' => 'Drain connessioni LB — rimuovere primario dal pool target group.',
            ],
            [
                'step'   => 2,
                'action' => 'Confermare traffico su nodo secondario',
                'detail' => '100% richieste HTTP verso secondary — monitor 5xx rate.',
            ],
            [
                'step'   => 3,
                'action' => 'Smoke post-switch',
                'detail' => 'Login segreteria, Livewire, rentri:monitor — sessione persistente.',
            ],
            [
                'step'   => 4,
                'action' => 'Misurare RTO effettivo',
                'detail' => sprintf('Target RTO ≤ %d min (HA_RTO_MINUTES).', $this->haPreflight->rpoRtoTargets()['rto_minutes']),
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function recoveryChecklist(): array
    {
        return [
            [
                'key'   => 'primary_restored',
                'label' => 'Nodo primario ripristinato e health /up OK',
                'ok'    => false,
                'hint'  => 'Compilare post-drill — probe manuale o --probe.',
            ],
            [
                'key'   => 'lb_rebalanced',
                'label' => 'Load balancer ribilanciato (entrambi i nodi in pool)',
                'ok'    => false,
                'hint'  => 'Reintegrare primario dopo fix root cause.',
            ],
            [
                'key'   => 'sessions_intact',
                'label' => 'Sessioni utente non perse (Redis centralizzato)',
                'ok'    => $this->haPreflight->isRedisSession(),
                'hint'  => 'Verificare login esistente post-failover.',
            ],
            [
                'key'   => 'drill_timestamp',
                'label' => 'HA_LAST_FAILOVER_DRILL_AT aggiornato',
                'ok'    => $this->lastFailoverDrillAt() !== null,
                'hint'  => 'Documentare data drill in .env e runbook sign-off.',
            ],
            [
                'key'   => 'post_mortem',
                'label' => 'Post-mortem entro 24h (opzionale staging)',
                'ok'    => false,
                'hint'  => 'Registrare durata RTO e azioni correttive.',
            ],
        ];
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function rollbackSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Reintegrare nodo primario nel pool LB',
                'detail' => 'Verificare GET /up 200 prima di riattivare traffico.',
            ],
            [
                'step'   => 2,
                'action' => 'Ripristinare weight bilanciamento 50/50',
                'detail' => 'Target group con entrambi i nodi healthy.',
            ],
            [
                'step'   => 3,
                'action' => 'Confermare Horizon su entrambi i nodi',
                'detail' => 'Evitare doppio processing — un solo leader Horizon consigliato.',
            ],
            [
                'step'   => 4,
                'action' => 'Aggiornare HA_LAST_FAILOVER_DRILL_AT',
                'detail' => 'Sign-off runbook § rollback post-drill.',
            ],
            [
                'step'   => 5,
                'action' => 'Smoke finale entrambi i nodi',
                'detail' => 'Login, dashboard KPI, rentri:preflight --demo.',
            ],
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     primary: array{ok: bool, url: string, http_status: ?int, message: string},
     *     secondary: array{ok: bool, url: string, http_status: ?int, message: string}
     * }
     */
    public function probeNodes(): array
    {
        $primary = $this->probeNode($this->primaryAppUrl(), 'primario');
        $secondary = $this->probeNode($this->secondaryAppUrl(), 'secondario');

        $hasPrimary = $this->primaryAppUrl() !== '';
        $hasSecondary = $this->secondaryAppUrl() !== '';

        if (! $hasPrimary && ! $hasSecondary) {
            return [
                'passed'    => true,
                'primary'   => $primary,
                'secondary' => $secondary,
                'message'   => 'Nessun URL nodo configurato — probe saltato.',
            ];
        }

        $passed = (! $hasPrimary || $primary['ok']) && (! $hasSecondary || $secondary['ok']);

        return [
            'passed'    => $passed,
            'primary'   => $primary,
            'secondary' => $secondary,
        ];
    }

    public function runbookRelativePath(): string
    {
        return self::RUNBOOK_DOC;
    }

    /**
     * @return array{ok: bool, url: string, http_status: ?int, message: string}
     */
    private function probeNode(string $baseUrl, string $label): array
    {
        if ($baseUrl === '') {
            return [
                'ok'          => false,
                'url'         => '',
                'http_status' => null,
                'message'     => 'URL nodo '.$label.' non configurato.',
            ];
        }

        $url = $baseUrl.$this->healthCheckPath();

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return [
                    'ok'          => true,
                    'url'         => $url,
                    'http_status' => $response->status(),
                    'message'     => 'Health OK — nodo '.$label,
                ];
            }

            return [
                'ok'          => false,
                'url'         => $url,
                'http_status' => $response->status(),
                'message'     => 'HTTP '.$response->status().' — nodo '.$label,
            ];
        } catch (\Throwable $e) {
            return [
                'ok'          => false,
                'url'         => $url,
                'http_status' => null,
                'message'     => $e->getMessage(),
            ];
        }
    }

    private function healthRouteAvailable(): bool
    {
        return app()->bound('router')
            && collect(app('router')->getRoutes())->contains(
                fn ($route): bool => in_array('GET', $route->methods(), true)
                    && str_ends_with($route->uri(), 'up'),
            );
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}
     */
    private function item(
        string $key,
        string $label,
        bool $ok,
        ?string $hint,
        bool $optional,
        string $group,
    ): array {
        return compact('key', 'label', 'ok', 'hint', 'optional', 'group');
    }
}
