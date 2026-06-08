# Sprint 105 — Review handoff (agente Sprint 106)

**Destinatario:** agente Sprint 106 · **RENTRI produzione switch + rollback runbook**.

**Riferimenti:** [SPRINT-105-AUDIT-NOTES.md](SPRINT-105-AUDIT-NOTES.md) · [CICLO-9-PIANO.md](CICLO-9-PIANO.md)

---

## Cosa è stato implementato (Sprint 105)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Preflight WAF monitor/block | `WafDeploymentPreflightService.php` |
| 2 | WAF rules post-ciclo 9 | `docs/WAF-RULES-PREP.md` |
| 3 | Rollout runbook | `docs/WAF-STAGING-ROLLOUT.md` |
| 4 | Admin UI badge | `/admin/waf-status` |
| 5 | Config | `config/waf.php`, `.env.example` |
| 6 | Test Sprint 105 | `tests/Feature/Sprint105/*` (12 test) |

---

## Istruzione ESATTA agente Sprint 106

**RENTRI produzione switch + rollback runbook:**

1. Implementare **`RentriProductionSwitchService`** — checklist `RENTRI_ENV=production`, stub off, cert prod, preflight.
2. Doc **`RENTRI-PRODUCTION-SWITCH-RUNBOOK.md`** — sequenza switch, rollback `RENTRI_API_STUB=true`, monitor 48h.
3. UI badge su hub RENTRI / impostazioni — modalità sandbox vs produzione + link runbook.
4. Aggiornare **`GO-LIVE-RENTRI.md`** con gate post-WAF.
5. Test Sprint 106 ≥6; regression 704+ verdi.
6. `docs/SPRINT-106-REVIEW-HANDOFF.md` + aggiornare `CICLO-9-PIANO.md`.
7. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 106

1. Runbook switch produzione MASE documentato e testabile in preflight.
2. Rollback path verificato in test.
3. Suite test verde.
