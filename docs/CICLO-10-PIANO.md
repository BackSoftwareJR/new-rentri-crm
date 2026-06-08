# Ciclo 10 — RENTRI cert produzione e hardening post go-live ✅ CHIUSO

**Sprint 111–120** · Partenza: ciclo 9 chiuso (750 test, [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)) · **Chiusura:** 847 test, [GO-LIVE-CERT-PRODUZIONE.md](GO-LIVE-CERT-PRODUZIONE.md)

**Obiettivo:** validazione certificato RENTRI produzione end-to-end, hardening post go-live, mobile app prep, chiusura GO-LIVE-CERT-PRODUZIONE.

**Pattern:** implement → audit notes → review handoff.

**Baseline:** [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md) · [CICLO-9-PIANO.md](CICLO-9-PIANO.md) (CHIUSO)

---

## Tabella sprint 111–120

| Sprint | Focus | Tipo | Stato |
|--------|-------|------|-------|
| **111** | RENTRI cert produzione — validazione E2E ministeriale | Ops | ✅ |
| **112** | Post go-live monitoring — alert SLA + dead-letter automation | Ops | ✅ |
| **113** | Pen-test remediation — chiusura findings vendor | Security | ✅ |
| **114** | WAF produzione block mode — tuning regole post-deploy | Infra | ✅ |
| **115** | Mobile app operatore — API prep + PWA shell | Feature | ✅ |
| **116** | GPS provider produzione — contratto fornitore live | Fix/Ops | ✅ |
| **117** | Stripe reconciliation prod — reporting + dispute stub | Ops | ✅ |
| **118** | HA failover drill — esercitazione multi-istanza | Infra | ✅ |
| **119** | Analytics KPI v3 — export CSV business + alert email | Feature | ✅ |
| **120** | Chiusura ciclo 10 GO-LIVE-CERT-PRODUZIONE | Docs | ✅ |

---

## Sprint 111 — ✅ completato

1. **`RentriProductionCertValidationService`** — checklist E2E produzione: env, solo api.rentri.gov.it, mTLS + firma, health, codifiche, vidima dry-run doc.
2. **UI RentriSettings** — sezione «Validazione certificato produzione» + runbook link.
3. **`RentriProductionIntegrationTest`** — gated `RENTRI_PRODUCTION_INTEGRATION_TEST` + cert path (mai CI default).
4. **Doc** — `VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md`.
5. **Test Sprint 111** — 10 test in `tests/Feature/Sprint111/*` (760 totali, 6 skipped).

### File principali

- `app/Domain/Rentri/RentriProductionCertValidationService.php`
- `docs/VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md`
- `tests/Feature/Sprint111/RentriProductionIntegrationTest.php`

---

## Sprint 112 — ✅ completato

1. **`RentriSlaAlertService`** — valutazione P95 + dead-letter rate vs `RENTRI_SLA_*`; cache ultimo check; activity log breach.
2. **`rentri:sla-check --notify`** — comando cron con output JSON; schedule hourly in `routes/console.php`.
3. **Notifiche** — `NotificationEvent::RentriSlaBreach` + dead-letter nuovi su `--notify`.
4. **Hub `/segreteria/rentri`** — ultimo check SLA + ultimi 5 breach.
5. **Doc** — `MONITORING-CICLO-3.md` §4.
6. **Test Sprint 112** — 9 test in `tests/Feature/Sprint112/*` (769 totali, 6 skipped).

### File principali

- `app/Domain/Rentri/RentriSlaAlertService.php`
- `app/Console/Commands/RentriSlaCheckCommand.php`
- `app/Mail/RentriSlaBreachMail.php`

---

## Sprint 113 — ✅ completato

1. **`PenTestRemediationService`** — CRUD findings vendor (JSON storage): id PT-XXX, severità P0–P3, owner, status open/in_progress/closed, evidenza, sprint_ref, asset_key.
2. **UI `/admin/pen-test-prep`** — lista findings, add/close, export markdown template remediation.
3. **`OwaspExternalPrepService`** — scope × findings aperti, gate zero P0, checklist remediation workflow.
4. **Doc** — `REMEDIATION-FINDINGS-TEMPLATE.md` aggiornato con UI Sprint 113.
5. **Test Sprint 113** — 9 test in `tests/Feature/Sprint113/*` (778 totali, 6 skipped).

Handoff: [SPRINT-113-REVIEW-HANDOFF.md](SPRINT-113-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Security/PenTestRemediationService.php`
- `app/Http/Livewire/Admin/PenTestPrepPage.php`
- `config/security.php`

---

## Sprint 114 — ✅ completato

1. **`WafDeploymentPreflightService`** — `productionBlockChecklist()`, cross-ref findings P0/P1 × path WAF, `modeToggleGuide()`, `tuningRunbookSteps()`.
2. **UI `/admin/waf-status`** — toggle monitor/block docs, runbook tuning, checklist block prod, tab findings correlati → pen-test prep.
3. **Integrazione `PenTestRemediationService`** — gate block prod su P0/P1 aperti su path mappati.
4. **Doc** — `WAF-STAGING-ROLLOUT.md`, `WAF-RULES-PREP.md` § cross-ref Sprint 114.
5. **Test Sprint 114** — 9 test in `tests/Feature/Sprint114/*` (787 totali, 6 skipped).

Handoff: [SPRINT-114-REVIEW-HANDOFF.md](SPRINT-114-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Security/WafDeploymentPreflightService.php`
- `resources/views/livewire/admin/waf-status.blade.php`

---

## Sprint 115 — ✅ completato

1. **`OperatoreMobileApiService`** — API JSON read-only bonifica, ricambi, vetrina; envelope `demo_mode`.
2. **Route `/operatore/api/*`** — auth operatore + `demo.scope`; policy allineate Livewire.
3. **PWA** — manifest, service worker, offline shell; layout operatore installabile.
4. **Doc** — `OPERATORE-PWA.md` strategia cache/offline.
5. **Test Sprint 115** — 9 test in `tests/Feature/Sprint115/*` (796 totali, 6 skipped).

Handoff: [SPRINT-115-REVIEW-HANDOFF.md](SPRINT-115-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Operatore/OperatoreMobileApiService.php`
- `app/Http/Controllers/Operatore/OperatoreApiController.php`
- `public/operatore-sw.js`
- `docs/OPERATORE-PWA.md`

---

## Sprint 116 — ✅ completato

1. **`TrasportoGpsProductionSwitchService`** — checklist switch stub→live, preset field map (`flat_default`, `nested_fleet`), probe provider, rollback.
2. **`trasporto:gps-switch-check`** — dry-run, `--probe`, `--json`.
3. **UI `/segreteria/trasporti`** — badge GPS, checklist switch, preset, rollback stub.
4. **Doc** — `GPS-PROVIDER-PRODUZIONE-RUNBOOK.md` contratto fornitore + fallback.
5. **Test Sprint 116** — 11 test in `tests/Feature/Sprint116/*` (807 totali, 6 skipped).

Handoff: [SPRINT-116-REVIEW-HANDOFF.md](SPRINT-116-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Trasporti/TrasportoGpsProductionSwitchService.php`
- `app/Console/Commands/TrasportoGpsSwitchCheckCommand.php`
- `docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md`

---

## Istruzione Sprint 117 — Stripe reconciliation prod

Vedi [SPRINT-116-REVIEW-HANDOFF.md](SPRINT-116-REVIEW-HANDOFF.md).

---

## Sprint 117 — ✅ completato

1. **`StripeProductionSwitchService`** — checklist switch sandbox→prod (chiavi, webhook, dispute).
2. **`StripeReconciliationReportService`** — matched/crm_only/stripe_only + export CSV hub.
3. **`StripeDisputeStubService`** — workflow dispute + webhook stub routing.
4. **`stripe:production-switch-check`** — dry-run, `--json`.
5. **UI `/segreteria/ecommerce`** — switch Stripe, riconciliazione, export CSV.
6. **Doc** — `STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md`.
7. **Test Sprint 117** — 11 test in `tests/Feature/Sprint117/*` (818 totali, 6 skipped).

Handoff: [SPRINT-117-REVIEW-HANDOFF.md](SPRINT-117-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Ecommerce/StripeProductionSwitchService.php`
- `app/Domain/Ecommerce/StripeReconciliationReportService.php`
- `app/Console/Commands/StripeProductionSwitchCheckCommand.php`
- `docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md`

---

## Istruzione Sprint 118 — HA failover drill

Vedi [SPRINT-117-REVIEW-HANDOFF.md](SPRINT-117-REVIEW-HANDOFF.md).

---

## Sprint 118 — ✅ completato

1. **`HaFailoverDrillService`** — checklist drill, fasi health/switch/recovery, probe `/up`, rollback.
2. **`ha:failover-drill`** — dry-run, `--probe`, `--json`.
3. **UI `/admin/ha-status`** — sezione esercitazione failover estesa.
4. **Doc** — `HA-FAILOVER-DRILL-RUNBOOK.md` failover + rollback post-drill.
5. **Test Sprint 118** — 10 test in `tests/Feature/Sprint118/*` (828 totali, 6 skipped).

Handoff: [SPRINT-118-REVIEW-HANDOFF.md](SPRINT-118-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Infrastructure/HaFailoverDrillService.php`
- `app/Console/Commands/HaFailoverDrillCommand.php`
- `docs/HA-FAILOVER-DRILL-RUNBOOK.md`

---

## Istruzione Sprint 119 — Analytics KPI v3

Vedi [SPRINT-118-REVIEW-HANDOFF.md](SPRINT-118-REVIEW-HANDOFF.md).

---

## Sprint 119 — ✅ completato

1. **`BusinessKpiExportService`** — export CSV metriche business + soglie.
2. **`BusinessKpiAlertService`** — breach detection, email, activity log.
3. **`kpi:business-check --notify`** — cron daily 07:30 Europe/Rome.
4. **UI** — dashboard v3 export CSV + alert status; admin audit banner.
5. **Doc** — `KPI-BUSINESS-DASHBOARD-V3.md`.
6. **Test Sprint 119** — 10 test in `tests/Feature/Sprint119/*` (838 totali, 6 skipped).

Handoff: [SPRINT-119-REVIEW-HANDOFF.md](SPRINT-119-REVIEW-HANDOFF.md).

### File principali

- `app/Domain/Dashboard/BusinessKpiExportService.php`
- `app/Domain/Dashboard/BusinessKpiAlertService.php`
- `app/Console/Commands/BusinessKpiCheckCommand.php`
- `docs/KPI-BUSINESS-DASHBOARD-V3.md`

---

## Istruzione Sprint 120 — Chiusura ciclo 10

Vedi [SPRINT-119-REVIEW-HANDOFF.md](SPRINT-119-REVIEW-HANDOFF.md).

---

## Sprint 120 — ✅ completato

1. **`GO-LIVE-CERT-PRODUZIONE.md`** — consolidamento sprint 111–119, checklist certificazione E2E, gap esterni.
2. **Piano e backlog** — ciclo 10 marcato CHIUSO.
3. **Test Sprint 120** — 9 test in `tests/Feature/Sprint120/*` (847 totali, 6 skipped).
4. **README** — link ciclo 10 + sign-off certificazione.

Handoff: [SPRINT-120-REVIEW-HANDOFF.md](SPRINT-120-REVIEW-HANDOFF.md).

### File principali

- `docs/GO-LIVE-CERT-PRODUZIONE.md`
- `tests/Feature/Sprint120/Cycle10ClosureGoLiveTest.php`

---

## Gap target ciclo 10 — tutti completati ✅

| Area | Sprint | Priorità |
|------|--------|----------|
| RENTRI cert prod E2E | 111 | P0 |
| SLA/dead-letter automation | 112 | P1 |
| Pen-test remediation | 113 | P1 |
| WAF block tuning | 114 | P1 |
| Mobile app PWA | 115 | P2 |
| GPS prod live | 116 | P2 |
| Stripe reconciliation | 117 | P2 |
| HA failover drill | 118 | P2 |
| KPI v3 alerts | 119 | P3 |
| Chiusura docs | 120 | — |

---

## Riferimenti

- [CICLO-10-PIANO-STUB.md](CICLO-10-PIANO-STUB.md) (origine stub)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §13
- [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md)
