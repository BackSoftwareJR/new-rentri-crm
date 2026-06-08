# Monitoraggio Ciclo 3 — Health, dead-letter, alerting

Guida operativa per **monitoraggio base** post-deploy demo e produzione.  
Complementa [GO-LIVE-CICLO-3.md](GO-LIVE-CICLO-3.md) e [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md).

---

## 1. Endpoint health

| Endpoint | Uso | Note |
|----------|-----|------|
| `GET /up` | Health check Laravel (framework) | Registrato in `bootstrap/app.php`; usato da load balancer e preflight demo |
| `php artisan rentri:preflight` | Pre-deploy produzione | Cert mTLS, manifest Vite, DB |
| `php artisan rentri:preflight --demo` | Pre-deploy demo | Sandbox forzata, seed, `/up` |
| `php artisan rentri:monitor` | Snapshot operativo post-deploy | Dead-letter, retry, alert config |

### Integrazione load balancer / k8s

```yaml
# Esempio probe HTTP
path: /up
expect: 200
interval: 30s
timeout: 5s
```

In demo CI (GitHub Actions), `DemoPreflightService` e `Cycle3MonitoringService` verificano `/up` via request interna.

---

## 2. KPI dashboard segreteria

La dashboard `/segreteria` espone (sezione RENTRI):

| KPI | Fonte | Soglia alert |
|-----|-------|--------------|
| Movimenti da trasmettere | `RegistroMovimento` non trasmessi | > 0 → operativo |
| Chiamate API (totale) | `rentri_transazioni` scoped | informativo |
| Errori API | stato `errore`, no dead-letter | > 0 → giallo |
| **Dead-letter RENTRI** | `dead_letter_at IS NOT NULL` | **> 0 → rosso, intervento manuale** |
| **Retry pianificati** | `next_retry_at` futuro | > 0 → verificare queue |

Storico dettagliato: `/segreteria/rentri/transazioni` (KPI dedicati già presenti da Sprint 40).

---

## 3. Comando `rentri:monitor`

```bash
php artisan rentri:monitor          # output human-readable
php artisan rentri:monitor --json     # per cron / Datadog / PagerDuty
```

### Exit code

| Code | Significato |
|------|-------------|
| `0` | Nessun alert, o solo warning non critici assenti |
| `1` | Alert **critical** (health KO, dead-letter, demo su production env) |

### Alert emessi

| Code | Level | Condizione |
|------|-------|------------|
| `framework_health` | critical | `/up` non 200 |
| `rentri_dead_letter` | critical | `dead_letter > 0` |
| `rentri_retry_pending` | warning | `retry_pianificati > 0` |
| `demo_on_production_env` | critical | `APP_DEMO_MODE=true` + `APP_ENV=production` |
| `prod_on_demo_env` | warning | `APP_DEMO_MODE=false` + `APP_ENV=demo` |

### Esempio cron (produzione)

```cron
*/15 * * * * cd /var/www/rentri-crm && php artisan rentri:monitor --json >> /var/log/rentri-monitor.log 2>&1 || mail -s "RENTRI alert" ops@example.it < /var/log/rentri-monitor.log
```

---

## 4. Comando `rentri:sla-check` (Sprint 112)

Valutazione automatica **SLA RENTRI** vs soglie `RENTRI_SLA_*` (P95 latency, dead-letter rate). Complementa `rentri:monitor` con trend su periodo 7/30 gg e notifiche hub/email.

```bash
php artisan rentri:sla-check                    # valutazione human-readable
php artisan rentri:sla-check --json             # output JSON per cron / monitoring
php artisan rentri:sla-check --notify           # notifica breach fail + dead-letter nuovi
php artisan rentri:sla-check --days=30 --notify # periodo 30 giorni
```

### Exit code

| Code | Significato |
|------|-------------|
| `0` | Nessun breach **fail** (ok o solo warn) |
| `1` | Almeno un breach **fail** (P95 o dead-letter rate) |

### Soglie (`.env`)

| Variabile | Default | Uso |
|-----------|---------|-----|
| `RENTRI_SLA_P95_LATENCY_SECONDS` | 30 | P95 latency transazioni completate |
| `RENTRI_SLA_DEAD_LETTER_RATE_PERCENT` | 2.0 | % dead-letter sul periodo |
| `RENTRI_SLA_MAX_AVG_RETRY_COUNT` | 1.0 | Media retry (informativo in metriche) |

### Notifiche

| Evento | Quando |
|--------|--------|
| `rentri.sla_breach` | Breach **fail** su P95 o dead-letter rate — email `RentriSlaBreachMail` |
| `rentri.dead_letter` | Dead-letter nuovi dall'ultimo run (`--notify`) — email `RentriDeadLetterMail` |

Activity log: ogni breach fail → `SLA breach: {label}` (`log_name=rentri`). Hub `/segreteria/rentri` mostra ultimo check e ultimi 5 breach.

### Schedule Laravel (produzione)

Registrato in `routes/console.php`:

```php
Schedule::command('rentri:sla-check --notify')->hourly()->timezone('Europe/Rome');
```

### Esempio cron alternativo

```cron
0 * * * * cd /var/www/rentri-crm && php artisan rentri:sla-check --notify --json >> /var/log/rentri-sla.log 2>&1
```

---

## 5. Dead-letter RENTRI — runbook breve

1. Aprire `/segreteria/rentri/transazioni` → filtro errori / colonna Dead-letter.
2. Dettaglio transazione → leggere `response_json`, correlation id MASE.
3. Azioni:
   - **Errore dati** (4xx business): correggere payload, creare nuova operazione se necessario.
   - **Errore transiente esaurito**: «Riprova ora» se retry non più schedulato, oppure fix lato MASE/cert.
4. Dopo risoluzione: verificare `rentri:monitor` → dead-letter = 0.

Config retry: `RENTRI_RETRY_MAX_ATTEMPTS`, `RENTRI_RETRY_BASE_DELAY_SECONDS` (`.env.example`).

---

## 6. Horizon / queue

| Componente | Monitoraggio |
|------------|--------------|
| `RetryRentriTransazioneJob` | Horizon `/horizon` (admin) |
| Worker down | Retry non consumati → alert `rentri_retry_pending` persistente |
| Failed jobs | Tab Horizon + log `storage/logs/laravel.log` |
| **Scaling preflight (Sprint 107)** | `HorizonScalingPreflightService` — UI `/segreteria/impostazioni/notifiche` |

### Checklist Horizon produzione

| Controllo | Env / config |
|-----------|--------------|
| Queue Redis | `QUEUE_CONNECTION=redis` |
| Workers | `horizon.environments.production.maxProcesses` ≥ 3 |
| Notifiche async | `NOTIFICATIONS_QUEUE=true` se `NOTIFICATIONS_LIVE=true` |
| Failed jobs | 0 prima go-live volume |

Runbook: [HORIZON-SCALING-RUNBOOK.md](HORIZON-SCALING-RUNBOOK.md).

---

## 7. Demo vs produzione

| Istanza | Monitor consigliato |
|---------|---------------------|
| **Demo** | `rentri:preflight --demo --require-seed` post-deploy; `rentri:monitor` opzionale |
| **Produzione** | `/up` + `rentri:monitor` ogni 15 min; `rentri:sla-check --notify` ogni ora; alert dead-letter immediato |

Scope `is_demo`: i contatori dashboard rispettano `HasDemoScope` — prod e demo non mescolano metriche se deploy separati (consigliato).

### Notifiche email (Sprint 99 / 107)

| Modalità | Env | Monitoraggio |
|----------|-----|--------------|
| Stub | `NOTIFICATIONS_LIVE=false` | Log canale `notifications` (nessun SMTP) |
| Live sync | `NOTIFICATIONS_LIVE=true`, `NOTIFICATIONS_QUEUE=false` | OK per volume basso (< 50/giorno) |
| Live async | `NOTIFICATIONS_LIVE=true`, `NOTIFICATIONS_QUEUE=true` | **Consigliato produzione** — Horizon workers |

**SMTP volume (Sprint 107):** `SmtpVolumePreflightService` in hub notifiche.

| Controllo | Config |
|-----------|--------|
| Relay configurato | `MAIL_*` + test email UI |
| Rate limit doc | [HORIZON-SCALING-RUNBOOK.md](HORIZON-SCALING-RUNBOOK.md) § SMTP |
| Daily cap (opz.) | `NOTIFICATIONS_SMTP_DAILY_CAP` |
| Rate limit (opz.) | `NOTIFICATIONS_SMTP_RATE_LIMIT_PER_MINUTE` |

In produzione, dopo abilitazione SMTP live, eseguire «Invia email di test» da `/segreteria/impostazioni/notifiche` e controllare failed jobs / log se non arriva.

---

*Monitoraggio ciclo 3 — Sprint 45 (agg. Sprint 107 Horizon/SMTP volume, Sprint 112 SLA automation).*
