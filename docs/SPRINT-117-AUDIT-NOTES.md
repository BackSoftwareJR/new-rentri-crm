# Sprint 117 — Audit notes

**Focus:** Stripe reconciliation prod — switch test/live, reporting CRM vs webhook, dispute stub.

---

## Deliverable verificati

| # | Item | Esito |
|---|------|-------|
| 1 | `StripeProductionSwitchService` — checklist unificata switch prod | ✅ |
| 2 | `StripeProductionPreflightService` — webhook URL (esteso Sprint 103) | ✅ |
| 3 | `StripeReconciliationReportService` — summary, rows, CSV export | ✅ |
| 4 | `StripeDisputeStubService` — workflow + handle dispute webhook | ✅ |
| 5 | `stripe:production-switch-check` — dry-run, `--json` | ✅ |
| 6 | UI `/segreteria/ecommerce` — switch, reconciliation, export | ✅ |
| 7 | Runbook | `STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md` ✅ |
| 8 | Test Sprint 117 ≥6 | ✅ |

---

## Config aggiuntiva

- `STRIPE_DISPUTE_WEBHOOK_SECRET`
- `STRIPE_DISPUTE_STUB` (default true)
- `STRIPE_RECONCILIATION_DAYS` (default 30)

---

## Regressioni

Baseline Sprint 116: 807 test, 6 skipped. Sprint 117: **818 test**, 6 skipped, 11 nuovi in `Sprint117/`.
