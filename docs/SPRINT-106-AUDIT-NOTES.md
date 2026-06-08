# Sprint 106 — Audit notes: RENTRI production switch

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Gap pre-Sprint 106

| Aspetto | Pre-106 | Gap |
|---------|---------|-----|
| Switch prod | UI step 4 (6 voci) | ❌ no checklist env unificata |
| Rollback | Menzionato in docs | ❌ no runbook sequenziato |
| CLI | solo rentri:preflight | ❌ no production-switch-check |
| Hub RENTRI | stub banner | ❌ no status switch |

---

## 2. Implementazione

| Componente | Ruolo |
|------------|--------|
| `RentriProductionSwitchService` | Checklist env + UI + preflight + WAF opt |
| `rentri:production-switch-check` | Dry-run report CLI |
| `RENTRI-PRODUCTION-SWITCH-RUNBOOK.md` | Switch, 48h monitor, rollback |
| UI step 4 + hub | Checklist unificata + runbook link |

---

## Riferimenti

- [SPRINT-106-REVIEW-HANDOFF.md](SPRINT-106-REVIEW-HANDOFF.md)
- [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md)
