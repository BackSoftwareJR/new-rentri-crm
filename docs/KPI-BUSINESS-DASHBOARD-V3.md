# KPI Business Dashboard v3

**Sprint 119 · Ciclo 10** · Estensione v2 con export CSV e alert email.

**Service base:** `BusinessKpiDashboardService` (Sprint 109)  
**Export:** `BusinessKpiExportService`  
**Alert:** `BusinessKpiAlertService` · comando `kpi:business-check`

---

## Novità v3

| Feature | Dettaglio |
|---------|-----------|
| Export CSV | Dashboard segreteria → «Export CSV» · colonne metrica, current, previous, delta, soglie |
| Alert email | Breach soglia **alert** → `BusinessKpiBreachMail` via hub notifiche |
| Cron | `kpi:business-check --notify` daily 07:30 Europe/Rome |
| Hub UI | Dashboard segreteria + banner admin `/admin/audit` |

---

## Soglie configurabili (env)

```env
KPI_BUSINESS_ORDINI_WARN=5
KPI_BUSINESS_ORDINI_ALERT=1
KPI_BUSINESS_VFU_WARN=8
KPI_BUSINESS_VFU_ALERT=2
KPI_BUSINESS_MAGAZZINO_WARN=500
KPI_BUSINESS_MAGAZZINO_ALERT=100
KPI_BUSINESS_REVENUE_WARN=500
KPI_BUSINESS_REVENUE_ALERT=100
KPI_BUSINESS_ALERT_ENABLED=true
KPI_BUSINESS_ALERT_PERIOD=last_7_days
```

Config: `config/dashboard.php` → `business_kpi.thresholds` · `business_kpi.alert`.

---

## Comando

```bash
php artisan kpi:business-check                    # valutazione human-readable
php artisan kpi:business-check --json             # output JSON per monitoring
php artisan kpi:business-check --notify           # email su breach alert
php artisan kpi:business-check --period=last_30_days --notify
```

Exit code **1** se almeno una metrica in stato `alert`.

---

## Metriche

Invariate rispetto a [KPI-BUSINESS-DASHBOARD-V2.md](KPI-BUSINESS-DASHBOARD-V2.md): ordini confermati, VFU accettate, magazzino kg, revenue stub.

---

## Riferimenti

- [SPRINT-119-AUDIT-NOTES.md](SPRINT-119-AUDIT-NOTES.md)
- [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) § KPI business (Sprint 119)
