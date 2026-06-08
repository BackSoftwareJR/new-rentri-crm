# Sprint 99 — Audit notes (SMTP notifiche live)

**Data:** 2026-06-04 · **Ciclo 8**

---

## Obiettivo

Passare da hub notifiche stub (solo log) a invio SMTP configurabile via `NOTIFICATIONS_LIVE`, con test invio da UI impostazioni.

---

## Implementazione

| Componente | Ruolo |
|------------|-------|
| `MailTransportRuntimeService` | stub/live, mailer effettivo, preflight MAIL_* |
| `NotificationService` | rispetta `shouldSendMail()`; audit log sempre |
| `NotificationTestMail` | mailable test da hub impostazioni |
| `NotificationSettingsPage` | badge, checklist SMTP, pulsante test |
| `config/notifications.php` | `NOTIFICATIONS_LIVE` |

### Env

- `NOTIFICATIONS_LIVE=false` (default) — nessun `Mail::send`, log su canale `notifications`
- `NOTIFICATIONS_LIVE=true` — `Mail::mailer(config('mail.default'))` (tipicamente `smtp`)
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`

### Retrocompatibilità

- `NOTIFICATIONS_DRIVER` legacy mantenuto in config; runtime guidato da `NOTIFICATIONS_LIVE`
- Sprint 66 (`NotificationHubTest`) invariato: default stub, mock Log

---

## Test

- `tests/Feature/Sprint99/NotificationSmtpLiveTest.php` — 8 test, `Mail::fake` su scenari live/stub

---

## Riferimenti

- [SPRINT-99-REVIEW-HANDOFF.md](SPRINT-99-REVIEW-HANDOFF.md)
- [CICLO-8-PIANO.md](CICLO-8-PIANO.md)
