# Sprint 104 — Audit notes: Pen-test OWASP esterno prep

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Gap pre-Sprint 104

| Aspetto | Pre-104 | Gap |
|---------|---------|-----|
| OWASP checklist | Ciclo 5 baseline | ❌ no 2FA enforce, Stripe webhook, MUD/GPS |
| Pen-test esterno | Solo menzione GO-LIVE | ❌ no scope doc, no account template |
| Remediation | Ad hoc | ❌ no template P0/P1/P2 |
| UI prep | — | ❌ no hub admin |

---

## 2. Implementazione

| Componente | Ruolo |
|------------|--------|
| `OwaspExternalPrepService` | Scope assets, test accounts, out-of-scope, checklist |
| `PenTestPrepPage` | UI admin `/admin/pen-test-prep` |
| `PEN-TEST-EXTERNAL-SCOPE.md` | Brief vendor |
| `REMEDIATION-FINDINGS-TEMPLATE.md` | Tracking findings |
| `OWASP-INTERNAL-CHECKLIST.md` | Aggiornato post-ciclo 9 |

---

## 3. Post-cycle 9 coverage OWASP

- **A07** — 2FA enforced admin/segreteria (`EnsureTwoFactorEnabled`)
- **A08** — Webhook Stripe idempotency (`stripe_webhook_events`)
- **A10** — MUD/GPS outbound endpoint configurati (no URL utente)

---

## Riferimenti

- [SPRINT-104-REVIEW-HANDOFF.md](SPRINT-104-REVIEW-HANDOFF.md)
- [PEN-TEST-EXTERNAL-SCOPE.md](PEN-TEST-EXTERNAL-SCOPE.md)
