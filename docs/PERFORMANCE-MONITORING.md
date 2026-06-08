# Performance monitoring — CRM RENTRI

**Sprint 74** · KPI cache Redis + k6 load · Horizon prep

---

## 1. KPI cache dashboard

| Parametro | Env | Default |
|-----------|-----|---------|
| Abilitazione | `KPI_CACHE_ENABLED` | `true` |
| Store | `KPI_CACHE_STORE` | `cache.default` (array in test, redis in prod) |
| TTL | `KPI_CACHE_TTL` | `300` (secondi) |

**Servizio:** `App\Domain\Dashboard\KpiRedisCacheService`

- Chiave: `dashboard:kpi:{demo|prod}` (demo scope isolato).
- Invalidazione event-driven su save/delete: VFU, registro, RENTRI transazioni, MUD, e-commerce, CER.
- UI dashboard: badge **KPI cache: hit/miss** + pulsante **Refresh KPI**.

---

## 2. Load test k6

| Script | Scopo |
|--------|--------|
| `scripts/k6-smoke.js` | Health + login page anonimo |
| `scripts/k6-authenticated.js` | Login cookie + segreteria/operatore |

```bash
# Smoke anonimo
k6 run scripts/k6-smoke.js

# Autenticato (segreteria + operatore)
K6_EMAIL=segreteria@example.com K6_PASSWORD=password k6 run scripts/k6-authenticated.js
```

Soglie default: p95 < 3s, checks > 85%.

---

## 3. Horizon — metriche job queue (prep)

| Job | Queue | Unique | Schedule |
|-----|-------|--------|----------|
| `AuditExportScheduledJob` | default | sì (1h) | Lun 02:00 |
| `LegacyIncrementalSyncJob` | default | sì (5m) | Lun 03:00 |
| `SendNotificationJob` | default | no | on-demand |

**Dashboard Horizon:** `/horizon` (solo admin con `HorizonMonitorService::canAccess()`).

**Metriche da monitorare in prod:**

- Job throughput / failed per ora
- Queue wait time (Redis)
- `audit:export-scheduled` e `legacy:sync-incremental` last success (activity log modulo `audit` / `legacy`)

---

## 4. Redis produzione (checklist)

1. [ ] `CACHE_STORE=redis` e `KPI_CACHE_STORE=redis`
2. [ ] `SESSION_DRIVER=redis` (vedi `docs/REDIS-SESSION-PREP.md`)
3. [ ] `QUEUE_CONNECTION=redis` per Horizon
4. [ ] Monitoraggio memoria Redis + eviction policy `volatile-lru`

---

## 5. Rollback cache KPI

```bash
# Disabilita cache senza redeploy codice
KPI_CACHE_ENABLED=false
```

Oppure refresh manuale da dashboard segreteria (**Refresh KPI**).

---

*Aggiornato Sprint 74 — giugno 2026.*
