# Sprint 117 — Review handoff (agente Sprint 118)

**Destinatario:** agente Sprint 118 · **HA failover drill — esercitazione multi-istanza**.

**Riferimenti:** [SPRINT-117-AUDIT-NOTES.md](SPRINT-117-AUDIT-NOTES.md) · [CICLO-10-PIANO.md](CICLO-10-PIANO.md) · [STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md](STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md)

---

## Cosa è stato implementato (Sprint 117)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Switch service Stripe prod | `StripeProductionSwitchService.php` |
| 2 | Reporting reconciliation | `StripeReconciliationReportService.php` |
| 3 | Dispute stub workflow | `StripeDisputeStubService.php` |
| 4 | Comando preflight/switch | `StripeProductionSwitchCheckCommand.php` |
| 5 | UI hub e-commerce | `EcommerceIndex.php`, `ecommerce/index.blade.php` |
| 6 | Webhook dispute routing | `EcommercePaymentGatewayService.php` |
| 7 | Runbook | `docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md` |
| 8 | Test Sprint 117 | `tests/Feature/Sprint117/StripeProductionReconciliationTest.php` |

---

## Istruzione ESATTA agente Sprint 118

**HA failover drill — esercitazione multi-istanza:**

1. Servizio drill failover (health, switch traffic, recovery checklist).
2. Comando artisan e/o UI admin per esercitazione documentata.
3. Runbook HA failover + rollback post-drill.
4. Test Sprint 118 ≥6; regression suite Sprint 117+ verdi.
5. `docs/SPRINT-118-REVIEW-HANDOFF.md` + aggiornare `CICLO-10-PIANO.md`.
6. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 118

1. Drill failover eseguibile in staging con checklist verificabile.
2. Documentazione recovery e rollback post-esercitazione.
3. Suite test verde.

---

## Note per Sprint 118

- Pattern di riferimento: switch/checklist Sprint 106/116/117.
- Backlog: [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §13 sprint 118.
- Baseline test post-117: vedi audit notes.
