# Sprint 106 — Review handoff (agente Sprint 107)

**Destinatario:** agente Sprint 107 · **Horizon scaling + SMTP volume**.

**Riferimenti:** [SPRINT-106-AUDIT-NOTES.md](SPRINT-106-AUDIT-NOTES.md) · [CICLO-9-PIANO.md](CICLO-9-PIANO.md)

---

## Cosa è stato implementato (Sprint 106)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Switch service unificato | `RentriProductionSwitchService.php` |
| 2 | Runbook switch/rollback | `docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md` |
| 3 | UI hub + step 4 | `Rentri.php`, `rentri-settings.blade.php` |
| 4 | CLI dry-run | `rentri:production-switch-check` |
| 5 | GO-LIVE-RENTRI post-WAF | gate aggiornato |
| 6 | Test Sprint 106 | `tests/Feature/Sprint106/*` (≥6 test) |

---

## Istruzione ESATTA agente Sprint 107

**Horizon scaling + SMTP volume:**

1. Implementare **`HorizonScalingPreflightService`** — worker count, queue notifiche, NOTIFICATIONS_QUEUE.
2. Doc **`HORIZON-SCALING-RUNBOOK.md`** — scaling staging/prod, SMTP volume limits.
3. UI hub notifiche — badge volume/queue + checklist Horizon.
4. Aggiornare **`MONITORING-CICLO-3.md`** § notifiche volume.
5. Test Sprint 107 ≥6; regression 716+ verdi.
6. `docs/SPRINT-107-REVIEW-HANDOFF.md` + aggiornare `CICLO-9-PIANO.md`.
7. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 107

1. Runbook Horizon/SMTP documentato.
2. Preflight service verificabile in test.
3. Suite test verde.
