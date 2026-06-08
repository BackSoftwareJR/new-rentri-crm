# Go-live RENTRI — Produzione API ministeriali

Checklist e runbook per il go-live del **ciclo 2** (integrazione RENTRI/FIR reale, sprint 31–35). Complementa [GO-LIVE.md](GO-LIVE.md) (legacy/import) e [PRE-DEPLOY-CHECKLIST.md](PRE-DEPLOY-CHECKLIST.md).

**Aggiornamento Sprint 106 · Ciclo 9:** switch produzione unificato + gate post-WAF.

---

## 0. Gate pre-produzione (ciclo 9)

| Gate | Doc / tool | Obbligatorio |
|------|------------|--------------|
| WAF block edge | [WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md) | Consigliato (opzionale P1) |
| Pen-test prep | [PEN-TEST-EXTERNAL-SCOPE.md](PEN-TEST-EXTERNAL-SCOPE.md) | Prima audit vendor |
| Switch check | `php artisan rentri:production-switch-check` | **Sì** |
| Runbook switch | [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md) | **Sì** |
| Sandbox validato | [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md) | **Sì** |

**Service:** `RentriProductionSwitchService` · **UI:** hub RENTRI + Impostazioni step 4.

---

## 1. Variabili ambiente produzione

```env
# Ambiente applicazione
APP_ENV=production
APP_DEBUG=false

# RENTRI — API live (Sprint 106: RENTRI_ENV obbligatorio per switch prod)
RENTRI_ENV=production
RENTRI_BASE_URL_SANDBOX=https://demoapi.rentri.gov.it
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_API_STUB=false
RENTRI_FIRMA_STUB=false
RENTRI_AUTH_MODE=mtls
RENTRI_VERIFY_SSL=true
RENTRI_HTTP_TIMEOUT=30

# Polling async (vidima FIR + registro)
RENTRI_FIR_POLL_MAX_ATTEMPTS=15
RENTRI_FIR_POLL_INTERVAL_MS=500
RENTRI_REGISTRO_POLL_MAX_ATTEMPTS=15
RENTRI_REGISTRO_POLL_INTERVAL_MS=500

# Solo CI/manuale con certificati reali
RENTRI_INTEGRATION_TEST=false
```

In **Impostazioni RENTRI** (UI): ambiente `produzione` o `sandbox`, dati operatore, upload certificati PKCS#12:

| Certificato | Uso | Storage |
|-------------|-----|---------|
| Interoperabilità | mTLS verso API RENTRI | `rentri/certificates/` |
| Firma remota xFIR | COSE_Sign1 post-vidima | `rentri/certificates/firma/` |

---

## 2. Preflight automatizzato

```bash
cd new-rentri-crm
npm run build
php artisan rentri:preflight
php artisan rentri:production-switch-check --dry-run
```

### Check eseguiti

| Check | Fail se | Warn se |
|-------|---------|---------|
| `app_key` | APP_KEY vuota | — |
| `app_debug` | APP_DEBUG in production | APP_DEBUG in staging |
| `database` | DB non raggiungibile | — |
| `vite_manifest` | build assente | — |
| `rentri_cert` | live senza cert mTLS / scaduto | stub attivo |
| `rentri_firma_cert` | firma live senza cert / scaduto | firma stub attivo |
| `rentri_operator` | live senza sito/CF / onboarding | stub attivo |
| `rentri_stub` | — | stub in production |
| `rentri_firma_stub` | — | firma stub in production |

**Go-live RENTRI:** tutti i check `ok` o solo `warn` accettabili; **nessun `fail`**.

---

## 3. Smoke test manuali E2E

Eseguire in **sandbox/demo** prima della produzione, poi replicare su `api.rentri.gov.it`.

### 3.1 Onboarding

1. `/segreteria/rentri-impostazioni` — wizard 3 step
2. Dati operatore: CF operatore, P.IVA, `num_iscr_sito`
3. Upload certificato interoperabilità `.p12`
4. **Test connessione** — badge «Connesso sandbox/demo»
5. Sezione **Certificato firma remota xFIR** — upload secondo `.p12`

### 3.2 Health e codifiche

- Test connessione OK
- `php artisan rentri:sync-codifiche` (se configurato) o sync da UI CER
- Sync blocchi FIR: `/segreteria/fir/blocchi` → «Sincronizza da RENTRI»

### 3.3 Vidima FIR

1. Creare trasporto in preparazione
2. Dettaglio trasporto → **Vidima FIR**
3. Verificare protocollo RENTRI, transazione_id, QR in UI
4. Storico API: `/segreteria/rentri/transazioni` — tipo `fir`, stato completata

### 3.4 Firma xFIR

1. Su FIR vidimato → **Firma xFIR**
2. Badge «Firmato» + download JSON payload COSE_Sign1
3. Verificare `typ: COSE_Sign1` nel file scaricato

### 3.5 Trasmissione registro

1. `/segreteria/rentri` — periodo con movimenti non trasmessi
2. **Trasmetti a RENTRI** — protocollo accettazione in messaggio success
3. Movimenti bloccati (`locked_at`); storico trasmissioni aggiornato

### 3.6 Test integrazione opzionale (CLI)

Con certificati reali e rete verso demoapi:

```bash
RENTRI_API_STUB=false RENTRI_INTEGRATION_TEST=true php artisan test --filter=RentriIntegrationTest
```

---

## 4. Runbook operativo

### 4.1 Indisponibilità servizi MASE

| Sintomo | Azione |
|---------|--------|
| HTTP 5xx da demoapi/api.rentri.gov.it | Verificare [rentri.gov.it](https://www.rentri.gov.it) / comunicazioni MASE; sospendere trasmissioni batch |
| Timeout vidima/registro | Aumentare temporaneamente `RENTRI_*_POLL_*`; ritentare singola operazione |
| 401/403 persistente | Verificare certificato mTLS, scadenza, CF operatore autorizzato sul sito |

**Non** abilitare stub in production per aggirare indisponibilità — usare coda differita e retry manuale quando il servizio riparte.

### 4.2 Rotazione certificati

1. Ottenere nuovo PKCS#12 da CA dominio RENTRI
2. Impostazioni RENTRI → ricaricare certificato (interoperabilità e/o firma)
3. **Test connessione** immediato
4. `php artisan rentri:preflight` — verificare scadenze
5. Revocare/disattivare certificato precedente lato portale MASE quando previsto

### 4.3 Rollback a modalità stub (emergenza staging)

Solo **staging/dev**, mai production senza approvazione:

```env
RENTRI_API_STUB=true
RENTRI_FIRMA_STUB=true
php artisan config:clear
```

Effetto: nessuna HTTP ministeriale; vidima/registro/firma simulati in-app. I movimenti già `locked_at` **non** vengono sbloccati automaticamente.

---

---

## 5. Runbook post-deploy (Ciclo 4)

Dopo ogni deploy staging/produzione:

```bash
php artisan rentri:preflight
php artisan rentri:monitor
```

Dettaglio dead-letter, escalation e cron: [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md).  
Monitor KPI: [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md).

---

## 6. Palestra operativa (Ciclo 4 — CHIUSO)

La **palestra operativa** consente formazione RENTRI in sandbox MASE senza toccare dati produzione (toggle sessione `is_demo=true`).

| Documento | Contenuto |
|-----------|-----------|
| [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) | Guida utente, FAQ, percorso consigliato |
| [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md) | Checklist chiusura ciclo 4 (codice ✅, UAT operativo ☐) |
| [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) | Sessione guidata 90 min + checklist firmabile |
| [CICLO-4-PIANO.md](CICLO-4-PIANO.md) | Piano sprint 46–50, architettura toggle |

### Checklist pre-go-live operativo (palestra)

- [x] Toggle palestra implementato e testato (PHPUnit + Playwright `npm run test:e2e`)
- [x] Isolamento demo/prod (scope, policy, magazzino virtuale)
- [x] Preset multi-operatore sandbox (profili `default` / `sede_nord` / `sede_sud`)
- [x] CI produzione: `.github/workflows/production.yml` (test + preflight + Playwright)
- [ ] UAT formazione **firmato** — [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) §4
- [ ] Checklist [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md) voci operative completate in sede

### Quando usare palestra vs istanza demo

| Scenario | Soluzione |
|----------|-----------|
| Formazione su staging condiviso | Toggle **palestra operativa** (`ALLOW_SESSION_DEMO=true`) |
| Demo permanente / vendita | Istanza deploy `APP_DEMO_MODE=true` — [DEMO-DEPLOY.md](DEMO-DEPLOY.md) |
| Operazioni reali MASE | Palestra **OFF**, scope prod, cert produzione |

---

## 7. Checklist go-live RENTRI (finale)

- [ ] `RENTRI_ENV=production`
- [ ] `RENTRI_API_STUB=false` e `RENTRI_FIRMA_STUB=false`
- [ ] `php artisan rentri:production-switch-check` SUCCESS
- [ ] Runbook [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md) condiviso
- [ ] WAF block attivo (consigliato — [WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md))
- [ ] Certificati mTLS + firma xFIR validi e caricati
- [ ] `php artisan rentri:preflight` verde (0 fail)
- [ ] Smoke E2E §3 completato in sandbox
- [ ] Smoke E2E replicato in produzione (se applicabile)
- [ ] Runbook §4 + [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md) condivisi con segreteria/IT
- [ ] Palestra operativa: UAT firmato ([UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md)) o checklist [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md) operativa
- [ ] [GO-LIVE.md](GO-LIVE.md) §5 (legacy/import) completato

---

*Sprint 50 — ciclo 4 palestra operativa chiuso — 4 giugno 2026.*
