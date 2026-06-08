# Ciclo 7 Enterprise — Audit RENTRI/FIR ministeriale

**Ciclo 7 CHIUSO ✅ · Sprint 90** · Confronto codebase vs D.D. 143/2023, D.D. 251/2023, [demoapi.rentri.gov.it/docs](https://demoapi.rentri.gov.it/docs?page=api-flussi-operativi-formulari).

**Pattern lavoro:** analizza → fix chirurgico → documenta → agente review (Sprint 77, 79, 81, … REVIEW ONLY).

**Sign-off:** [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)

---

## 1. Riferimenti normativi e tecnici

| Fonte | Contenuto rilevante |
|-------|---------------------|
| **D.D. 143/2023** | Modalità operative 8–12: registro cronologico digitale, FIR xFIR, vidimazione interoperabilità, trasmissione dati |
| **D.D. 251/2023** | Istruzioni compilazione FIR e registro; modello FIR dal 13/02/2025 |
| **demoapi.rentri.gov.it** | API v1.0: vidimazione `POST /vidimazione-formulari/v1.0/{codice_blocco}`, async status/result, COSE_Sign1 in QR base45 |
| **supporto.rentri.gov.it** | xFIR: firme produttore/trasportatore/destinatario; trasmissione dati rifiuti pericolosi (mod. operativa 12) |

**Distinzione certificati:**
- **mTLS interoperabilità** — PKCS#12 su `RentriCertificateService` (identità operatore verso API)
- **Firma remota xFIR** — certificato separato su `RentriFirmaCertificateService` (COSE_Sign1 payload)

---

## 2. Matrice conformità capability (finale Sprint 90)

| Capability | Stato | Note audit |
|------------|-------|------------|
| **Account / onboarding RENTRI** | ✅ OK | Wizard 4 step, CF operatore, test connessione |
| **mTLS client API** | ✅ OK | Guzzle `cert` option; health live via blocchi FIR |
| **Vidimazione FIR async** | ✅ OK | submit → poll status → result; `RentriFirService::vidima()` |
| **Recupero blocchi FIR** | ✅ OK | Sync insert + update `progressivo_ultimo` (S78) |
| **Payload vidima body** | ⚠️ Parziale | Campi CRM (`trasporto_id`) nel body locale; contract test S84 verifica shape minima — conferma OpenAPI MASE in sandbox |
| **Registro cronologico trasmissione** | ✅ OK | Mapping `tipo_movimento`, `quantita_kg`, periodo; lock movimenti post-successo |
| **Checklist conformità registro** | ✅ OK | `RentriRegistroConformitaValidator` pre-invio UI |
| **xFIR build + XSD** | ✅ OK | Schema `xfir-v1.0.xsd`, messaggi IT |
| **xFIR COSE_Sign1 firma** | ✅ OK | RS256/ES256 per tipo chiave (S76); stub/test verificati |
| **xFIR trasmissione MASE** | ✅ OK | Endpoint dedicato, poll async xFIR dedicato (S82), protocollo persistito |
| **QR payload post-vidima** | ✅ OK | `api_mode` da `RentriRuntimeModeService` (S76) |
| **Error mapping HTTP/vidima** | ✅ OK | `RentriApiClient::translateHttpError`, `RentriFirVidimaMessageMapper` |
| **Retry / dead-letter MASE** | ✅ OK | `RetryRentriTransazioneJob`, KPI dashboard |
| **UI stub/live copy** | ✅ OK | `x-rentri-api-mode-badge` + runtime mode (S76, S86) |
| **Stub palestra senza cert** | ✅ OK | `signRequestForMode` + `offlineStubHeaders` (S76) |
| **Pre-vidima validator service** | ✅ OK | `RentriFirVidimaValidator` gate CF/sito/cert/onboarding (S80) |
| **Preflight runtime mode** | ✅ OK | `PreflightService` / `DemoPreflightService` usano runtime (S78) |
| **Payload xFIR transmit** | ✅ OK | `RentriXfirCoseTransmissionMapper` — solo 5 chiavi COSE MASE (S88) |

**Legenda:** ✅ OK = implementato e testato · ⚠️ Parziale = riserva non bloccante · Gap = mancante (nessuno P0–P2)

---

## 3. Bug ed errori logici (priorità)

### P0 — bloccanti go-live enterprise

| ID | Descrizione | File | Sprint 76 |
|----|-------------|------|-----------|
| **P0-1** | COSE live dichiarava `ES256` con firma RSA-SHA256 | `RentriXfirCoseSigner` | ✅ RS256/ES256 per tipo chiave |
| **P0-2** | `api_mode` / label stub ignoravano `RentriRuntimeModeService` dopo passaggio live UI | QR builder, Registry, TrasportoShow | ✅ |
| **P0-3** | Stub API richiedeva cert mTLS anche in palestra offline senza PKCS#12 | `RentriApiClient::executePath` | ✅ `signRequestForMode` + offline headers |

### P1 — inconsistenza operativa

| ID | Descrizione | Target sprint |
|----|-------------|---------------|
| **P1-1** | Sync blocchi non aggiorna progressivi MASE esistenti | Sprint 78 | ✅ |
| **P1-2** | Preflight non riflette runtime live da DB | Sprint 78 | ✅ |
| **P1-3** | Vidima senza validator service (CF, sito, cert, onboarding) | Sprint 80 | ✅ |
| **P1-4** | Poll xFIR riusa timeout FIR vidima | Sprint 82 | ✅ |
| **P1-5** | Payload vidima/xFIR da validare vs OpenAPI MASE | Sprint 84 | ✅ |

### P2 — polish / osservabilità

| ID | Descrizione | Target sprint |
|----|-------------|---------------|
| **P2-1** | Copy tracking «stub» sempre visibile | Sprint 86 | ✅ |
| **P2-2** | Test integrazione sandbox opzionale CI | — | ☐ backlog ciclo 8 |
| **P2-3** | Metriche SLA RENTRI enterprise | — | ☐ backlog ciclo 8 |

### Remediation aggiuntiva (post-audit)

| ID | Descrizione | Sprint | Stato |
|----|-------------|--------|-------|
| **M-88-1** | Metadati CRM in `payload_firmato` xFIR | Sprint 88 | ✅ `RentriXfirCoseTransmissionMapper` |

---

## 4. Piano sprint 76–90

| Sprint | Focus | Tipo |
|--------|-------|------|
| **76** | Remediation P0: runtime mode, stub offline, COSE alg | Fix + audit doc |
| **77** | **REVIEW ONLY** — QA fix S76, regression RENTRI/FIR | Review |
| **78** | P1: blocchi sync update + preflight runtime | Fix |
| **79** | P1: `RentriFirVidimaValidator` service-layer | Fix |
| **80** | Poll xFIR timeout config dedicato | Fix |
| **81** | Contract test payload vidima/registro vs fixture MASE | Test |
| **82** | UI copy stub/live sweep trasporti/RENTRI | Polish |
| **83** | Vidima checklist UI (parità registro) | UX |
| **84** | xFIR `payload_firmato` shape audit + fix | Sprint 88 | ✅ |
| **85** | Error mapping codici MASE aggiuntivi | Fix |
| **86** | Integration test sandbox opzionale CI | Test |
| **87** | COSE conformance test fixture reale | Test |
| **88** | Registro campi opzionali MASE (audit diff) | Fix |
| **89** | Monitoring enterprise RENTRI (SLA, alert) | Ops |
| **89** | **REVIEW ONLY** — QA fix S88 COSE mapper | Review | ✅ |
| **90** | Chiusura ciclo 7 + GO-LIVE-ENTERPRISE | Docs | ✅ |

---

## 5. Fix Sprint 76 applicati

1. **`RentriRuntimeModeService`** — `apiModeLabel()`, `apiModeDisplayLabel()`.
2. **`RentriFirQrPayloadBuilder`**, **`RentriRegistryService`**, **`TrasportoShow`** — runtime mode al posto di `config('api_stub')`.
3. **`RentriApiClient::executePath`** — cert obbligatorio solo in live; stub offline senza cert.
4. **`RentriCertificateService::signRequestForMode`** + `offlineStubHeaders()`.
5. **`RentriXfirCoseSigner`** — `RS256` per chiavi RSA, `ES256` per EC.

**Test:** `tests/Feature/Sprint76/RentriEnterpriseP0RemediationTest.php` (7 test).

---

## 6. Esito finale ciclo 7

| Priorità | Totale | Remediati | Residui |
|----------|--------|-----------|---------|
| **P0** | 3 | 3 ✅ | 0 |
| **P1** | 5 | 5 ✅ | 0 |
| **P2** | 1 (P2-1) | 1 ✅ | P2-2, P2-3 → ciclo 8 |
| **Audit COSE** | M-88-1 | ✅ | 0 |

**Suite test:** 569+ PHPUnit (Sprint 90). Smoke: [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md) §2.

---

## Riferimenti

- [CICLO-7-PIANO.md](CICLO-7-PIANO.md)
- [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)
- [SPRINT-89-REVIEW-REPORT.md](SPRINT-89-REVIEW-REPORT.md)
- [SPRINT-88-AUDIT-NOTES.md](SPRINT-88-AUDIT-NOTES.md)
- [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md)
- [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md)
