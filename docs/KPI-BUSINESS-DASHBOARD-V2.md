# KPI Business Dashboard v2

**Sprint 109 · Ciclo 9** · Service: `BusinessKpiDashboardService`

Dashboard segreteria — sezione **KPI business v2** con finestra **7 o 30 giorni** e confronto vs periodo precedente della stessa lunghezza.

---

## Metriche

| Chiave | Definizione | Fonte dati | Drill-down |
|--------|-------------|------------|------------|
| `ordini_confermati` | Ordini e-commerce con `stato = confermato` e `confermato_at` nella finestra | `ecommerce_ordini` | `/segreteria/ecommerce` |
| `vfu_accettate` | Pratiche VFU con `data_accettazione` valorizzata nella finestra | `vfu_registrations` | `/segreteria/vfu` |
| `magazzino_kg` | Somma `peso_kg` di tutti i movimenti registro con `data_movimento` nella finestra | `registro_movimenti` | `/segreteria/registro-movimenti` |
| `revenue_eur` | **Stub:** somma `totale` degli ordini confermati nella finestra (no fatturazione reale) | `ecommerce_ordini.totale` | `/segreteria/ecommerce` |

### Trend

Per ogni metrica il service calcola **delta** vs finestra precedente:

- **7 gg:** ultimi 7 giorni vs 7 giorni immediatamente precedenti
- **30 gg:** ultimi 30 giorni vs 30 giorni precedenti

Output delta: `diff`, `pct`, `direction` (`up` | `down` | `flat`).

---

## Soglie (threshold)

Configurate in `config/dashboard.php` → `business_kpi.thresholds`.

| Metrica | warn (≤) | alert (≤) | Interpretazione |
|---------|----------|-----------|-----------------|
| `ordini_confermati` | 5 | 1 | Volume ordini sotto atteso |
| `vfu_accettate` | 8 | 2 | Accettazioni VFU basse |
| `magazzino_kg` | 500 | 100 | Attività magazzino ridotta (kg) |
| `revenue_eur` | 500 | 100 | Revenue stub sotto soglia |

Stati UI: `ok` (default), `warn` (giallo), `alert` (rosso) — colore valore sulla card KPI.

---

## API service

```php
$service = app(BusinessKpiDashboardService::class);

$service->comparisonForPeriod('last_7_days');  // o last_30_days
$service->dashboard(7);                        // shortcut 7 gg
$service->metricsForRange($from, $to);
$service->thresholdStatus('ordini_confermati', $count);
```

---

## Riferimenti

- [SPRINT-109-AUDIT-NOTES.md](SPRINT-109-AUDIT-NOTES.md)
- [SPRINT-109-REVIEW-HANDOFF.md](SPRINT-109-REVIEW-HANDOFF.md)
- `DashboardAnalyticsService` — analytics mensili (complementare, non duplicato)
