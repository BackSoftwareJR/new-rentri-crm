# Sprint 112 — Audit notes: Post go-live monitoring SLA automation

**Data audit:** 4 giugno 2026 · **Ciclo 10**

---

## Implementazione

| Componente | Ruolo |
|------------|--------|
| `RentriSlaAlertService` | Valuta P95 + dead-letter rate vs `RENTRI_SLA_*`; cache ultimo check; activity log breach |
| `RentriSlaCheckCommand` | `rentri:sla-check --notify --json --days=7\|30` per cron |
| `RentriSlaBreachMail` | Email breach SLA fail |
| `NotificationEvent::RentriSlaBreach` | Evento hub notifiche |
| Hub `/segreteria/rentri` | Ultimo check + ultimi 5 breach da activity log |
| `routes/console.php` | Schedule hourly `rentri:sla-check --notify` (Europe/Rome) |

---

## Riferimenti

- [SPRINT-112-REVIEW-HANDOFF.md](SPRINT-112-REVIEW-HANDOFF.md)
- [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) §4
- [CICLO-10-PIANO.md](CICLO-10-PIANO.md)
