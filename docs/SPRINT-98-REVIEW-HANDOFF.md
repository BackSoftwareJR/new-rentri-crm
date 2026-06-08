# Sprint 98 — Review handoff (agente Sprint 99)

**Destinatario:** agente Sprint 99 · **SMTP notifiche live**.

**Riferimenti:** [SPRINT-98-AUDIT-NOTES.md](SPRINT-98-AUDIT-NOTES.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 98)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Runtime mode stub/live | `TrasportoGpsRuntimeModeService.php` |
| 2 | GPS poll adapter | `TrasportoGpsTrackingService.php` |
| 3 | Migration posizione | `gps_last_position`, `gps_tracked_at` |
| 4 | Config env | `config/services.php`, `.env.example` |
| 5 | UI badge + mappa + refresh | `TrasportoShow`, `show.blade.php` |
| 6 | Test Sprint 98 | `tests/Feature/Sprint98/TrasportoGpsTrackingTest.php` (9 test) |

### Env chiave

- `TRASPORTO_GPS_STUB` (default `true`)
- `TRASPORTO_GPS_PROVIDER_URL`, `TRASPORTO_GPS_API_KEY`

---

## Istruzione ESATTA agente Sprint 99

**SMTP notifiche live + template:**

1. Audit `NotificationService` / mail attuale (log driver default) vs SMTP produzione.
2. **`MailTransportRuntimeService`** — stub (log/array) vs live SMTP configurabile.
3. Config `MAIL_*` production checklist + `NOTIFICATIONS_LIVE` flag.
4. UI impostazioni notifiche — badge stub/live + test invio email.
5. Test Sprint 99 ≥6.
6. `docs/SPRINT-99-REVIEW-HANDOFF.md` + `CICLO-8-PIANO.md`
7. No commit/push salvo richiesta utente

---

## Output atteso agente Sprint 99

1. SMTP live prep documentata + test invio sandbox.
2. Regression suite verde (641+ test attesi, 4 skipped).
3. Handoff Sprint 100 (chiusura ciclo 8 GO-LIVE-OPERATIVO).
