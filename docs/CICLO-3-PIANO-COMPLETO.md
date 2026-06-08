# Ciclo 3 — Piattaforma demo + gap RENTRI/FIR produzione completa

**Sprint 36–45** · Ultimo aggiornamento: 4 giugno 2026  
**Partenza:** ciclo 2 chiuso (sprint 31–35), [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md)

---

## 1. Obiettivi ciclo 3

| # | Obiettivo | Esito atteso |
|---|-----------|--------------|
| 1 | **Piattaforma demo** | UX identica alla produzione; dati CRM isolati; API solo sandbox MASE (o stub locale) |
| 2 | **Gap ciclo 2** | XSD xFIR, invio payload firmato, retry job MASE, conformità minima per modulo |
| 3 | **Go-live ciclo 3** | Runbook demo + produzione hardening, CI deploy demo/prod separati |

---

## 2. Architettura Demo Mode (scelta Sprint 36)

### 2.1 Opzioni valutate

| Opzione | Pro | Contro | Decisione |
|---------|-----|--------|-----------|
| **DB connection `demo` separato** | Isolamento fisico totale | Doppia migration, seed, deploy; diverge da SQLite test | ❌ |
| **Prefix tabelle `demo_*`** | Query esplicite | Refactor massivo modelli/relazioni | ❌ |
| **`APP_DEMO_MODE` + colonna `is_demo`** | Minimo diff; stesso schema; scope Eloquent | Coesistenza righe nello stesso DB (mitigato da scope) | ✅ **Scelto** |
| **Tenant demo user + session toggle** | UX switch senza redeploy | Rischio cross-write se mal configurato | 📋 Sprint 44 (opzionale) |
| **Snapshot reset** | Demo ripetibile | Complemento a `rentri:demo-reset` | ✅ Comando Sprint 36 |

### 2.2 Implementazione adottata

```
┌─────────────────────────────────────────────────────────────┐
│  Deploy A: .env.production     APP_DEMO_MODE=false          │
│  → HasDemoScope filtra is_demo=false                        │
│  → RentriSetting::instance() → row is_demo=false            │
│  → RentriApiClient → sandbox|prod da settings.ambiente      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Deploy B: .env.demo             APP_DEMO_MODE=true         │
│  → HasDemoScope filtra is_demo=true                         │
│  → RentriSetting::instance() → row is_demo=true              │
│  → RentriApiClient → FORZA demoapi.rentri.gov.it            │
│  → RENTRI_DEMO_NO_HTTP=true → stub locale, zero HTTP        │
│  → Banner UI + sezione «Ambiente di prova»                    │
│  → rentri:demo-reset → DELETE WHERE is_demo=true            │
└─────────────────────────────────────────────────────────────┘
```

**File chiave:** `config/demo.php`, `App\Support\Demo\DemoContext`, `App\Models\Concerns\HasDemoScope`, `App\Domain\Demo\DemoResetService`, `rentri:demo-reset`.

**Modelli scoped (Sprint 36–43):** `Fir`, `FirBlocco`, `RegistroMovimento`, `RentriTransmissione`, `RentriTransazione`, `RentriSetting`, `Trasporto`, `MagazzinoSvuotamento`.

**Sicurezza write:** `creating` / `updating` su `HasDemoScope` lancia `DemoIsolationException` se `record.is_demo !== DemoContext::isActive()`.

**FK trasporti ↔ svuotamenti (Sprint 43):** `Trasporto` rifiuta collegamenti cross-mode verso `MagazzinoSvuotamento` con `is_demo` diverso.

### 2.3 Variabili ambiente

| Variabile | Default prod | Demo consigliato |
|-----------|--------------|------------------|
| `APP_DEMO_MODE` | `false` | `true` |
| `RENTRI_DEMO_FORCE_SANDBOX` | n/a | `true` |
| `RENTRI_DEMO_NO_HTTP` | `false` | `false` (live sandbox) o `true` (offline) |
| `RENTRI_API_STUB` | `false` (prod live) | `true` o live sandbox |
| `DB_*` | DB produzione | **DB separato consigliato** (stesso schema) |

Pattern deploy: **due istanze** (URL demo vs prod), non toggle sessione su stessa istanza prod.

---

## 3. Matrice Demo vs Produzione vs Stub

| Aspetto | Produzione CRM | Demo CRM | Stub locale |
|---------|----------------|----------|-------------|
| **Flag** | `APP_DEMO_MODE=false` | `APP_DEMO_MODE=true` | demo + `RENTRI_DEMO_NO_HTTP=true` |
| **Persistenza FIR/registro/trasporti** | `is_demo=false` | `is_demo=true` | `is_demo=true` |
| **RentriSetting** | Singleton `is_demo=false` | Singleton `is_demo=true` | idem demo |
| **Base URL MASE** | Da UI: sandbox **o** api.rentri.gov.it | **Sempre** demoapi.rentri.gov.it | Nessuna HTTP |
| **mTLS cert** | Cert reale operatore | Cert sandbox MASE | Opzionale / assente |
| **Vidima / registro API** | Live o stub (`RENTRI_API_STUB`) | Sandbox live o stub | Stub `RentriApiClient` |
| **Firma xFIR COSE** | Live o `RENTRI_FIRMA_STUB` | Stesso flusso UX | Stub firma ok |
| **Audit transazioni** | `rentri_transazioni` prod | `rentri_transazioni` demo | demo scope |
| **Reset dati** | ❌ | `rentri:demo-reset` | idem |
| **Banner UI** | Assente | «Modalità DEMO» | idem |
| **Preflight go-live** | `rentri:preflight` strict | Warn stub/demo attesi | N/A |

---

## 4. Tabella sprint 36–45

| Sprint | Scope | Deliverable | Stato |
|--------|-------|-------------|-------|
| **36** | Fondamenta demo | `APP_DEMO_MODE`, `is_demo` + `HasDemoScope`, banner, settings «Ambiente di prova», `RentriApiClient` sandbox/offline, `rentri:demo-reset`, test Feature, questo piano | ✅ |
| **37** | Demo seed + walkthrough | `rentri:demo-seed` (blocchi, trasporto, movimenti demo), card dashboard «Prova RENTRI», doc `.env.demo`, test E2E guidato | ✅ |
| **38** | XSD xFIR ministeriale | Validator XSD completo vs schema MASE, errori IT in UI, test fixture XML validi/invalidi | ✅ |
| **39** | Invio xFIR firmato | Endpoint dedicato (se distinto da vidima), upload/submit payload COSE_Sign1, protocollo ministeriale, storico transazione | ✅ |
| **40** | Job retry MASE | Queue `RentriTransazione` failed → retry exponential backoff, dead-letter, UI stato job | ✅ |
| **41** | Conformità registro | Checklist ministeriale registro: payload campi obbligatori, lock movimento post-trasmissione, audit trail export | ✅ |
| **42** | Conformità FIR | Edge vidima (blocco esaurito, async timeout), firma pre-vidima blocked, QR payload spec | ✅ |
| **43** | Isolamento trasporti demo | Scope `MagazzinoSvuotamento` demo o FK guard; seed svuotamenti demo; test cross-ref | ✅ |
| **44** | Deploy demo CI/CD | Pipeline `.env.demo`, health demo, preflight variant demo, optional session admin toggle documentato | ✅ |
| **45** | Hardening + chiusura ciclo 3 | Pen-test checklist, monitoraggio, `GO-LIVE-CICLO-3.md`, backlog chiuso, regression full suite | ✅ |

---

## 5. Checklist conformità RENTRI/FIR per modulo CRM

Legenda: ✅ ciclo 2 | 🟡 parziale / gap ciclo 3 | ❌ non implementato

### 5.1 Impostazioni RENTRI / onboarding

| Requisito ministeriale / operativo | Stato | Sprint gap |
|-----------------------------------|-------|------------|
| mTLS certificato interoperabilità | ✅ | — |
| CF operatore + num_iscr_sito | ✅ | — |
| Test connessione health + codifiche | ✅ | — |
| Certificato firma remota xFIR distinto | ✅ | — |
| Ambiente sandbox vs produzione esplicito | ✅ | 36 demo force sandbox |
| Preflight automatizzato pre-go-live | ✅ | 44 variant demo ✅ |

### 5.2 FIR digitali (vidima)

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| Sync blocchi GET vidimazione-formulari | ✅ | — |
| Vidima async submit → poll → result | ✅ | — |
| Path v1.0 `{codice_blocco}` | ✅ | — |
| Progressivo blocco incrementale | ✅ | 42 edge esaurimento |
| QR payload post-vidima | ✅ | — |
| Validazione XSD xFIR pre/post build | ✅ | 38 |
| Invio payload firmato ministeriale | ✅ | 39 |

### 5.3 Firma xFIR (COSE)

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| Build XML xFIR | ✅ | — |
| COSE_Sign1 PKCS#12 firma remota | ✅ | — |
| Download payload firmato | ✅ | — |
| Validazione schema XSD completa | ✅ | 38 |
| Submit endpoint dedicato MASE | ✅ | 39 |

### 5.4 Registro movimenti / trasmissione

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| Mapping CER, tipo, quantità, data | ✅ | — |
| Trasmissione async + protocollo | ✅ | — |
| Lock movimento post-success | ✅ | — |
| Payload campi obbligatori audit | ✅ | — |
| Retry su indisponibilità MASE | ✅ | — |

### 5.5 Trasporti

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| Collegamento FIR ↔ trasporto | ✅ | — |
| Pesi partenza/arrivo | ✅ | — |
| Vidima/firma da dettaglio trasporto | ✅ | — |
| Isolamento demo svuotamenti | ✅ | 43 |

### 5.6 Storico / audit API

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| `rentri_transazioni` per call | ✅ | — |
| Correlation id | ✅ | — |
| Retry job queue | ✅ | — |
| Export audit conformità | ✅ | — |

### 5.7 Piattaforma demo

| Requisito | Stato | Sprint gap |
|-----------|-------|------------|
| Dati isolati da produzione | ✅ | 36 |
| No API api.rentri.gov.it in demo | ✅ | 36 |
| Reset dataset demo | ✅ | 36 |
| Seed scenario completo FIR→registro | ✅ | 37 |
| Walkthrough UI guidato | ✅ | 37 |

---

## 6. Riferimenti

- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) — §6 ciclo 3 **CHIUSO**
- [GO-LIVE-CICLO-3.md](GO-LIVE-CICLO-3.md) — runbook chiusura ciclo 3
- [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) — produzione ciclo 2
- [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md) — hardening Sprint 45
- [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) — health, dead-letter, alerting
- Sprint produzione: [SPRINT-31](SPRINT-31-RENTRI-PRODUZIONE.md) … [SPRINT-35](SPRINT-35-RENTRI-PRODUZIONE.md)

---

*Piano ciclo 3 — CHIUSO Sprint 45.*
