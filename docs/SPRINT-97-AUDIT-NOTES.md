# Sprint 97 — Audit notes: 2FA enforced admin/segreteria

**Data:** 4 giugno 2026  
**Scope:** enforcement TOTP obbligatorio ruoli admin/segreteria.

---

## Gap pre-Sprint 97

2FA opt-in (Sprint 67) senza middleware enforcement — admin/segreteria potevano accedere senza TOTP configurato.

---

## Fix

| Componente | Ruolo |
|------------|--------|
| `TwoFactorEnforcementService` | Logica config, grace, ruoli |
| `EnsureTwoFactorEnabled` | Redirect → `/segreteria/impostazioni/sicurezza` |
| Banner UI | Grace period + pagina setup |
| Config | `TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA`, `TWO_FACTOR_ENFORCE_GRACE_UNTIL` |

---

## Route escluse dal blocco

- `segreteria.impostazioni.sicurezza`
- `logout` (fuori middleware group)

---

## Riferimenti

- [2FA-PREP-RUNBOOK.md](2FA-PREP-RUNBOOK.md) — Fase 2 enforced
- [SPRINT-97-REVIEW-HANDOFF.md](SPRINT-97-REVIEW-HANDOFF.md)
