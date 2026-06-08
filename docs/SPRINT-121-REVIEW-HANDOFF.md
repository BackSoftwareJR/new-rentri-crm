# Sprint 121 — Review handoff · Logging produzione

**Stato:** ✅ implementato  
**Baseline test pre-121:** 847 test (ciclo 10 chiuso)

## Deliverable

| # | Item | File / route |
|---|------|--------------|
| 1 | Canali JSON strutturati | `config/logging.php` — `stack_prod`, `json_daily`, `rentri`, `security`, `integration`, `business` |
| 2 | Correlazione richieste | `AssignRequestCorrelationId`, `RequestContext`, propagazione queue in `AppServiceProvider` |
| 3 | StructuredLogService | `app/Support/Logging/StructuredLogService.php` |
| 4 | Integrazioni critiche | `RentriApiClient`, Stripe webhook, GPS probe, preflight/WAF, SLA/KPI breach |
| 5 | Persistenza DB | migration `application_logs`, model `ApplicationLog` |
| 6 | UI admin | `/admin/logs`, export CSV, `LogsIndex` Livewire |
| 7 | Artisan | `logs:purge`, `logs:health` |
| 8 | Docs | `docs/LOGGING-PRODUZIONE.md` |
| 9 | Test | `tests/Feature/Sprint121/ProductionLoggingTest.php` (10 test) |

## Comandi operativi

```bash
php artisan logs:health
php artisan logs:purge --days=90
php artisan test tests/Feature/Sprint121/ProductionLoggingTest.php
```

## Env produzione (snippet)

```env
LOG_CHANNEL=stack_prod
LOG_STACK_PROD=json_daily,rentri,security,integration,business
LOG_LEVEL=info
APP_LOG_PERSIST_DB=true
APP_LOG_RETENTION_DAYS=90
```

## Verifica manuale

1. Login admin → `/admin/logs` — filtri e dettaglio
2. Eseguire chiamata RENTRI stub → riga modulo `rentri` con `duration_ms`
3. Header risposta contiene `X-Request-Id`
4. Export CSV con filtri attivi
5. `logs:health` → OK

## Opzionale (fuori scope sprint)

- Agent Datadog / ELK sidecar
- CloudWatch subscription filter (solo doc infra in LOGGING-PRODUZIONE.md)
- Sampling rate su API RENTRI ad alto volume

## Note review

- Activity log Spatie **invariato** — complementare, non duplicato
- WAF failure log con dedup cache 1h per evitare spam su refresh UI
- KPI/SLA log solo su breach (non ogni check schedulato OK)
