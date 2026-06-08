# Sprint 107 — Audit notes: Horizon scaling + SMTP volume

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## Implementazione

| Componente | Ruolo |
|------------|--------|
| `HorizonScalingPreflightService` | Workers, queue redis, NOTIFICATIONS_QUEUE, failed/retry count |
| `SmtpVolumePreflightService` | NOTIFICATIONS_LIVE, MAIL_*, rate limit doc, daily cap optional |
| UI notifiche | Badge Horizon + SMTP volume + checklist |
| `HORIZON-SCALING-RUNBOOK.md` | Scaling staging/prod, SMTP limits |

---

## Riferimenti

- [SPRINT-107-REVIEW-HANDOFF.md](SPRINT-107-REVIEW-HANDOFF.md)
