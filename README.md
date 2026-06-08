# RENTRI CRM (Autodemolizione)

CRM Laravel 12 per autodemolizione con integrazione RENTRI, magazzino rifiuti, VFU, FIR, MUD, e-commerce ricambi e migrazione dati legacy (stub MVP).

**Vertical slice MVP chiuso** — sprint 1–30 (giugno 2026).  
**Ciclo 2 produzione RENTRI/FIR chiuso** — sprint 31–35 (giugno 2026).  
**Ciclo 3 piattaforma demo + gap RENTRI chiuso** — sprint 36–45 (giugno 2026). Vedi [docs/GO-LIVE-CICLO-3.md](docs/GO-LIVE-CICLO-3.md).  
**Ciclo 4 palestra operativa chiuso** — sprint 46–50 (giugno 2026). Vedi [docs/CICLO-4-PIANO.md](docs/CICLO-4-PIANO.md) · [docs/GO-LIVE-CICLO-4.md](docs/GO-LIVE-CICLO-4.md).  
**Ciclo 5 perfezionamento 360° chiuso** — sprint 51–60 (giugno 2026). Vedi [docs/CICLO-5-PIANO-360.md](docs/CICLO-5-PIANO-360.md) · [docs/GO-LIVE-360.md](docs/GO-LIVE-360.md).  
**Ciclo 6 completamento verticale moduli chiuso** — sprint 61–75 (giugno 2026). Vedi [docs/CICLO-6-PIANO-MODULI-COMPLETI.md](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) · [docs/GO-LIVE-CICLO-6.md](docs/GO-LIVE-CICLO-6.md).  
**Ciclo 7 enterprise RENTRI/FIR chiuso** — sprint 76–90 (giugno 2026). Vedi [docs/CICLO-7-PIANO.md](docs/CICLO-7-PIANO.md) · [docs/GO-LIVE-ENTERPRISE.md](docs/GO-LIVE-ENTERPRISE.md).  
**Ciclo 8 validazione operativa reale chiuso** — sprint 91–100 (giugno 2026). Vedi [docs/CICLO-8-PIANO.md](docs/CICLO-8-PIANO.md) · [docs/GO-LIVE-OPERATIVO.md](docs/GO-LIVE-OPERATIVO.md).  
**Ciclo 9 produzione e gap infra chiuso** — sprint 101–110 (giugno 2026). Vedi [docs/CICLO-9-PIANO.md](docs/CICLO-9-PIANO.md) · [docs/GO-LIVE-PRODUZIONE.md](docs/GO-LIVE-PRODUZIONE.md).  
**Ciclo 10 RENTRI cert produzione chiuso** — sprint 111–120 (giugno 2026). Vedi [docs/CICLO-10-PIANO.md](docs/CICLO-10-PIANO.md) · [docs/GO-LIVE-CERT-PRODUZIONE.md](docs/GO-LIVE-CERT-PRODUZIONE.md).

## Release MVP — moduli

| Area | Route / entry | Stato MVP |
|------|---------------|-----------|
| Dashboard segreteria | `/segreteria` | KPI cross-modulo + widget migrazione legacy |
| VFU & bonifica | `/segreteria/vfu`, `/operatore/bonifica` | Accettazione, wizard bonifica, certificato PDF stub |
| Magazzino & registro | `/segreteria/magazzino`, registro movimenti | Carichi/scarichi, trasmissione RENTRI |
| Trasporti & FIR | Trasporti, FIR digitali | Vidima live, firma xFIR COSE, download payload |
| RENTRI | `/segreteria/rentri` | mTLS, vidima/registro async, onboarding + firma cert |
| MUD | `/segreteria/mud` | Dichiarazioni bozza, export JSON stub |
| E-commerce ricambi | `/segreteria/ecommerce`, `/operatore/ricambi` | Catalogo, ordini bozza, import legacy |
| Migrazione legacy | `rentri:import-legacy` | 5 entità fixture, report, audit, rollback doc |
| Audit admin | `/admin/audit` | Activity log (RENTRI, e-commerce, MUD, legacy) |
| Operatore mobile | `/operatore/*` | Bonifica, vetrina, email pericolosi stub |

## Ciclo 2 — Produzione RENTRI (sprint 31–35)

| Sprint | Feature | Doc |
|--------|---------|-----|
| 31 | Account + mTLS + test connessione | [SPRINT-31](docs/SPRINT-31-RENTRI-PRODUZIONE.md) |
| 32 | Vidima FIR async end-to-end | [SPRINT-32](docs/SPRINT-32-RENTRI-PRODUZIONE.md) |
| 33 | Trasmissione registro MASE | [SPRINT-33](docs/SPRINT-33-RENTRI-PRODUZIONE.md) |
| 34 | Firma COSE xFIR | [SPRINT-34](docs/SPRINT-34-RENTRI-PRODUZIONE.md) |
| 35 | Go-live + runbook | [SPRINT-35](docs/SPRINT-35-RENTRI-PRODUZIONE.md), [GO-LIVE-RENTRI](docs/GO-LIVE-RENTRI.md) |

## Ciclo 3 — Piattaforma demo + gap RENTRI (sprint 36–45)

| Sprint | Feature | Doc |
|--------|---------|-----|
| 36–37 | Demo mode, seed, walkthrough | [DEMO-DEPLOY](docs/DEMO-DEPLOY.md), [CICLO-3-PIANO](docs/CICLO-3-PIANO-COMPLETO.md) |
| 38–39 | XSD xFIR + invio firmato MASE | [CICLO-3-PIANO](docs/CICLO-3-PIANO-COMPLETO.md) |
| 40 | Retry MASE + dead-letter | [MONITORING-CICLO-3](docs/MONITORING-CICLO-3.md) |
| 41–42 | Conformità registro + FIR | [CICLO-3-PIANO](docs/CICLO-3-PIANO-COMPLETO.md) |
| 43–44 | Isolamento demo + CI staging | [DEMO-DEPLOY](docs/DEMO-DEPLOY.md) |
| 45 | Hardening + go-live ciclo 3 | [GO-LIVE-CICLO-3](docs/GO-LIVE-CICLO-3.md), [SECURITY-CHECKLIST](docs/SECURITY-CHECKLIST-DEMO-PROD.md) |

## Ciclo 4 — Palestra operativa (sprint 46–50) ✅ CHIUSO

| Sprint | Feature | Doc |
|--------|---------|-----|
| 46 | Toggle sessione + sandbox UI | [PALESTRA-OPERATIVA](docs/PALESTRA-OPERATIVA.md), [CICLO-4-PIANO](docs/CICLO-4-PIANO.md) |
| 47 | Isolamento verticale demo/prod | [GO-LIVE-CICLO-4](docs/GO-LIVE-CICLO-4.md) |
| 48 | Preset multi-operatore + UX walkthrough | [PALESTRA-OPERATIVA](docs/PALESTRA-OPERATIVA.md) |
| 49 | Playwright smoke + CI produzione | [RUNBOOK-POST-DEPLOY](docs/RUNBOOK-POST-DEPLOY.md) |
| 50 | UAT formazione + chiusura ciclo 4 | [UAT-FORMAZIONE-PALESTRA](docs/UAT-FORMAZIONE-PALESTRA.md) |

Palestra operativa: toggle sidebar → scope `is_demo=true` → sandbox MASE. Smoke E2E: `npm run test:e2e`.

## Ciclo 5 — Perfezionamento 360° (sprint 51–60) ✅ CHIUSO

| Sprint | Feature | Doc |
|--------|---------|-----|
| 51–54 | Design system, sicurezza, operatore mobile, demo isolation | [CICLO-5-PIANO-360](docs/CICLO-5-PIANO-360.md) |
| 55–57 | Wizard RENTRI, VFU timeline, cert rottamazione, 2FA prep | [2FA-PREP-RUNBOOK](docs/2FA-PREP-RUNBOOK.md) |
| 58–59 | Onboarding, a11y toast, tablet, print registro, WAF/k6 prep | [WAF-RULES-PREP](docs/WAF-RULES-PREP.md) |
| 60 | UAT UX 360°, GO-LIVE-360, axe/lighthouse, chiusura | [GO-LIVE-360](docs/GO-LIVE-360.md) |

UAT UX: [docs/UAT-UX-360-CHECKLIST.md](docs/UAT-UX-360-CHECKLIST.md) · A11y: [docs/A11Y-AUDIT-RUNBOOK.md](docs/A11Y-AUDIT-RUNBOOK.md) · Load smoke: `k6 run scripts/k6-smoke.js`

## Ciclo 6 — Completamento verticale moduli (sprint 61–75) ✅ CHIUSO

| Sprint | Modulo | Doc |
|--------|--------|-----|
| 61 | E-commerce (immagini, checkout, stati ordine) | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 62 | Anagrafiche (P.IVA/CF, alert autorizzazioni) | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 63–64 | VFU allegati, magazzino export, alert serbatoio | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 65–66 | MUD telematico prep, notifiche centralizzate | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 67–68 | 2FA TOTP slice, dashboard analytics KPI | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 69–70 | RENTRI prod hardening, trasporti/FIR polish | [GO-LIVE-RENTRI](docs/GO-LIVE-RENTRI.md) |
| 71–72 | Bonifica operatore, legacy sync incrementale | [CICLO-6-PIANO](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) |
| 73–74 | Audit export live, KPI Redis cache + k6 | [PERFORMANCE-MONITORING](docs/PERFORMANCE-MONITORING.md) |
| 75 | UAT moduli + GO-LIVE ciclo 6 | [GO-LIVE-CICLO-6](docs/GO-LIVE-CICLO-6.md) |

UAT moduli: [docs/UAT-CICLO-6-CHECKLIST.md](docs/UAT-CICLO-6-CHECKLIST.md) · Performance: [docs/PERFORMANCE-MONITORING.md](docs/PERFORMANCE-MONITORING.md) · Load autenticato: `k6 run scripts/k6-authenticated.js`

## Ciclo 7 — Enterprise RENTRI/FIR (sprint 76–90) ✅ CHIUSO

| Sprint | Focus | Doc |
|--------|-------|-----|
| 76 | Audit + remediation P0 (runtime, stub offline, COSE alg) | [CICLO-7-PIANO](docs/CICLO-7-PIANO.md) |
| 78 | Blocchi sync + preflight runtime | [CICLO-7-ENTERPRISE-AUDIT](docs/CICLO-7-ENTERPRISE-AUDIT.md) |
| 80 | Vidima validator service-layer | [CICLO-7-ENTERPRISE-AUDIT](docs/CICLO-7-ENTERPRISE-AUDIT.md) |
| 82 | Poll xFIR timeout config dedicato | [SPRINT-82-REVIEW-HANDOFF](docs/SPRINT-82-REVIEW-HANDOFF.md) |
| 84 | Contract test payload MASE | [SPRINT-84-REVIEW-HANDOFF](docs/SPRINT-84-REVIEW-HANDOFF.md) |
| 86 | UI badge stub/live | [SPRINT-86-REVIEW-HANDOFF](docs/SPRINT-86-REVIEW-HANDOFF.md) |
| 88 | COSE payload_firmato mapper | [SPRINT-88-AUDIT-NOTES](docs/SPRINT-88-AUDIT-NOTES.md) |
| 90 | Chiusura ciclo 7 GO-LIVE enterprise | [GO-LIVE-ENTERPRISE](docs/GO-LIVE-ENTERPRISE.md) |

Smoke enterprise: `php artisan test --filter=Sprint90` · Audit: [docs/CICLO-7-ENTERPRISE-AUDIT.md](docs/CICLO-7-ENTERPRISE-AUDIT.md)

## Ciclo 8 — Validazione operativa reale (sprint 91–100) ✅ CHIUSO

| Sprint | Focus | Doc |
|--------|-------|-----|
| 91 | Validazione cert sandbox MASE + wizard UI | [VALIDAZIONE-SANDBOX-MASE](docs/VALIDAZIONE-SANDBOX-MASE.md) |
| 92 | CI integration sandbox gated | [CICLO-8-PIANO](docs/CICLO-8-PIANO.md) |
| 93 | SLA dashboard RENTRI | [SPRINT-93-REVIEW-HANDOFF](docs/SPRINT-93-REVIEW-HANDOFF.md) |
| 94 | Vidima OpenAPI alignment | [SPRINT-94-AUDIT-NOTES](docs/SPRINT-94-AUDIT-NOTES.md) |
| 95 | MUD telematico live prep | [SPRINT-95-AUDIT-NOTES](docs/SPRINT-95-AUDIT-NOTES.md) |
| 96 | Stripe e-commerce gateway | [SPRINT-96-AUDIT-NOTES](docs/SPRINT-96-AUDIT-NOTES.md) |
| 97 | 2FA enforced admin/segreteria | [2FA-PREP-RUNBOOK](docs/2FA-PREP-RUNBOOK.md) |
| 98 | GPS tracking trasporti | [SPRINT-98-AUDIT-NOTES](docs/SPRINT-98-AUDIT-NOTES.md) |
| 99 | SMTP notifiche live | [SPRINT-99-AUDIT-NOTES](docs/SPRINT-99-AUDIT-NOTES.md) |
| 100 | Chiusura GO-LIVE-OPERATIVO | [GO-LIVE-OPERATIVO](docs/GO-LIVE-OPERATIVO.md) |

Smoke operativo: `php artisan test --filter=Sprint100` · Env checklist: [docs/GO-LIVE-OPERATIVO.md](docs/GO-LIVE-OPERATIVO.md)

## Ciclo 9 — Produzione e gap infra (sprint 101–110) ✅ CHIUSO

| Sprint | Focus | Doc |
|--------|-------|-----|
| 101 | MUD telematico endpoint MASE produzione | [SPRINT-101-AUDIT-NOTES](docs/SPRINT-101-AUDIT-NOTES.md) |
| 102 | GPS provider adapter + geofencing | [SPRINT-102-AUDIT-NOTES](docs/SPRINT-102-AUDIT-NOTES.md) |
| 103 | Stripe produzione onboarding + webhook | [SPRINT-103-AUDIT-NOTES](docs/SPRINT-103-AUDIT-NOTES.md) |
| 104 | Pen-test OWASP esterno prep | [PEN-TEST-EXTERNAL-SCOPE](docs/PEN-TEST-EXTERNAL-SCOPE.md) |
| 105 | WAF deploy attivo staging/prod | [WAF-STAGING-ROLLOUT](docs/WAF-STAGING-ROLLOUT.md) |
| 106 | RENTRI produzione switch + rollback | [RENTRI-PRODUCTION-SWITCH-RUNBOOK](docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md) |
| 107 | Horizon scaling + SMTP volume | [HORIZON-SCALING-RUNBOOK](docs/HORIZON-SCALING-RUNBOOK.md) |
| 108 | HA multi-istanza + backup drill | [HA-BACKUP-DRILL-RUNBOOK](docs/HA-BACKUP-DRILL-RUNBOOK.md) |
| 109 | KPI business dashboard v2 | [KPI-BUSINESS-DASHBOARD-V2](docs/KPI-BUSINESS-DASHBOARD-V2.md) |
| 110 | Chiusura GO-LIVE-PRODUZIONE | [GO-LIVE-PRODUZIONE](docs/GO-LIVE-PRODUZIONE.md) |

Smoke produzione: `php artisan test --filter=Sprint110` · Switch MASE: `php artisan rentri:production-switch-check --dry-run` · Admin: `/admin/pen-test-prep`, `/admin/waf-status`, `/admin/ha-status`

## Ciclo 10 — RENTRI cert produzione (sprint 111–120) ✅ CHIUSO

| Sprint | Focus | Doc |
|--------|-------|-----|
| 111 | RENTRI cert produzione E2E | [VALIDAZIONE-CERT-PRODUZIONE-RENTRI](docs/VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md) |
| 112 | SLA + dead-letter automation | [MONITORING-CICLO-3](docs/MONITORING-CICLO-3.md) |
| 113 | Pen-test remediation vendor | [SPRINT-113-AUDIT-NOTES](docs/SPRINT-113-AUDIT-NOTES.md) |
| 114 | WAF block mode tuning | [SPRINT-114-AUDIT-NOTES](docs/SPRINT-114-AUDIT-NOTES.md) |
| 115 | Operatore PWA + API mobile | [OPERATORE-PWA](docs/OPERATORE-PWA.md) |
| 116 | GPS provider produzione live | [GPS-PROVIDER-PRODUZIONE-RUNBOOK](docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md) |
| 117 | Stripe reconciliation prod | [STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK](docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md) |
| 118 | HA failover drill | [HA-FAILOVER-DRILL-RUNBOOK](docs/HA-FAILOVER-DRILL-RUNBOOK.md) |
| 119 | KPI business v3 + alert | [KPI-BUSINESS-DASHBOARD-V3](docs/KPI-BUSINESS-DASHBOARD-V3.md) |
| 120 | Chiusura GO-LIVE-CERT-PRODUZIONE | [GO-LIVE-CERT-PRODUZIONE](docs/GO-LIVE-CERT-PRODUZIONE.md) |

Smoke certificazione: `php artisan test --filter=Sprint120` · SLA: `rentri:sla-check --notify` · GPS: `trasporto:gps-switch-check` · KPI: `kpi:business-check --notify`

## Requirements

- PHP 8.2+
- Composer 2.x
- PostgreSQL 15+ (production) or SQLite (local smoke tests)
- Redis (recommended for queues / Horizon)

## Quick start (SQLite)

```bash
cd new-rentri-crm
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# Ensure .env has DB_CONNECTION=sqlite and other DB_* commented
php artisan migrate --seed
php artisan serve
```

Login: **admin@example.com** / **password** (role: `admin`).

## Comandi chiave

```bash
php artisan test                              # suite completa (~750 test)
php artisan test --filter=Sprint110           # chiusura ciclo 9 produzione
php artisan test --filter=Sprint100           # chiusura ciclo 8 operativo
php artisan test --filter=Sprint90            # chiusura ciclo 7 enterprise
php artisan test --filter=Sprint75            # chiusura ciclo 6
php artisan test --filter=Sprint60            # chiusura ciclo 5
php artisan rentri:production-switch-check --dry-run  # checklist switch MASE
php artisan rentri:preflight                  # check pre-deploy produzione
php artisan rentri:preflight --demo           # check pre-deploy demo
php artisan rentri:monitor                    # health + dead-letter KPI
npm run test:e2e                              # smoke Playwright palestra
php artisan rentri:demo-seed                  # fixture walkthrough (solo demo)
php artisan rentri:import-legacy --report     # riepilogo record legacy nel DB
php artisan rentri:import-legacy anagrafiche --dry-run
php artisan route:list
php artisan horizon                           # queue dashboard (admin)
composer run setup                            # install + migrate + seed (dopo .env)
```

## Documentazione operativa

| Documento | Contenuto |
|-----------|-----------|
| [docs/PRE-DEPLOY-CHECKLIST.md](docs/PRE-DEPLOY-CHECKLIST.md) | Checklist go/no-go, sequenza import legacy |
| [docs/DEPLOY-PRODUCTION.md](docs/DEPLOY-PRODUCTION.md) | Deploy staging/produzione, `.env.production.example` |
| [docs/GO-LIVE.md](docs/GO-LIVE.md) | Post-import, widget dashboard, riconciliazione magazzino |
| [docs/GO-LIVE-RENTRI.md](docs/GO-LIVE-RENTRI.md) | Go-live API RENTRI, preflight, smoke E2E, runbook |
| [docs/GO-LIVE-CICLO-3.md](docs/GO-LIVE-CICLO-3.md) | Chiusura ciclo 3 demo + prod, runbook integrato |
| [docs/DEMO-DEPLOY.md](docs/DEMO-DEPLOY.md) | Deploy istanza demo, CI staging |
| [docs/SECURITY-CHECKLIST-DEMO-PROD.md](docs/SECURITY-CHECKLIST-DEMO-PROD.md) | Pen-test checklist isolamento demo/prod |
| [docs/GO-LIVE-CICLO-4.md](docs/GO-LIVE-CICLO-4.md) | Chiusura ciclo 4 palestra operativa |
| [docs/PALESTRA-OPERATIVA.md](docs/PALESTRA-OPERATIVA.md) | Guida utente palestra operativa |
| [docs/UAT-FORMAZIONE-PALESTRA.md](docs/UAT-FORMAZIONE-PALESTRA.md) | Sessione formazione + checklist firmabile |
| [docs/RUNBOOK-POST-DEPLOY.md](docs/RUNBOOK-POST-DEPLOY.md) | Post-deploy: preflight, monitor, dead-letter |
| [docs/CICLO-5-PIANO-360.md](docs/CICLO-5-PIANO-360.md) | Piano sprint 51–60 (CHIUSO) |
| [docs/GO-LIVE-360.md](docs/GO-LIVE-360.md) | Go-live ciclo 5: security sign-off OWASP/WAF/2FA |
| [docs/CICLO-6-PIANO-MODULI-COMPLETI.md](docs/CICLO-6-PIANO-MODULI-COMPLETI.md) | Piano sprint 61–75 (CHIUSO) |
| [docs/GO-LIVE-CICLO-6.md](docs/GO-LIVE-CICLO-6.md) | Go-live ciclo 6: sign-off moduli verticali |
| [docs/CICLO-7-PIANO.md](docs/CICLO-7-PIANO.md) | Piano sprint 76–90 enterprise RENTRI (CHIUSO) |
| [docs/CICLO-7-ENTERPRISE-AUDIT.md](docs/CICLO-7-ENTERPRISE-AUDIT.md) | Audit conformità RENTRI/FIR ministeriale |
| [docs/GO-LIVE-ENTERPRISE.md](docs/GO-LIVE-ENTERPRISE.md) | Go-live ciclo 7: remediation P0–P2 enterprise |
| [docs/CICLO-8-PIANO.md](docs/CICLO-8-PIANO.md) | Piano sprint 91–100 validazione operativa (CHIUSO) |
| [docs/GO-LIVE-OPERATIVO.md](docs/GO-LIVE-OPERATIVO.md) | Go-live ciclo 8: checklist env unificata deploy |
| [docs/CICLO-9-PIANO.md](docs/CICLO-9-PIANO.md) | Piano sprint 101–110 produzione infra (CHIUSO) |
| [docs/GO-LIVE-PRODUZIONE.md](docs/GO-LIVE-PRODUZIONE.md) | Go-live ciclo 9: sign-off produzione + smoke |
| [docs/CICLO-10-PIANO.md](docs/CICLO-10-PIANO.md) | Piano sprint 111–120 cert produzione (CHIUSO) |
| [docs/GO-LIVE-CERT-PRODUZIONE.md](docs/GO-LIVE-CERT-PRODUZIONE.md) | Go-live ciclo 10: certificazione produzione E2E |
| [docs/CICLO-10-PIANO-STUB.md](docs/CICLO-10-PIANO-STUB.md) | Origine stub ciclo 10 (superseded) |
| [docs/CICLO-9-PIANO-STUB.md](docs/CICLO-9-PIANO-STUB.md) | Origine stub ciclo 9 (superseded) |
| [docs/UAT-CICLO-6-CHECKLIST.md](docs/UAT-CICLO-6-CHECKLIST.md) | UAT percorsi moduli sprint 61–74 |
| [docs/PERFORMANCE-MONITORING.md](docs/PERFORMANCE-MONITORING.md) | KPI cache Redis, k6 load, Horizon prep |
| [docs/UAT-UX-360-CHECKLIST.md](docs/UAT-UX-360-CHECKLIST.md) | UAT UX percorsi segreteria/operatore/RENTRI |
| [docs/A11Y-AUDIT-RUNBOOK.md](docs/A11Y-AUDIT-RUNBOOK.md) | Audit accessibilità axe |
| [docs/LIGHTHOUSE-BUDGET.md](docs/LIGHTHOUSE-BUDGET.md) | Soglie performance Lighthouse |
| [docs/OWASP-INTERNAL-CHECKLIST.md](docs/OWASP-INTERNAL-CHECKLIST.md) | Pen-test interno OWASP A01–A10 |
| [docs/WAF-RULES-PREP.md](docs/WAF-RULES-PREP.md) | Prep regole WAF staging/prod |
| [docs/2FA-PREP-RUNBOOK.md](docs/2FA-PREP-RUNBOOK.md) | Prep autenticazione a due fattori |
| [docs/CICLO-4-PIANO.md](docs/CICLO-4-PIANO.md) | Piano sprint 46–50 (CHIUSO) |
| [docs/MONITORING-CICLO-3.md](docs/MONITORING-CICLO-3.md) | Health `/up`, dead-letter, `rentri:monitor` |
| [docs/CICLO-3-PIANO-COMPLETO.md](docs/CICLO-3-PIANO-COMPLETO.md) | Piano sprint 36–45 |
| [docs/MIGRAZIONE-LEGACY.md](docs/MIGRAZIONE-LEGACY.md) | Mapping fixture, rollback manuale, `--report` |
| [docs/RENTRI_VERTICAL_BACKLOG.md](docs/RENTRI_VERTICAL_BACKLOG.md) | Backlog verticale e handoff sprint |

## PostgreSQL

In `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rentri_crm
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Then:

```bash
php artisan migrate --seed
```

## Roles

| Role        | Area        |
|------------|-------------|
| admin      | `/admin/*`, Horizon, all areas when combined with other middleware |
| editor     | Same as admin for route middleware |
| segreteria | `/segreteria/*` |
| operatore  | `/operatore/*` |

Seeder: `RolePermissionSeeder` creates roles and the default admin user.

## RENTRI configuration

```env
RENTRI_BASE_URL_SANDBOX=https://demoapi.rentri.gov.it
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_API_STUB=true          # false per go-live API ministeriale
RENTRI_FIRMA_STUB=true        # false per firma COSE xFIR reale
RENTRI_AUTH_MODE=mtls
RENTRI_INTEGRATION_TEST=false # true solo per test manuali con cert reale
```

Vedi [docs/GO-LIVE-RENTRI.md](docs/GO-LIVE-RENTRI.md) per checklist completa.

## Packages

- [Livewire](https://livewire.laravel.com) — UI modules under `app/Http/Livewire/`
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission) — roles
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) — audit
- [laravel/horizon](https://laravel.com/docs/horizon) — queue dashboard at `/horizon` (admin only)

## Domain layout

```
app/Domain/{Anagrafiche,Vfu,Bonifica,Magazzino,Trasporti,Fir,Rentri,Ecommerce,Mud,Legacy,Audit,Deploy}/
app/Services/Rentri/
app/Jobs/Rentri/
```

## Fuori scope (post ciclo 5)

- Connessione DB gestionale legacy live (solo fixture file)
- Rollback import automatico / sync magazzino post-import
- Deploy produzione infra (secrets, WAF attivo, TLS) — prep doc ✅
- Pen test OWASP **esterno** (checklist interna ✅)
- 2FA implementazione codice (runbook ✅)
- Pagamenti e-commerce, immagini ricambi, load test MASE reale
- UAT formazione firmata in sede (checklist ✅, esecuzione operativa)

## Horizon

Horizon is available at `/horizon`. Access is gated to users with the `admin` role. From the admin area, `/admin/horizon` redirects to `/horizon`.

## License

Proprietary — internal use.
