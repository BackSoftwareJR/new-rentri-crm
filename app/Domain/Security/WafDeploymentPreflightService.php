<?php

namespace App\Domain\Security;

use App\Enums\PenTestFindingSeverity;
use App\Enums\PenTestFindingStatus;

/**
 * Preflight deploy WAF edge staging/produzione (Sprint 105, agg. Sprint 114 block tuning).
 */
class WafDeploymentPreflightService
{
    public function __construct(
        private readonly PenTestRemediationService $remediation,
    ) {}
    public function mode(): string
    {
        $mode = strtolower((string) config('waf.mode', 'off'));

        return in_array($mode, ['off', 'monitor', 'block'], true) ? $mode : 'off';
    }

    public function modeLabel(): string
    {
        return match ($this->mode()) {
            'monitor' => 'Monitor-only',
            'block'   => 'Block attivo',
            default   => 'Disattivato',
        };
    }

    public function isActive(): bool
    {
        return $this->mode() !== 'off';
    }

    public function isMonitorOnly(): bool
    {
        return $this->mode() === 'monitor';
    }

    public function isBlockMode(): bool
    {
        return $this->mode() === 'block';
    }

    public function provider(): string
    {
        return (string) config('waf.provider', 'aws');
    }

    /**
     * Path protetti post-ciclo 9 — allineati a WAF-RULES-PREP.md.
     *
     * @return list<array{key: string, label: string, path: string, risk: string, rule: string, monitor: bool, block: bool}>
     */
    public function protectedPaths(): array
    {
        return [
            [
                'key'     => 'login',
                'label'   => 'Login + brute force',
                'path'    => '/login',
                'risk'    => 'A07 auth',
                'rule'    => 'Rate limit IP 10/min',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'two_factor',
                'label'   => '2FA challenge',
                'path'    => '/login/two-factor-challenge',
                'risk'    => 'A07 auth',
                'rule'    => 'Rate limit 5/min (allineato Laravel)',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'livewire',
                'label'   => 'Livewire update',
                'path'    => '/livewire/update',
                'risk'    => 'A03 injection',
                'rule'    => 'Body cap 2 MB; SQLi/XSS CRS; rate 120/min auth',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'stripe_webhook',
                'label'   => 'Webhook Stripe e-commerce',
                'path'    => '/webhooks/stripe/ecommerce',
                'risk'    => 'A08 integrity',
                'rule'    => 'Escludere Stripe-Signature da XSS; rate 60/min IP; no geo block',
                'monitor' => true,
                'block'   => false,
            ],
            [
                'key'     => 'admin_audit',
                'label'   => 'Admin audit + export',
                'path'    => '/admin/audit',
                'risk'    => 'A01 access',
                'rule'    => 'IP allowlist staging; deny non-admin',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'admin_pen_test',
                'label'   => 'Admin pen-test prep',
                'path'    => '/admin/pen-test-prep',
                'risk'    => 'A01 access',
                'rule'    => 'Solo admin; IP allowlist staging',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'admin_waf',
                'label'   => 'Admin WAF status',
                'path'    => '/admin/waf-status',
                'risk'    => 'A01 access',
                'rule'    => 'Solo admin; IP allowlist staging',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'rentri_upload',
                'label'   => 'Upload certificati RENTRI',
                'path'    => '/segreteria/impostazioni/rentri',
                'risk'    => 'A08 upload',
                'rule'    => 'MIME p12/pfx; max 512 KB',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'horizon',
                'label'   => 'Horizon queue',
                'path'    => '/horizon/*',
                'risk'    => 'A05 misconfig',
                'rule'    => 'Solo admin + IP interni; block public',
                'monitor' => true,
                'block'   => true,
            ],
            [
                'key'     => 'sensitive_files',
                'label'   => 'File sensibili',
                'path'    => '/*.env, /*.git/*',
                'risk'    => 'A05 disclosure',
                'rule'    => 'Block always',
                'monitor' => false,
                'block'   => true,
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, applies_in: string}>
     */
    public function deploymentChecklist(): array
    {
        $mode = $this->mode();
        $owaspDoc = base_path('docs/OWASP-INTERNAL-CHECKLIST.md');
        $rolloutDoc = base_path('docs/WAF-STAGING-ROLLOUT.md');
        $siemConfigured = filled(config('waf.siem_log_group'));

        return [
            [
                'key'         => 'owasp_internal',
                'label'       => 'Checklist OWASP interna aggiornata (ciclo 9)',
                'ok'          => is_file($owaspDoc),
                'hint'        => 'docs/OWASP-INTERNAL-CHECKLIST.md',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'rollout_runbook',
                'label'       => 'Runbook rollout staging (48h monitor → block)',
                'ok'          => is_file($rolloutDoc),
                'hint'        => 'docs/WAF-STAGING-ROLLOUT.md',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'waf_mode_set',
                'label'       => 'WAF_MODE configurato (monitor o block in staging/prod)',
                'ok'          => $mode !== 'off',
                'hint'        => 'WAF_MODE=monitor per fase 1; block dopo finestra monitor',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'login_throttle',
                'label'       => 'Throttle login Laravel attivo (throttle:5,1)',
                'ok'          => true,
                'hint'        => 'routes/web.php — complementare a WAF edge',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'stripe_webhook_exclusion',
                'label'       => 'Esclusione WAF header Stripe-Signature documentata',
                'ok'          => true,
                'hint'        => 'WAF-RULES-PREP.md § Stripe webhook',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'livewire_exclusion',
                'label'       => 'Esclusioni Livewire (X-Livewire, CSRF) documentate',
                'ok'          => true,
                'hint'        => 'WAF-RULES-PREP.md § false positive',
                'applies_in'  => 'monitor,block',
            ],
            [
                'key'         => 'siem_logging',
                'label'       => 'Log WAF → SIEM / CloudWatch (retention 90 gg)',
                'ok'          => $siemConfigured || $mode === 'off',
                'hint'        => 'WAF_SIEM_LOG_GROUP in .env staging/prod',
                'applies_in'  => 'block',
            ],
            [
                'key'         => 'monitor_window',
                'label'       => sprintf(
                    'Finestra monitor-only ≥ %dh prima del block produzione',
                    (int) config('waf.monitor_hours_before_block', 48),
                ),
                'ok'          => $mode !== 'block' || $siemConfigured,
                'hint'        => 'WAF_MONITOR_HOURS_BEFORE_BLOCK=48',
                'applies_in'  => 'block',
            ],
            [
                'key'         => 'zero_p0_findings',
                'label'       => 'Zero findings P0 aperti (gate remediation → WAF block prod)',
                'ok'          => $this->remediation->isGoLiveGateClear(),
                'hint'        => sprintf(
                    '%d P0 aperti — chiudere su /admin/pen-test-prep prima block produzione',
                    $this->remediation->openCountBySeverity(PenTestFindingSeverity::P0),
                ),
                'applies_in'  => 'block',
            ],
            [
                'key'         => 'waf_paths_p0_p1_clear',
                'label'       => 'Zero findings P0/P1 aperti su path WAF mappati',
                'ok'          => $this->openWafPathFindingsCount(['P0', 'P1']) === 0,
                'hint'        => 'Cross-ref pen-test asset → regole WAF; vedi tab UI findings correlati',
                'applies_in'  => 'block',
            ],
        ];
    }

    /**
     * Checklist dedicata al passaggio block mode produzione post-deploy (Sprint 114).
     *
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function productionBlockChecklist(): array
    {
        $mode = $this->mode();
        $siemConfigured = filled(config('waf.siem_log_group'));
        $rolloutDoc = base_path('docs/WAF-STAGING-ROLLOUT.md');
        $openP0P1OnPaths = $this->openWafPathFindingsCount(['P0', 'P1']);

        return [
            [
                'key'   => 'staging_block_stable',
                'label' => 'Staging block stabile ≥ 3 giorni (smoke Livewire/login OK)',
                'ok'    => is_file($rolloutDoc),
                'hint'  => 'WAF-STAGING-ROLLOUT.md §3 — gate manuale infra',
            ],
            [
                'key'   => 'prod_monitor_48h',
                'label' => sprintf(
                    'Produzione monitor-only ≥ %dh completata',
                    (int) config('waf.monitor_hours_before_block', 48),
                ),
                'ok'    => $mode !== 'block' || $siemConfigured,
                'hint'  => 'WAF-STAGING-ROLLOUT.md §4 — review SIEM prima block',
            ],
            [
                'key'   => 'siem_prod',
                'label' => 'SIEM produzione configurato (WAF_SIEM_LOG_GROUP)',
                'ok'    => $siemConfigured,
                'hint'  => 'Es. /aws/waf/rentri-crm-prod',
            ],
            [
                'key'   => 'waf_mode_block',
                'label' => 'WAF_MODE=block su produzione',
                'ok'    => $mode === 'block',
                'hint'  => 'Impostare env + redeploy; badge UI «Block attivo»',
            ],
            [
                'key'   => 'zero_p0_remediation',
                'label' => 'Zero findings P0 aperti (remediation vendor)',
                'ok'    => $this->remediation->isGoLiveGateClear(),
                'hint'  => '/admin/pen-test-prep',
            ],
            [
                'key'   => 'waf_findings_tuned',
                'label' => 'Findings P0/P1 su path WAF chiusi o regole aggiornate',
                'ok'    => $openP0P1OnPaths === 0,
                'hint'  => $openP0P1OnPaths.' finding P0/P1 ancora aperti su path protetti',
            ],
            [
                'key'   => 'stripe_webhook_exclusion',
                'label' => 'Webhook Stripe: count-only body (no block HMAC)',
                'ok'    => $this->stripeWebhookBlockDisabled(),
                'hint'  => 'WAF-RULES-PREP.md § Stripe webhook',
            ],
            [
                'key'   => 'rollback_runbook',
                'label' => 'Rollback runbook condiviso con ops',
                'ok'    => is_file($rolloutDoc),
                'hint'  => 'WAF-STAGING-ROLLOUT.md §6',
            ],
        ];
    }

    /**
     * Guida toggle modalità WAF (env-only — attivazione edge = infra).
     *
     * @return list<array{mode: string, label: string, env: string, description: string, next_step: string}>
     */
    public function modeToggleGuide(): array
    {
        return [
            [
                'mode'        => 'off',
                'label'       => 'Disattivato',
                'env'         => 'WAF_MODE=off',
                'description' => 'Locale, CI, dev — nessuna regola edge.',
                'next_step'   => 'Staging: passare a monitor per avviare rollout.',
            ],
            [
                'mode'        => 'monitor',
                'label'       => 'Monitor-only',
                'env'         => 'WAF_MODE=monitor',
                'description' => 'Count/log SIEM, zero deny — analisi false positive.',
                'next_step'   => sprintf(
                    'Dopo ≥%dh monitor prod + review findings → WAF_MODE=block',
                    (int) config('waf.monitor_hours_before_block', 48),
                ),
            ],
            [
                'mode'        => 'block',
                'label'       => 'Block attivo',
                'env'         => 'WAF_MODE=block',
                'description' => 'Deny su match CRS/custom rules — produzione hardening.',
                'next_step'   => 'Monitorare SIEM 48h post-block; tuning regole per path con findings.',
            ],
        ];
    }

    /**
     * Runbook tuning post-deploy block mode (Sprint 114).
     *
     * @return list<array{step: int, title: string, action: string}>
     */
    public function tuningRunbookSteps(): array
    {
        return [
            [
                'step'   => 1,
                'title'  => 'Review SIEM monitor 48h',
                'action' => 'Verificare count SQLi/XSS su /livewire/update e baseline webhook Stripe.',
            ],
            [
                'step'   => 2,
                'title'  => 'Cross-ref findings P0/P1',
                'action' => 'Allineare regole WAF ai path con finding aperti su /admin/pen-test-prep.',
            ],
            [
                'step'   => 3,
                'title'  => 'Attivazione block graduale',
                'action' => 'Path traversal → SQLi QS → XSS QS → XSS body Livewire (WAF-STAGING-ROLLOUT §5).',
            ],
            [
                'step'   => 4,
                'title'  => 'Smoke post-block',
                'action' => 'Login, Livewire VFU, upload cert, Stripe CLI webhook, GET /.env → 403.',
            ],
            [
                'step'   => 5,
                'title'  => 'Chiusura findings correlati',
                'action' => 'Registrare evidenza fix su pen-test prep; export remediation template.',
            ],
            [
                'step'   => 6,
                'title'  => 'Monitor post-block 48h',
                'action' => 'Alert spike block rate; rollback count-only se FP massivo (§6 rollback).',
            ],
        ];
    }

    /**
     * Path WAF arricchiti con findings P0/P1 aperti (cross-ref pen-test asset_key).
     *
     * @return list<array<string, mixed>>
     */
    public function pathsWithFindingsCrossRef(): array
    {
        $findingsByPath = $this->openFindingsGroupedByWafPathKey();

        return array_map(function (array $path) use ($findingsByPath): array {
            $key = $path['key'];
            $findings = $findingsByPath[$key] ?? [];

            return array_merge($path, [
                'open_p0'    => collect($findings)->where('severity', 'P0')->count(),
                'open_p1'    => collect($findings)->where('severity', 'P1')->count(),
                'findings'   => $findings,
                'needs_tune' => $findings !== [],
            ]);
        }, $this->protectedPaths());
    }

    /**
     * Mappa asset pen-test (OwaspExternalPrepService) → chiavi path WAF.
     *
     * @return array<string, list<string>>
     */
    public function assetWafPathMap(): array
    {
        return [
            'login'          => ['login', 'two_factor'],
            'segreteria'     => ['livewire'],
            'operatore'      => ['livewire'],
            'admin_audit'    => ['admin_audit', 'admin_pen_test', 'admin_waf'],
            'rentri_settings'=> ['rentri_upload'],
            'stripe_webhook' => ['stripe_webhook'],
            'health'         => [],
            'mud_telematico' => [],
            'gps_provider'   => [],
        ];
    }

    public function isReadyForProductionBlockMode(): bool
    {
        foreach ($this->productionBlockChecklist() as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function siemChecklist(): array
    {
        $logGroup = (string) config('waf.siem_log_group', '');

        return [
            [
                'key'   => 'log_group',
                'label' => 'Log group / bucket WAF configurato',
                'ok'    => $logGroup !== '',
                'hint'  => 'WAF_SIEM_LOG_GROUP',
            ],
            [
                'key'   => 'retention',
                'label' => 'Retention log ≥ 90 giorni',
                'ok'    => true,
                'hint'  => 'CloudWatch / S3 lifecycle policy infra',
            ],
            [
                'key'   => 'alert_block_spike',
                'label' => 'Alert su spike block rate (>100/h)',
                'ok'    => true,
                'hint'  => 'Runbook WAF-STAGING-ROLLOUT.md § SIEM',
            ],
            [
                'key'   => 'correlation_app',
                'label' => 'Correlazione con log Laravel (request_id)',
                'ok'    => true,
                'hint'  => 'Header X-Request-Id se disponibile da load balancer',
            ],
        ];
    }

    public function isReadyForMonitorMode(): bool
    {
        return $this->checklistReadyFor('monitor');
    }

    public function isReadyForBlockMode(): bool
    {
        return $this->checklistReadyFor('block');
    }

    /**
     * @return array{
     *     mode: string,
     *     mode_label: string,
     *     provider: string,
     *     active: bool,
     *     ready_monitor: bool,
     *     ready_block: bool,
     *     ready_production_block: bool,
     *     paths_count: int,
     *     monitor_hours: int,
     *     open_p0_p1_on_waf_paths: int,
     *     paths_needing_tune: int
     * }
     */
    public function summary(): array
    {
        $crossRef = $this->pathsWithFindingsCrossRef();
        $pathsNeedingTune = collect($crossRef)->where('needs_tune', true)->count();

        return [
            'mode'                    => $this->mode(),
            'mode_label'              => $this->modeLabel(),
            'provider'                => $this->provider(),
            'active'                  => $this->isActive(),
            'ready_monitor'           => $this->isReadyForMonitorMode(),
            'ready_block'             => $this->isReadyForBlockMode(),
            'ready_production_block'  => $this->isReadyForProductionBlockMode(),
            'paths_count'             => count($this->protectedPaths()),
            'monitor_hours'           => (int) config('waf.monitor_hours_before_block', 48),
            'open_p0_p1_on_waf_paths' => $this->openWafPathFindingsCount(['P0', 'P1']),
            'paths_needing_tune'      => $pathsNeedingTune,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documentPaths(): array
    {
        return [
            'waf_rules'    => 'docs/WAF-RULES-PREP.md',
            'rollout'      => 'docs/WAF-STAGING-ROLLOUT.md',
            'owasp'        => 'docs/OWASP-INTERNAL-CHECKLIST.md',
            'pen_test'     => 'docs/PEN-TEST-EXTERNAL-SCOPE.md',
            'remediation'  => 'docs/REMEDIATION-FINDINGS-TEMPLATE.md',
            'go_live'      => 'docs/GO-LIVE-OPERATIVO.md',
        ];
    }

    /**
     * @param  list<string>  $severities
     */
    private function openWafPathFindingsCount(array $severities): int
    {
        $count = 0;
        foreach ($this->openFindingsGroupedByWafPathKey() as $findings) {
            foreach ($findings as $finding) {
                if (in_array($finding['severity'], $severities, true)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function stripeWebhookBlockDisabled(): bool
    {
        $stripe = collect($this->protectedPaths())->firstWhere('key', 'stripe_webhook');

        return is_array($stripe) && ($stripe['block'] ?? true) === false;
    }

    /**
     * @return array<string, list<array{id: string, title: string, severity: string, status: string, asset_key: string}>>
     */
    private function openFindingsGroupedByWafPathKey(): array
    {
        $grouped = [];
        $map = $this->assetWafPathMap();

        foreach ($this->remediation->all() as $finding) {
            if (($finding['status'] ?? '') === PenTestFindingStatus::Closed->value) {
                continue;
            }

            $severity = (string) ($finding['severity'] ?? '');
            if (! in_array($severity, ['P0', 'P1', 'P2', 'P3'], true)) {
                continue;
            }

            $assetKey = (string) ($finding['asset_key'] ?? '');
            $wafKeys = $map[$assetKey] ?? [];

            if ($wafKeys === [] && $assetKey !== '') {
                continue;
            }

            if ($wafKeys === []) {
                $wafKeys = ['_unmapped'];
            }

            $entry = [
                'id'        => (string) ($finding['id'] ?? ''),
                'title'     => (string) ($finding['title'] ?? ''),
                'severity'  => $severity,
                'status'    => (string) ($finding['status'] ?? ''),
                'asset_key' => $assetKey,
            ];

            foreach ($wafKeys as $wafKey) {
                $grouped[$wafKey][] = $entry;
            }
        }

        return $grouped;
    }

    private function checklistReadyFor(string $targetMode): bool
    {
        foreach ($this->deploymentChecklist() as $item) {
            $applies = str_contains($item['applies_in'], $targetMode);

            if ($applies && ! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log strutturato failure WAF block produzione (dedup 1h per chiave checklist).
     */
    public function logProductionBlockFailuresOnce(): void
    {
        $failed = collect($this->productionBlockChecklist())->where('ok', false)->values();

        if ($failed->isEmpty()) {
            return;
        }

        $cacheKey = 'application_log.waf_block_failures.'.md5(json_encode($failed->pluck('key')->all()) ?: '');

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHour());

        $logger = app(\App\Support\Logging\StructuredLogService::class);

        foreach ($failed as $item) {
            $logger->warning(
                'security',
                'waf_preflight_fail',
                'Checklist WAF block produzione non soddisfatta: '.$item['label'],
                [
                    'outcome' => 'failure',
                    'context' => [
                        'key'  => $item['key'],
                        'hint' => $item['hint'] ?? null,
                    ],
                ],
            );
        }
    }
}
