# Horizon scaling + SMTP volume — Runbook

**Sprint 107 · Ciclo 9** · Scaling queue workers e limiti invio email.

**Verifica UI:** `/segreteria/impostazioni/notifiche` · Services: `HorizonScalingPreflightService`, `SmtpVolumePreflightService`.

---

## 1. Obiettivo

Garantire capacità di elaborazione job (RENTRI retry, notifiche email) e rispetto limiti SMTP relay in staging/produzione.

---

## 2. Configurazione consigliata produzione

```env
QUEUE_CONNECTION=redis
NOTIFICATIONS_LIVE=true
NOTIFICATIONS_QUEUE=true
MAIL_MAILER=smtp
MAIL_HOST=smtp.relay.example.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@operatore.it

# Opzionale — cap applicativo
NOTIFICATIONS_SMTP_RATE_LIMIT_PER_MINUTE=30
NOTIFICATIONS_SMTP_DAILY_CAP=500

# Horizon scaling
HORIZON_MIN_WORKERS=3
HORIZON_FAILED_JOBS_WARN=0
HORIZON_RETRY_WARN_THRESHOLD=10
```

**Horizon:** `config/horizon.php` → `environments.production.supervisor-1.maxProcesses` (default 10).

---

## 3. Scaling Horizon

### Staging

| Parametro | Valore |
|-----------|--------|
| `maxProcesses` | 3 |
| `QUEUE_CONNECTION` | redis |
| Supervisor | `php artisan horizon` (systemd/supervisor) |

### Produzione

| Parametro | Valore |
|-----------|--------|
| `maxProcesses` | 10 (auto-balance) |
| `balance` | auto |
| `NOTIFICATIONS_QUEUE` | true |

### Sequenza deploy scale-up

1. Verificare Redis raggiungibile.
2. `php artisan horizon:terminate` (graceful).
3. Aggiornare `maxProcesses` in config o env override.
4. Riavviare Horizon.
5. Monitorare `/horizon` — throughput jobs, failed tab.

### Rollback scale-down

1. Ridurre `maxProcesses`.
2. `horizon:terminate` + restart.
3. Verificare `rentri:monitor` — retry pending non in crescita.

---

## 4. SMTP volume limits

### Rate limit relay (provider)

| Provider tipo | Limite tipico | Azione |
|---------------|---------------|--------|
| Shared SMTP | 30–100 email/min | `NOTIFICATIONS_QUEUE=true` + worker ≥ 3 |
| Transactional (SendGrid/SES) | 1000+/min | Tune `maxProcesses` |
| On-prem relay | Variabile | Coordinare con IT |

**Regola:** non superare il rate limit del relay — usare coda async e backoff su errori 452/421 SMTP.

### Daily cap (opzionale app)

`NOTIFICATIONS_SMTP_DAILY_CAP=500` — documentato in config; enforcement lato app roadmap (preflight verifica presenza).

### Monitoraggio volume

| Segnale | Tool |
|---------|------|
| Email inviate | Log canale `notifications` |
| Failed SMTP | `failed_jobs` + Horizon |
| Dead-letter RENTRI | Hub RENTRI SLA |
| Queue depth | Horizon metrics / `jobs` table |

```bash
php artisan rentri:monitor --json
# failed_jobs count via UI notifiche
```

---

## 5. Checklist pre-produzione volume

- [ ] `HorizonScalingPreflightService` — tutte voci OK in UI
- [ ] `SmtpVolumePreflightService` — MAIL_* + NOTIFICATIONS_QUEUE
- [ ] Email di test da hub notifiche
- [ ] `failed_jobs` = 0
- [ ] Retry RENTRI sotto soglia
- [ ] Runbook condiviso con ops

---

## 6. Alert consigliati

| Alert | Soglia | Azione |
|-------|--------|--------|
| Failed jobs | > 0 | Horizon failed tab |
| Queue depth | > 100 pending 15 min | Scale workers |
| SMTP 5xx burst | > 10/min | Pause NOTIFICATIONS_LIVE |
| Retry RENTRI | > 10 pending | rentri:monitor |

---

## Riferimenti

- [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) §5–6
- [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) §2.8 SMTP
- [SPRINT-107-AUDIT-NOTES.md](SPRINT-107-AUDIT-NOTES.md)

---

*Runbook Sprint 107 — attivazione Horizon prod = team infra.*
