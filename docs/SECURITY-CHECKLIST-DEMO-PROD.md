# Security checklist — Demo vs Produzione

Checklist pen-test / hardening per **isolamento istanze demo e produzione** (Ciclo 3, sprint 36–45).  
Complementa [GO-LIVE-CICLO-3.md](GO-LIVE-CICLO-3.md) e [DEMO-DEPLOY.md](DEMO-DEPLOY.md).

Legenda: ✅ verificato in codice/test | 🔍 verifica manuale deploy | ⚠️ rischio se non rispettato

---

## 1. Architettura deploy

| # | Controllo | Demo | Prod | Riferimento |
|---|-----------|------|------|-------------|
| 1.1 | Due istanze separate (URL + env file) | `demo.crm.*` + `.env.demo` | `crm.*` + `.env` | [DEMO-DEPLOY §1](DEMO-DEPLOY.md) |
| 1.2 | DB dedicato consigliato | `rentri_crm_demo` | `rentri_crm_prod` | [CICLO-3-PIANO §2.3](CICLO-3-PIANO-COMPLETO.md) |
| 1.3 | **No toggle sessione** demo/prod sulla stessa istanza | 🔍 | 🔍 | `APP_DEMO_MODE` è per-deploy |
| 1.4 | CI demo su branch dedicato | `.github/workflows/demo-staging.yml` | pipeline prod separata | [DEMO-DEPLOY §6](DEMO-DEPLOY.md) |

---

## 2. Isolamento `is_demo` (persistenza)

| # | Controllo | Meccanismo | Test / codice |
|---|-----------|------------|---------------|
| 2.1 | Global scope filtra righe per modalità | `HasDemoScope` → `where is_demo = DemoContext::isActive()` | ✅ Sprint 36 |
| 2.2 | Write cross-mode bloccato | `creating`/`updating` → `DemoIsolationException::crossModeWrite()` | ✅ `HasDemoScope` |
| 2.3 | FK trasporto ↔ svuotamento stesso scope | `Trasporto::booted()` valida `is_demo` coerente | ✅ Sprint 43 |
| 2.4 | Modelli scoped elencati | `DemoContext::scopedModels()` — FIR, blocchi, registro, transazioni, settings, trasporti, svuotamenti | ✅ |
| 2.5 | Reset demo non tocca prod | `rentri:demo-reset` → `DELETE WHERE is_demo=true` | ✅ Sprint 36 |
| 2.6 | Seed demo idempotente | `rentri:demo-seed` crea solo fixture demo | ✅ Sprint 37 |

### Pen-test manuale (🔍)

- [ ] Su istanza **prod** (`APP_DEMO_MODE=false`): tentare `rentri:demo-seed --force` → deve fallire o non creare dati visibili in UI prod.
- [ ] Su istanza **demo**: verificare che query Eloquent non restituiscano record `is_demo=false` (se coesistono nello stesso DB per errore).
- [ ] Tentativo collegamento trasporto prod a svuotamento demo (API/tinker) → `DemoIsolationException::crossReference()`.

---

## 3. API RENTRI / sandbox MASE

| # | Controllo | Demo | Prod | Codice |
|---|-----------|------|------|--------|
| 3.1 | Host produzione MASE bloccato in demo | ✅ `api.rentri.gov.it` → `RuntimeException` | consentito se ambiente UI = produzione | `RentriApiClient` |
| 3.2 | Sandbox forzata in demo | `RENTRI_DEMO_FORCE_SANDBOX=true` | n/a | `DemoContext::forceSandboxApi()` |
| 3.3 | Modalità offline (zero HTTP) | `RENTRI_DEMO_NO_HTTP=true` | ❌ non usare in prod | `DemoContext::offlineNoHttp()` |
| 3.4 | Stub API in CI demo | `RENTRI_API_STUB=true` | `false` per go-live | config |
| 3.5 | Preflight pre-deploy | `rentri:preflight --demo` | `rentri:preflight` strict | Sprint 44 |

### Pen-test manuale (🔍)

- [ ] Demo: confermare in log/transazioni che nessuna URL contenga `api.rentri.gov.it`.
- [ ] Prod: `rentri:preflight` verde prima del go-live ([GO-LIVE-RENTRI](GO-LIVE-RENTRI.md)).
- [ ] Rotazione certificati mTLS/firma: accesso limitato a ruolo admin/segreteria.

---

## 4. Sessioni e autenticazione

| # | Controllo | Note |
|---|-----------|------|
| 4.1 | Session driver | `database` o `redis` in staging/prod — non `file` condiviso tra istanze |
| 4.2 | Cookie domain | Demo e prod su domini distinti → cookie non condivisi |
| 4.3 | Ruoli Spatie | `segreteria` / `admin` / `operatore` — Horizon solo `admin` |
| 4.4 | CSRF Livewire | Framework default — 🔍 verificare su form upload certificati |
| 4.5 | Certificati PKCS#12 | Storage cifrato (`Crypt::encryptString`) — non loggare password |
| 4.6 | Demo pubblica | 🔍 Se demo esposta su Internet: credenziali demo dedicate, rate limit, no dati reali operatore |

### Pen-test manuale (🔍)

- [ ] Session fixation: login/logout su demo non invalida session prod (domini separati).
- [ ] Accesso `/horizon` senza ruolo admin → 403.
- [ ] Upload certificato con file non-P12 → rifiutato in UI.

---

## 5. Monitoraggio e alerting

| # | Segnale | Soglia | Azione |
|---|---------|--------|--------|
| 5.1 | Health Laravel | `GET /up` ≠ 200 | Restart / rollback deploy |
| 5.2 | Dead-letter RENTRI | `dead_letter_at IS NOT NULL` > 0 | Dashboard + `/segreteria/rentri/transazioni` |
| 5.3 | Retry pianificati | `next_retry_at` futuro, no dead-letter | Verificare Horizon/worker |
| 5.4 | Misconfig demo/prod | `APP_DEMO_MODE` vs `APP_ENV` incoerenti | `rentri:monitor` alert |

Vedi [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md).

---

## 6. Checklist rapida pre-go-live

### Istanza produzione

- [ ] `APP_DEMO_MODE=false`
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `RENTRI_API_STUB=false`, `RENTRI_FIRMA_STUB=false` (se live)
- [ ] `php artisan rentri:preflight` — 0 fail
- [ ] `php artisan rentri:monitor` — 0 alert critical
- [ ] DB backup configurato

### Istanza demo

- [ ] `APP_DEMO_MODE=true`
- [ ] `RENTRI_DEMO_FORCE_SANDBOX=true`
- [ ] `php artisan rentri:preflight --demo --require-seed`
- [ ] Banner «Modalità DEMO» visibile post-login
- [ ] Nessun certificato produzione caricato per errore

---

## 7. Gap residui (post ciclo 3)

| Area | Stato | Nota |
|------|-------|------|
| Pen test esterno / OWASP ZAP | 🔄 | Prep Sprint 104 — scope doc + UI; audit vendor TBD |
| WAF / rate limiting edge | 🔄 | Prep Sprint 105 — runbook + UI; deploy regole = infra |
| Session admin toggle demo | 📋 | Documentato come anti-pattern — non implementato |
| Secret rotation automatizzata | 🔲 | Runbook manuale in GO-LIVE-RENTRI |

---

*Security checklist ciclo 3 — Sprint 45.*
