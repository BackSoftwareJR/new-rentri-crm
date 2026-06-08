# Go-live Ciclo 3 — Demo + Produzione

Runbook di **chiusura ciclo 3** (sprint 36–45): piattaforma demo, gap RENTRI/FIR, hardening e monitoraggio.  
Complementa [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) (ciclo 2 produzione API) e [CICLO-3-PIANO-COMPLETO.md](CICLO-3-PIANO-COMPLETO.md).

---

## 1. Stato ciclo 3 — CHIUSO ✅

| Sprint | Deliverable | Stato |
|--------|-------------|-------|
| 36 | Fondamenta demo (`APP_DEMO_MODE`, `HasDemoScope`, API sandbox) | ✅ |
| 37 | Demo seed + walkthrough UI | ✅ |
| 38 | Validazione XSD xFIR ministeriale | ✅ |
| 39 | Invio xFIR firmato MASE | ✅ |
| 40 | Job retry MASE + dead-letter | ✅ |
| 41 | Conformità registro (checklist, lock, audit export) | ✅ |
| 42 | Conformità FIR (vidima edge, firma guard, QR spec) | ✅ |
| 43 | Isolamento trasporti/svuotamenti demo | ✅ |
| 44 | Deploy demo CI/CD (workflow, preflight demo) | ✅ |
| 45 | Hardening, monitoraggio, security checklist | ✅ |

---

## 2. Due binari go-live

```
┌─────────────────────────────┐     ┌─────────────────────────────┐
│  GO-LIVE DEMO               │     │  GO-LIVE PRODUZIONE         │
│  docs/DEMO-DEPLOY.md        │     │  docs/GO-LIVE-RENTRI.md     │
├─────────────────────────────┤     ├─────────────────────────────┤
│  APP_DEMO_MODE=true         │     │  APP_DEMO_MODE=false        │
│  .env.demo.example          │     │  APP_ENV=production         │
│  rentri:preflight --demo    │     │  rentri:preflight           │
│  rentri:demo-seed --fresh   │     │  RENTRI_API_STUB=false      │
│  Branch demo → GH Actions   │     │  Cert mTLS + firma reali    │
└─────────────────────────────┘     └─────────────────────────────┘
```

---

## 3. Sequenza go-live DEMO

```bash
cd new-rentri-crm
cp .env.demo.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm ci && npm run build
php artisan migrate --force
php artisan db:seed
php artisan rentri:demo-seed --fresh
php artisan rentri:preflight --demo --require-seed
php artisan rentri:monitor
```

### Verifica UI

1. Login `segreteria@example.com`
2. Banner giallo «Modalità DEMO»
3. Card «Prova flusso RENTRI» con fixture caricate
4. Dashboard → KPI Dead-letter = 0 (post-deploy pulito)

### CI

Push su branch `demo` → workflow [demo-staging.yml](../.github/workflows/demo-staging.yml).

---

## 4. Sequenza go-live PRODUZIONE

Seguire [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) con aggiunte ciclo 3:

```bash
npm run build
php artisan migrate --force
php artisan rentri:preflight          # 0 fail
php artisan rentri:monitor              # 0 alert critical
```

### Smoke E2E aggiuntivi (ciclo 3)

| # | Flusso | Verifica |
|---|--------|----------|
| 1 | Vidima FIR | Blocco esaurito gestito (Sprint 42) |
| 2 | Firma xFIR | Blocker pre-vidima + QR spec |
| 3 | Invio xFIR MASE | Protocollo su storico API tipo `xfir` |
| 4 | Registro | Checklist conformità pre-invio + lock movimento |
| 5 | Retry MASE | Simulare 503 → retry schedulato → no dead-letter prematuro |
| 6 | Audit | Export JSON/CSV registro trasmesso |

---

## 5. Monitoraggio post go-live

| Strumento | Frequenza | Doc |
|-----------|-----------|-----|
| `GET /up` | Continuo (LB) | [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) |
| `rentri:monitor` | 15 min (prod) | Alert dead-letter / health |
| Dashboard KPI | On-demand | Dead-letter, retry, errori API |
| Horizon | On-demand | Queue retry MASE |
| Security review | Pre-release | [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md) |

---

## 6. Rollback

### Demo

```bash
php artisan rentri:demo-reset
# oppure redeploy immagine precedente + migrate:rollback (solo se migration breaking)
```

### Produzione

Vedi [GO-LIVE-RENTRI.md § Rollback](GO-LIVE-RENTRI.md).  
Transazioni RENTRI in dead-letter: risoluzione manuale prima di rollback schema.

---

## 7. Gap residui (manutenzione, non bloccano ciclo 3)

| Gap | Priorità | Nota |
|-----|----------|------|
| Pen test OWASP / audit esterno | Media | Checklist interna completata Sprint 45 |
| WAF / rate limit edge | Media | Infra |
| Pipeline CI produzione | Media | Demo CI ✅; prod da definire in infra team |
| Session toggle admin demo | Bassa | Anti-pattern documentato |
| Load test MASE | Bassa | Retry/dead-letter coprono indisponibilità |

---

## 8. Riferimenti documentazione

| Documento | Contenuto |
|-----------|-----------|
| [CICLO-3-PIANO-COMPLETO.md](CICLO-3-PIANO-COMPLETO.md) | Architettura demo, sprint 36–45 |
| [DEMO-DEPLOY.md](DEMO-DEPLOY.md) | Bootstrap demo, CI staging |
| [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) | Produzione API ministeriali ciclo 2 |
| [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md) | Pen-test checklist |
| [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) | Health, dead-letter, alerting |
| [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) | Backlog verticale |

---

*Go-live ciclo 3 — Sprint 45 — Ciclo 3 CHIUSO.*
