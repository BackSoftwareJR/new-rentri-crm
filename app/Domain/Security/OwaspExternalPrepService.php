<?php

namespace App\Domain\Security;

use App\Domain\Auth\TwoFactorEnforcementService;
use App\Domain\Ecommerce\StripeProductionPreflightService;
use App\Enums\PenTestFindingSeverity;

/**
 * Preparazione engagement pen-test OWASP esterno (Sprint 104).
 */
class OwaspExternalPrepService
{
    public function __construct(
        private readonly TwoFactorEnforcementService $twoFactorEnforcement,
        private readonly StripeProductionPreflightService $stripePreflight,
        private readonly PenTestRemediationService $remediation,
    ) {}

    /**
     * @return list<array{key: string, label: string, path: string, method: string, notes: string}>
     */
    public function scopeAssets(): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return [
            [
                'key'    => 'login',
                'label'  => 'Login + 2FA challenge',
                'path'   => '/login',
                'method' => 'GET/POST',
                'notes'  => 'Throttle 5/min; TOTP challenge post-login se 2FA attivo.',
            ],
            [
                'key'    => 'segreteria',
                'label'  => 'Area segreteria (Livewire)',
                'path'   => '/segreteria/*',
                'method' => 'GET',
                'notes'  => 'Middleware auth + role + demo.scope + two_factor.enforced.',
            ],
            [
                'key'    => 'operatore',
                'label'  => 'Area operatore',
                'path'   => '/operatore/*',
                'method' => 'GET',
                'notes'  => 'Ruolo operatore|admin|editor; 2FA non enforced.',
            ],
            [
                'key'    => 'admin_audit',
                'label'  => 'Admin audit export',
                'path'   => '/admin/audit',
                'method' => 'GET',
                'notes'  => 'Solo ruolo admin; export CSV con checksum.',
            ],
            [
                'key'    => 'rentri_settings',
                'label'  => 'Upload certificati RENTRI',
                'path'   => '/segreteria/impostazioni/rentri',
                'method' => 'POST (Livewire)',
                'notes'  => 'PKCS#12 whitelist; storage privato cifrato.',
            ],
            [
                'key'    => 'stripe_webhook',
                'label'  => 'Webhook Stripe e-commerce',
                'path'   => '/webhooks/stripe/ecommerce',
                'method' => 'POST',
                'notes'  => 'Firma Stripe-Signature; idempotency su stripe_event_id.',
            ],
            [
                'key'    => 'mud_telematico',
                'label'  => 'MUD telematico submit (outbound)',
                'path'   => '/mud/v1.0/dichiarazioni/trasmissione',
                'method' => 'POST (server-side)',
                'notes'  => 'Gateway RENTRI demoapi/api.rentri.gov.it — non endpoint pubblico CRM.',
            ],
            [
                'key'    => 'gps_provider',
                'label'  => 'GPS provider (outbound)',
                'path'   => config('services.trasporto_gps.provider_url', '/trasporti/{id}/position'),
                'method' => 'GET (server-side)',
                'notes'  => 'Bearer API key; adapter field map configurabile.',
            ],
            [
                'key'    => 'health',
                'label'  => 'Health check',
                'path'   => '/up',
                'method' => 'GET',
                'notes'  => 'Monitoraggio infra; no auth.',
            ],
        ];
    }

    /**
     * Template credenziali test per auditor (valori placeholder — sostituire pre-engagement).
     *
     * @return list<array{role: string, email: string, password: string, two_factor: string, notes: string}>
     */
    public function testAccountsTemplate(): array
    {
        return [
            [
                'role'       => 'admin',
                'email'      => 'pentest-admin@example.test',
                'password'   => '<generare-password-forte>',
                'two_factor' => 'TOTP seed dedicato (non produzione)',
                'notes'      => 'Accesso admin + audit + pen-test prep UI.',
            ],
            [
                'role'       => 'segreteria',
                'email'      => 'pentest-segreteria@example.test',
                'password'   => '<generare-password-forte>',
                'two_factor'   => 'TOTP seed dedicato',
                'notes'      => 'Wizard VFU, FIR, MUD, e-commerce, impostazioni RENTRI.',
            ],
            [
                'role'       => 'operatore',
                'email'      => 'pentest-operatore@example.test',
                'password'   => '<generare-password-forte>',
                'two_factor' => 'non richiesto',
                'notes'      => 'Bonifica, ricambi, vetrina — isolato da segreteria.',
            ],
            [
                'role'       => 'demo_readonly',
                'email'      => 'pentest-demo@example.test',
                'password'   => '<generare-password-forte>',
                'two_factor' => 'opzionale',
                'notes'      => 'Istanza demo APP_DEMO_MODE=true; no dati prod.',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, reason: string}>
     */
    public function outOfScopeItems(): array
    {
        return [
            [
                'key'    => 'mase_production',
                'label'  => 'API RENTRI/MASE produzione',
                'reason' => 'Traffico ministeriale reale — solo sandbox/demoapi in scope staging.',
            ],
            [
                'key'    => 'stripe_live_charges',
                'label'  => 'Addebiti Stripe produzione (sk_live_)',
                'reason' => 'Usare account Stripe test mode; no carte reali.',
            ],
            [
                'key'    => 'horizon_public',
                'label'  => 'Horizon queue dashboard',
                'reason' => 'Gate admin + IP interni; non esporre su Internet pubblico.',
            ],
            [
                'key'    => 'infra_waf',
                'label'  => 'Regole WAF edge (AWS/Cloudflare)',
                'reason' => 'Fuori scope app — Sprint 105 infra; fornire config read-only se richiesto.',
            ],
            [
                'key'    => 'third_party_smtp',
                'label'  => 'Relay SMTP produzione',
                'reason' => 'NOTIFICATIONS_LIVE=false in staging pen-test; log channel only.',
            ],
            [
                'key'    => 'gps_vendor_prod',
                'label'  => 'Contratto GPS provider reale',
                'reason' => 'Stub TRASPORTO_GPS_STUB=true; mock position endpoint fornito.',
            ],
            [
                'key'    => 'dos_ddos',
                'label'  => 'DoS / DDoS volumetrico',
                'reason' => 'Fuori scope applicativo — responsabilità infra/CDN.',
            ],
            [
                'key'    => 'social_engineering',
                'label'  => 'Social engineering / phishing',
                'reason' => 'Fuori scope technical assessment.',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function engagementChecklist(): array
    {
        $internalChecklist = base_path('docs/OWASP-INTERNAL-CHECKLIST.md');
        $scopeDoc = base_path('docs/PEN-TEST-EXTERNAL-SCOPE.md');
        $remediation = base_path('docs/REMEDIATION-FINDINGS-TEMPLATE.md');
        $twoFactorEnforced = $this->twoFactorEnforcement->isEnforcementEnabled();
        $stripeWebhook = (string) config('services.stripe.webhook_secret', '') !== '';

        return [
            [
                'key'   => 'internal_checklist',
                'label' => 'Checklist OWASP interna aggiornata (ciclo 9)',
                'ok'    => is_file($internalChecklist),
                'hint'  => 'docs/OWASP-INTERNAL-CHECKLIST.md',
            ],
            [
                'key'   => 'scope_document',
                'label' => 'Brief scope auditor (PEN-TEST-EXTERNAL-SCOPE.md)',
                'ok'    => is_file($scopeDoc),
                'hint'  => 'Condividere con vendor prima dell\'engagement.',
            ],
            [
                'key'   => 'remediation_template',
                'label' => 'Template remediation findings P0/P1/P2',
                'ok'    => is_file($remediation),
                'hint'  => 'docs/REMEDIATION-FINDINGS-TEMPLATE.md',
            ],
            [
                'key'   => 'staging_url',
                'label' => 'URL staging dedicato pen-test',
                'ok'    => ! str_contains((string) config('app.url'), 'localhost'),
                'hint'  => 'APP_URL su dominio staging isolato da produzione.',
            ],
            [
                'key'   => 'two_factor_enforced',
                'label' => '2FA enforced admin/segreteria (verificare bypass)',
                'ok'    => $twoFactorEnforced,
                'hint'  => 'TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA=true in staging target.',
            ],
            [
                'key'   => 'stripe_webhook_secret',
                'label' => 'Webhook Stripe configurato (test mode)',
                'ok'    => $stripeWebhook || (bool) config('services.ecommerce.payment_stub', true),
                'hint'  => 'STRIPE_WEBHOOK_SECRET o ECOMMERCE_PAYMENT_STUB=true.',
            ],
            [
                'key'   => 'demo_isolation',
                'label' => 'Istanza demo separata da prod',
                'ok'    => true,
                'hint'  => 'APP_DEMO_MODE per-deploy; vedi SECURITY-CHECKLIST-DEMO-PROD.md.',
            ],
            [
                'key'   => 'remediation_workflow',
                'label' => 'Workflow remediation findings operativo (UI + export)',
                'ok'    => is_file($remediation),
                'hint'  => 'Registro su /admin/pen-test-prep; export template markdown.',
            ],
            [
                'key'   => 'zero_p0_open',
                'label' => 'Zero findings P0 aperti (gate go-live)',
                'ok'    => $this->remediation->isGoLiveGateClear(),
                'hint'  => $this->remediation->openCountBySeverity(PenTestFindingSeverity::P0).' P0 aperti — chiudere prima produzione.',
            ],
        ];
    }

    /**
     * Checklist remediation collegata agli asset in scope.
     *
     * @return list<array{key: string, label: string, path: string, open_findings: int}>
     */
    public function scopeRemediationChecklist(): array
    {
        $openByAsset = [];
        foreach ($this->remediation->all() as $finding) {
            if (($finding['status'] ?? '') === 'closed') {
                continue;
            }
            $assetKey = (string) ($finding['asset_key'] ?? '_unassigned');
            $openByAsset[$assetKey] = ($openByAsset[$assetKey] ?? 0) + 1;
        }

        $checklist = [];
        foreach ($this->scopeAssets() as $asset) {
            $checklist[] = [
                'key'           => $asset['key'],
                'label'         => $asset['label'],
                'path'          => $asset['path'],
                'open_findings' => $openByAsset[$asset['key']] ?? 0,
            ];
        }

        if (($openByAsset['_unassigned'] ?? 0) > 0) {
            $checklist[] = [
                'key'           => '_unassigned',
                'label'         => 'Finding senza asset',
                'path'          => '—',
                'open_findings' => $openByAsset['_unassigned'],
            ];
        }

        return $checklist;
    }

    /**
     * @return array<string, string>
     */
    public function documentPaths(): array
    {
        return [
            'internal_checklist' => 'docs/OWASP-INTERNAL-CHECKLIST.md',
            'external_scope'     => 'docs/PEN-TEST-EXTERNAL-SCOPE.md',
            'remediation'        => 'docs/REMEDIATION-FINDINGS-TEMPLATE.md',
            'security_demo_prod' => 'docs/SECURITY-CHECKLIST-DEMO-PROD.md',
            'go_live_operativo'  => 'docs/GO-LIVE-OPERATIVO.md',
            'waf_prep'           => 'docs/WAF-RULES-PREP.md',
            'two_factor_runbook' => 'docs/2FA-PREP-RUNBOOK.md',
            'stripe_audit'       => 'docs/SPRINT-103-AUDIT-NOTES.md',
        ];
    }

    public function isReadyForEngagement(): bool
    {
        foreach ($this->engagementChecklist() as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     ready: bool,
     *     assets_count: int,
     *     out_of_scope_count: int,
     *     test_accounts_count: int,
     *     stripe_production: bool,
     *     two_factor_enforced: bool,
     *     remediation: array<string, mixed>
     * }
     */
    public function summary(): array
    {
        return [
            'ready'               => $this->isReadyForEngagement(),
            'assets_count'        => count($this->scopeAssets()),
            'out_of_scope_count'  => count($this->outOfScopeItems()),
            'test_accounts_count' => count($this->testAccountsTemplate()),
            'stripe_production'   => $this->stripePreflight->isProductionEnvironment(),
            'two_factor_enforced' => $this->twoFactorEnforcement->isEnforcementEnabled(),
            'remediation'         => $this->remediation->summary(),
        ];
    }
}
