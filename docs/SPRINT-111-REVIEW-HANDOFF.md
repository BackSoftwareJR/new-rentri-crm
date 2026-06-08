# Sprint 111 — Review handoff (agente Sprint 112)

**Destinatario:** agente Sprint 112 · **Post go-live monitoring SLA + dead-letter automation**.

**Riferimenti:** [SPRINT-111-AUDIT-NOTES.md](SPRINT-111-AUDIT-NOTES.md) · [CICLO-10-PIANO.md](CICLO-10-PIANO.md)

---

## Cosa è stato implementato (Sprint 111)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Production cert validation service | `RentriProductionCertValidationService.php` |
| 2 | UI validazione produzione | `RentriSettings.php` + `rentri-settings.blade.php` |
| 3 | Integration test gated | `RentriProductionIntegrationTest.php` |
| 4 | Doc validazione | `VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md` |
| 5 | Piano ciclo 10 | `CICLO-10-PIANO.md` |
| 6 | Test Sprint 111 | `RentriProductionCertValidationTest.php` |

---

## Istruzione ESATTA agente Sprint 112

**Post go-live monitoring — alert SLA + dead-letter automation:**

1. Automazione alert quando SLA P95/dead-letter superano soglie `RENTRI_SLA_*`.
2. Notifiche hub/email su dead-letter nuovi (integrazione `RentriSlaMetricsService`).
3. Runbook operativo aggiornato in `MONITORING-CICLO-3.md`.
4. Test Sprint 112 ≥6; regression 760+ verdi.
5. `docs/SPRINT-112-REVIEW-HANDOFF.md` + aggiornare `CICLO-10-PIANO.md`.
6. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 112

1. Alert automatici configurabili per SLA RENTRI.
2. Dead-letter notification operativa.
3. Suite test verde.
