# Logging produzione — RENTRI CRM

Sprint **121** · observability enterprise post-ciclo-10.

## Obiettivo

Log strutturati PSR-3/Monolog con correlazione richieste, persistenza consultabile in DB, UI admin e runbook operativo — senza duplicare l’activity log Spatie (audit business).

## Architettura

| Layer | Componente | Ruolo |
|-------|------------|-------|
| Correlazione | `AssignRequestCorrelationId` middleware | `X-Request-Id` / `trace_id` su ogni richiesta web |
| Job queue | `Queue::createPayloadUsing` + `JobProcessing` | Propaga `traceId` ai worker |
| Helper | `StructuredLogService` | `info/warning/error` con campi standard |
| File | Canali `rentri`, `security`, `integration`, `business` + `json_daily` | JSON rotazione giornaliera |
| DB | Tabella `application_logs` | Ricerca/filtri/export admin |
| Audit | `activity_log` (Spatie) | Azioni utente / compliance — **non sostituito** |

## Variabili ambiente

```env
# Canale default (dev)
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
LOG_DAILY_DAYS=14

# Produzione consigliata
LOG_CHANNEL=stack_prod
LOG_STACK_PROD=json_daily,rentri,security,integration,business
LOG_LEVEL=info

# Persistenza consultabile
APP_LOG_PERSIST_DB=true
APP_LOG_RETENTION_DAYS=90
APP_LOG_EXPORT_MAX_DAYS=30
```

## StructuredLogService — campi standard

Ogni riga include (file + DB):

- `trace_id` — correlazione LB / WAF / Horizon
- `module` — `rentri`, `ecommerce`, `gps`, `stripe`, `security`, `business`, `operatore`, `integration`
- `action` — es. `api_call`, `webhook_checkout_completed`, `sla_breach`
- `entity_type` / `entity_id`
- `user_id`, `demo_mode`
- `duration_ms`, `outcome` (`success` | `failure`)
- `context` — JSON mascherato (no secret/PII completi)

Esempio:

```php
app(StructuredLogService::class)->info(
    'rentri',
    'api_call',
    'Chiamata RENTRI completata',
    [
        'entity_type' => 'rentri_transazione',
        'entity_id'   => $tx->id,
        'outcome'     => 'success',
        'duration_ms' => 842,
        'context'     => ['endpoint' => '/fir/vidima'],
    ],
);
```

## Punti integrati (Sprint 121)

| Modulo | Trigger | Livello |
|--------|---------|---------|
| RENTRI API | success/fail + latency in `RentriApiClient` | info / error |
| Stripe | webhook OK + firma invalida | info / warning |
| GPS | probe provider live | info / warning |
| Security | `rentri:preflight` FAIL, WAF block checklist (dedup 1h) | error / warning |
| Business | SLA RENTRI breach, KPI business breach | warning (solo breach) |

## UI admin

- **URL:** `/admin/logs`
- Filtri: modulo, livello, trace_id, demo, date
- Dettaglio riga + export CSV
- Link da `/admin/audit`

## Comandi operativi

```bash
# Health check canali + spazio disco + ultimo errore critico
php artisan logs:health

# Purge oltre retention (default 90 gg, schedule domenica 04:00 Europe/Rome)
php artisan logs:purge
php artisan logs:purge --days=60
```

## Debug in produzione

1. Recuperare `X-Request-Id` dalla risposta HTTP o header ALB.
2. Cercare in `/admin/logs` per `trace_id`.
3. Cross-reference:
   - Activity log `/admin/audit` per azioni utente
   - SLA: `rentri:sla-check` + banner audit
   - KPI: `kpi:business-check` + dashboard v3
4. File JSON: `storage/logs/rentri-*.log`, `security-*.log`, ecc.

## Forwarding CloudWatch / S3 (infra)

L’app scrive su `storage/logs/*.log` (JSON). In AWS:

1. **CloudWatch agent** o **Fluent Bit** tail su `/var/www/.../storage/logs/*.log`
2. Log group suggeriti: `/rentri-crm/app/json`, `/rentri-crm/app/rentri`, …
3. **S3 lifecycle** allineata a `APP_LOG_RETENTION_DAYS` (90 gg default)
4. Correlazione WAF SIEM: header `X-Request-Id` (vedi `WafDeploymentPreflightService::siemChecklist`)

Non incluso in repo: agent Datadog/ELK — opzionale lato infra.

## Retention e privacy

- Purge DB: `logs:purge` + schedule settimanale
- Rotazione file: `LOG_DAILY_DAYS` (Monolog daily)
- Mascheramento: token Bearer, api_key, email parziale via `LogSensitiveDataMasker`
- Demo scope: in demo mode la UI log filtra `demo_mode=true` (come activity log)

## Test

```bash
php artisan test tests/Feature/Sprint121/ProductionLoggingTest.php
```

10 feature test Sprint 121 · baseline regressione: suite completa ≥847 test.

## Runbook incident

| Scenario | Azione |
|----------|--------|
| Spike error RENTRI | Filtra modulo `rentri`, livello `error`, ultima ora; correlare `correlation_id` MASE |
| Webhook Stripe KO | Modulo `stripe`, action `webhook_rejected` |
| GPS probe fail | Modulo `gps`, verificare `trasporto:gps-switch-check --probe` |
| WAF block | Modulo `security`, action `waf_preflight_fail` |
| SLA/KPI | Modulo `business`, actions `sla_breach` / `kpi_breach` |

---

Vedi anche: `docs/SPRINT-121-REVIEW-HANDOFF.md`, `docs/GO-LIVE-CERT-PRODUZIONE.md` § observability.
