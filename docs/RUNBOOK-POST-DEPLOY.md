# Runbook post-deploy — Produzione e staging

Procedure operative **dopo ogni deploy** su istanza staging o produzione RENTRI CRM.  
Complementa [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md), [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) e [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md).

---

## 1. Sequenza post-deploy (obbligatoria)

```bash
cd /var/www/rentri-crm   # path deploy

# 1. Build asset (se non in pipeline)
npm ci && npm run build

# 2. Migrazioni
php artisan migrate --force

# 3. Preflight
php artisan rentri:preflight              # produzione
# php artisan rentri:preflight --demo     # istanza demo deploy

# 4. Monitor snapshot
php artisan rentri:monitor

# 5. Smoke opzionale palestra (staging con ALLOW_SESSION_DEMO)
npm run test:e2e
```

**Gate go-live:** preflight **0 fail** + monitor **exit 0** (nessun alert critical).

---

## 2. Comando `rentri:monitor`

### Invocazione

```bash
php artisan rentri:monitor           # output human-readable
php artisan rentri:monitor --json      # integrazione cron / Datadog / PagerDuty
```

### Exit code

| Code | Significato | Azione |
|------|-------------|--------|
| **0** | Nessun alert critical | OK — registrare in log deploy |
| **1** | Alert **critical** | Escalation immediata (§4) |

### Alert emessi

| Code | Level | Condizione |
|------|-------|------------|
| `framework_health` | critical | `GET /up` ≠ 200 |
| `rentri_dead_letter` | critical | Transazioni RENTRI con `dead_letter_at` |
| `rentri_retry_pending` | warning | Retry pianificati in coda |
| `demo_on_production_env` | critical | `APP_DEMO_MODE=true` + `APP_ENV=production` |
| `prod_on_demo_env` | warning | `APP_DEMO_MODE=false` + `APP_ENV=demo` |

### Cron consigliato (produzione)

```cron
*/15 * * * * cd /var/www/rentri-crm && php artisan rentri:monitor --json >> /var/log/rentri-monitor.log 2>&1 || /usr/local/bin/escalate-rentri-alert.sh
```

---

## 3. Dead-letter RENTRI — gestione

### Rilevazione

1. `php artisan rentri:monitor` → alert `rentri_dead_letter`
2. Dashboard `/segreteria` → KPI **Dead-letter RENTRI** > 0 (rosso)
3. Storico `/segreteria/rentri/transazioni` → filtro errori / badge Dead-letter

### Runbook operativo

| Step | Azione |
|------|--------|
| 1 | Aprire dettaglio transazione → `response_json`, correlation ID MASE |
| 2 | Classificare errore: **4xx business** vs **5xx/transiente esaurito** |
| 3a | 4xx: correggere payload/dati; nuova operazione se necessario |
| 3b | 5xx esaurito: «Riprova ora» in UI o verifica stato MASE/certificato |
| 4 | Confermare `rentri:monitor` → dead-letter = 0 |
| 5 | Documentare in ticket interno |

### Config retry

Variabili `.env`: `RENTRI_RETRY_MAX_ATTEMPTS`, `RENTRI_RETRY_BASE_DELAY_SECONDS`.  
Job: `RetryRentriTransazioneJob` — visibile in Horizon `/horizon` (admin).

---

## 4. Escalation

| Severità | Condizione | Contatto | SLA risposta |
|----------|------------|----------|--------------|
| **P1 Critical** | `/up` down, dead-letter > 0, demo su prod | IT on-call + responsabile RENTRI | 30 min |
| **P2 Warning** | Retry persistenti > 1 h, cert in scadenza < 7 gg | Segreteria + IT | 4 h lavorative |
| **P3 Info** | KPI movimenti da trasmettere > 0 | Segreteria | Prossimo turno |

### Template escalation P1

```
[RENTRI CRM] Alert post-deploy
Istanza: production | staging
Alert: rentri_dead_letter (N=___)
Monitor: php artisan rentri:monitor --json
Azione: /segreteria/rentri/transazioni
Deploy: commit ___ @ ___
```

---

## 5. Health check load balancer

| Endpoint | Expect | Intervallo |
|----------|--------|------------|
| `GET /up` | HTTP 200 | 30 s |

Preflight demo verifica anche `/up` via `DemoPreflightService`.

---

## 6. Post-deploy palestra operativa (staging formazione)

Su istanze con `ALLOW_SESSION_DEMO=true`:

1. Verificare toggle sidebar (segreteria)
2. `npm run test:e2e` (CI o manuale)
3. Opzionale: sessione UAT [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md)

Checklist completa: [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md)

---

## 7. Rollback rapido

| Scenario | Azione |
|----------|--------|
| Deploy fallito preflight | Non promuovere; rollback release precedente |
| Dead-letter massivo post-deploy | Rollback + `rentri:monitor`; analisi transazioni |
| Stub emergenza (solo staging) | `RENTRI_API_STUB=true` — **mai production** senza approvazione |

---

*Runbook post-deploy — Ciclo 4 Sprint 50.*
