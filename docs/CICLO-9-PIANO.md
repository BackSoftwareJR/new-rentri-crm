# Ciclo 9 — Produzione e gap infra post-operativo ✅ CHIUSO

**Sprint 101–110** · Partenza: ciclo 8 chiuso (657 test, [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md)) · **Chiusura:** 750 test, [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)

**Obiettivo:** chiudere gap residui post-ciclo 8 (contratti MASE/GPS/Stripe, security infra, RENTRI prod switch) e consolidare go-live produzione.

**Pattern:** implement → audit notes → review handoff (come ciclo 8).

**Baseline:** [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md) (CHIUSO)

---

## Tabella sprint 101–110

| Sprint | Focus | Tipo | Stato |
|--------|-------|------|-------|
| **101** | MUD telematico endpoint MASE produzione | Fix/Ops | ✅ |
| **102** | GPS provider adapter + geofencing alert | Fix | ✅ |
| **103** | Stripe produzione onboarding + webhook prod | Ops | ✅ |
| **104** | Pen-test OWASP esterno + remediation | Security | ✅ |
| **105** | WAF deploy attivo staging/prod | Infra | ✅ |
| **106** | RENTRI produzione switch + rollback runbook | Ops | ✅ |
| **107** | Horizon scaling + SMTP volume | Infra | ✅ |
| **108** | HA multi-istanza + backup drill | Infra | ✅ |
| **109** | Analytics KPI business dashboard v2 | Feature | ✅ |
| **110** | Chiusura ciclo 9 GO-LIVE-PRODUZIONE | Docs | ✅ |

---

## Sprint 101 — ✅ completato

1. **`MudTelematicoEndpoints`** — sandbox `demoapi.rentri.gov.it` / prod `api.rentri.gov.it`; path `/mud/v1.0/dichiarazioni/*`.
2. **Research** — portale ufficiale `mudtelematico.it` (SPID); API CRM via gateway RENTRI-aligned.
3. **`MudTelematicoTransmissionService`** — HTTP live via endpoints + result query param.
4. **UI `MudShow`** — submit URL, portale MASE, probe HEAD reachability.
5. **Test Sprint 101** — 7 test in `tests/Feature/Sprint101/*` (664 totali, 4 skipped).

### File principali

- `app/Domain/Mud/MudTelematicoEndpoints.php`
- `docs/SPRINT-101-AUDIT-NOTES.md`
- `tests/fixtures/mud/mase-invio-submit.json`

---

## Sprint 102 — ✅ completato

1. **`TrasportoGpsProviderAdapter`** — field map configurabile (flat + nested dot notation).
2. **`TrasportoGpsPreflightService`** — checklist URL + API key su TrasportoShow.
3. **`TrasportoGpsGeofenceService`** — alert hub se distanza > raggio km (destinazione stub).
4. **Fixture** — varianti provider flat/nested in `position-response.json`.
5. **Test Sprint 102** — 8 test in `tests/Feature/Sprint102/*` (672 totali, 4 skipped).

### File principali

- `app/Domain/Trasporti/TrasportoGpsProviderAdapter.php`
- `docs/SPRINT-102-AUDIT-NOTES.md`

---

## Sprint 103 — ✅ completato

1. **`StripeProductionPreflightService`** — sk_live/sk_test, STRIPE_LIVE_MODE, webhook, EUR.
2. **Badge sandbox/produzione** — UI carrello/ordine + link dashboard Stripe.
3. **Idempotency webhook** — tabella `stripe_webhook_events`, guard replay.
4. **Riconciliazione** — log + `EcommerceStripeReconciliationMail` via hub notifiche.
5. **Test Sprint 103** — 8 test in `tests/Feature/Sprint103/*` (680 totali, 4 skipped).

### File principali

- `app/Domain/Ecommerce/StripeProductionPreflightService.php`
- `database/migrations/2026_06_10_100000_stripe_webhook_events_sprint103.php`
- `docs/SPRINT-103-AUDIT-NOTES.md`

---

## Sprint 104 — ✅ completato

1. **`OwaspExternalPrepService`** — scope assets, test accounts template, out-of-scope, checklist engagement.
2. **Docs** — `PEN-TEST-EXTERNAL-SCOPE.md`, `REMEDIATION-FINDINGS-TEMPLATE.md`.
3. **OWASP checklist** — aggiornata 2FA, Stripe webhook, MUD/GPS (ciclo 9).
4. **Admin UI** — `/admin/pen-test-prep`.
5. **Test Sprint 104** — 12 test in `tests/Feature/Sprint104/*` (692 totali, 4 skipped).

### File principali

- `app/Domain/Security/OwaspExternalPrepService.php`
- `docs/SPRINT-104-AUDIT-NOTES.md`

---

## Sprint 105 — ✅ completato

1. **`WafDeploymentPreflightService`** — WAF_MODE off/monitor/block, path Stripe/Livewire/admin, SIEM checklist.
2. **Docs** — `WAF-RULES-PREP.md` aggiornato, `WAF-STAGING-ROLLOUT.md` (48h → block, rollback).
3. **Admin UI** — `/admin/waf-status` + nav da pen-test-prep.
4. **Config** — `config/waf.php`, `.env.example`.
5. **Test Sprint 105** — 12 test in `tests/Feature/Sprint105/*` (704 totali, 4 skipped).

### File principali

- `app/Domain/Security/WafDeploymentPreflightService.php`
- `docs/WAF-STAGING-ROLLOUT.md`

---

## Sprint 106 — ✅ completato

1. **`RentriProductionSwitchService`** — checklist unificata env + UI + preflight + WAF opt.
2. **Runbook** — `RENTRI-PRODUCTION-SWITCH-RUNBOOK.md` (switch, 48h monitor, rollback, activity log).
3. **CLI** — `rentri:production-switch-check --dry-run`.
4. **UI** — hub RENTRI + Impostazioni step 4 enhanced.
5. **Test Sprint 106** — 10 test in `tests/Feature/Sprint106/*` (714 totali, 4 skipped).

### File principali

- `app/Domain/Rentri/RentriProductionSwitchService.php`
- `docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md`

---

## Sprint 107 — ✅ completato

1. **`HorizonScalingPreflightService`** — workers, queue redis, NOTIFICATIONS_QUEUE, failed/retry count.
2. **`SmtpVolumePreflightService`** — NOTIFICATIONS_LIVE, MAIL_*, rate limit doc, daily cap optional.
3. **Runbook** — `HORIZON-SCALING-RUNBOOK.md`.
4. **UI notifiche** — badge Horizon + SMTP volume + checklist.
5. **Test Sprint 107** — 10 test in `tests/Feature/Sprint107/*` (724 totali, 4 skipped).

### File principali

- `app/Domain/Infrastructure/HorizonScalingPreflightService.php`
- `app/Domain/Notifications/SmtpVolumePreflightService.php`

---

## Sprint 108 — ✅ completato

1. **`HaBackupPreflightService`** — backup schedule, restore drill, Redis session, RPO/RTO.
2. **Runbook** — `HA-BACKUP-DRILL-RUNBOOK.md` (quarterly restore, failover).
3. **Admin UI** — `/admin/ha-status`.
4. **REDIS-SESSION-PREP** — § multi-istanza HA.
5. **Test Sprint 108** — 10 test in `tests/Feature/Sprint108/*` (734 totali, 4 skipped).

### File principali

- `app/Domain/Infrastructure/HaBackupPreflightService.php`
- `docs/HA-BACKUP-DRILL-RUNBOOK.md`

---

## Sprint 109 — ✅ completato

1. **`BusinessKpiDashboardService`** — ordini confermati, VFU accettate, movimenti kg, revenue stub, trend 7/30 gg vs periodo precedente.
2. **Widget v2** — sezione KPI business su dashboard segreteria con drill-down e soglie colore.
3. **Doc** — `KPI-BUSINESS-DASHBOARD-V2.md` (metriche + threshold).
4. **Config** — `config/dashboard.php` → `business_kpi.thresholds`.
5. **Test Sprint 109** — 8 test in `tests/Feature/Sprint109/*` (742 totali, 4 skipped).

### File principali

- `app/Domain/Dashboard/BusinessKpiDashboardService.php`
- `docs/KPI-BUSINESS-DASHBOARD-V2.md`

---

## Sprint 110 — ✅ completato

1. **`GO-LIVE-PRODUZIONE.md`** — consolidamento deliverable 101–109, go/no-go unificato, smoke commands.
2. **Ciclo 9 CHIUSO** — banner piano + backlog §12.
3. **`CICLO-10-PIANO-STUB.md`** — outline sprint 111–120.
4. **README** — sezione ciclo 9 + link GO-LIVE-PRODUZIONE.
5. **Test Sprint 110** — 8 test in `tests/Feature/Sprint110/*` (750 totali, 4 skipped).

### File principali

- `docs/GO-LIVE-PRODUZIONE.md`
- `docs/CICLO-10-PIANO-STUB.md`
- `docs/SPRINT-110-REVIEW-HANDOFF.md`

---

## Gap target ciclo 9 (completati)

| Area | Sprint | Priorità |
|------|--------|----------|
| MUD endpoint MASE | 101 | P1 normativa |
| GPS provider reale | 102 | P2 ops |
| Stripe produzione | 103 | P2 business |
| Pen-test esterno | 104 | P1 security |
| WAF attivo | 105 | P1 infra |
| RENTRI prod switch | 106 | P0 |
| SMTP/queue volume | 107 | P2 |
| HA / backup | 108 | P2 |
| KPI business v2 | 109 | P3 |
| Chiusura docs | 110 | — |

---

## Riferimenti

- [CICLO-9-PIANO-STUB.md](CICLO-9-PIANO-STUB.md) (origine stub)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §12
- [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md) · chiusura ciclo 9
- [CICLO-10-PIANO-STUB.md](CICLO-10-PIANO-STUB.md) · prossimo ciclo
