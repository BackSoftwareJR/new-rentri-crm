# Sprint 105 — Audit notes: WAF deploy staging/prod

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Gap pre-Sprint 105

| Aspetto | Pre-105 | Gap |
|---------|---------|-----|
| WAF rules | Sprint 59 doc only | ❌ no path ciclo 9, no mode env |
| Rollout | Suggerimento generico | ❌ no runbook 48h → block |
| UI status | — | ❌ no badge monitor/block |
| Preflight | — | ❌ no checklist verificabile |

---

## 2. Implementazione

| Componente | Ruolo |
|------------|--------|
| `WafDeploymentPreflightService` | Mode off/monitor/block, path, checklist, SIEM |
| `WafStatusPage` | UI admin `/admin/waf-status` |
| `WAF-RULES-PREP.md` | Aggiornato Stripe, Livewire, admin |
| `WAF-STAGING-ROLLOUT.md` | 48h monitor → block, rollback, SIEM |

---

## 3. Config

```env
WAF_MODE=off          # locale
WAF_MODE=monitor      # staging fase 1
WAF_MODE=block        # post finestra monitor
WAF_SIEM_LOG_GROUP=/aws/waf/rentri-crm-staging
WAF_MONITOR_HOURS_BEFORE_BLOCK=48
```

Attivazione regole edge: **team infra** (fuori scope CRM).

---

## Riferimenti

- [SPRINT-105-REVIEW-HANDOFF.md](SPRINT-105-REVIEW-HANDOFF.md)
- [WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md)
