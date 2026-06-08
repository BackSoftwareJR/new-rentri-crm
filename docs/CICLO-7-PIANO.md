# Ciclo 7 — Enterprise RENTRI/FIR ministeriale ✅ CHIUSO

**Sprint 76–90** · Partenza: ciclo 6 chiuso (515 test, GO-LIVE-CICLO-6) · **Chiusura Sprint 90** (569+ test, GO-LIVE-ENTERPRISE)

**Obiettivo:** allineamento enterprise a RENTRI/FIR digitale (D.D. 143/2023, demoapi.rentri.gov.it) — audit, remediation P0/P1, review agenti.

**Pattern:** analizza → fix chirurgico → documenta → review (Sprint 77, 81, … REVIEW ONLY).

**Sign-off:** [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)

---

## Tabella sprint 76–90

| Sprint | Focus | Tipo | Stato |
|--------|-------|------|-------|
| **76** | Audit enterprise + remediation P0 (runtime mode, stub offline, COSE alg) | Fix | ✅ |
| **77** | **REVIEW ONLY** — QA fix S76, regression RENTRI/FIR | Review | ✅ |
| **78** | Blocchi sync update + preflight runtime mode | Fix | ✅ |
| **79** | **REVIEW ONLY** — QA fix S78 | Review | ✅ |
| **80** | Vidima validator service-layer | Fix | ✅ |
| **81** | **REVIEW ONLY** — QA fix S80 | Review | ✅ |
| **82** | Poll xFIR timeout config dedicato | Fix | ✅ |
| **83** | **REVIEW ONLY** — QA fix S82 | Review | ✅ |
| **84** | Contract test payload MASE | Test | ✅ |
| **85** | REVIEW ONLY — QA fix S84 | ✅ |
| **86** | UI copy stub/live sweep | Polish | ✅ |
| **87** | **REVIEW ONLY** — QA fix S86 | Review | ✅ |
| **88** | xFIR payload_firmato COSE audit + fix | Fix | ✅ |
| **89** | **REVIEW ONLY** — QA fix S88 | Review | ✅ |
| **90** | Chiusura ciclo 7 GO-LIVE-ENTERPRISE | Docs | ✅ |

---

## Sprint 76 — ✅ completato

1. **`docs/CICLO-7-ENTERPRISE-AUDIT.md`** — matrice conformità, P0/P1/P2, piano 76–90.
2. **P0-2** — `RentriRuntimeModeService::apiModeLabel()` usato in QR, registro, TrasportoShow.
3. **P0-3** — stub offline senza cert mTLS (`signRequestForMode`, `offlineStubHeaders`).
4. **P0-1** — COSE alg RS256/ES256 per tipo chiave PKCS#12.
5. **Test Sprint 76** — 7 test in `tests/Feature/Sprint76/*` (521 test totali).

### File principali

- `app/Domain/Rentri/RentriRuntimeModeService.php`
- `app/Services/Rentri/{RentriApiClient,RentriCertificateService,RentriFirQrPayloadBuilder,RentriRegistryService,RentriXfirCoseSigner}.php`
- `app/Http/Livewire/Segreteria/Trasporti/TrasportoShow.php`
- `docs/CICLO-7-ENTERPRISE-AUDIT.md`
- `docs/SPRINT-76-REVIEW-HANDOFF.md`

---

## Sprint 78 — ✅ completato

1. **P1-1** — `RentriFirBlocchiSync` aggiorna `progressivo_ultimo` su blocchi esistenti.
2. **P1-2** — `PreflightService` / `DemoPreflightService` usano `RentriRuntimeModeService`.
3. **Rentri.php** — fallback messaggio registro `api_mode` da runtime.
4. **Test Sprint 78** — 6 test in `tests/Feature/Sprint78/*` (527 test totali).

### File principali

- `app/Services/Rentri/RentriFirBlocchiSync.php`
- `app/Domain/Deploy/{PreflightService,DemoPreflightService}.php`
- `app/Http/Livewire/Segreteria/{Fir/FirBlocchiIndex,Rentri}.php`
- `docs/SPRINT-78-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 80 — Vidima validator (GO da Sprint 79)

Implementare P1-3 `RentriFirVidimaValidator`. Vedi [SPRINT-79-REVIEW-REPORT.md](SPRINT-79-REVIEW-REPORT.md) §5.

---

## Sprint 80 — ✅ completato

1. **P1-3** — `RentriFirVidimaValidator` gate CF, sito, cert mTLS (live), onboarding ≥ 3.
2. **`RentriFirService::vidima()`** — `assertReady()` prima submit API.
3. **UI TrasportoShow** — checklist pre-vidima IT con badge OK/KO.
4. **Test Sprint 80** — 7 test in `tests/Feature/Sprint80/*` (534 test totali).

### File principali

- `app/Domain/Rentri/RentriFirVidimaValidator.php`
- `app/Services/Rentri/{RentriFirService,Exceptions/RentriFirVidimaException}.php`
- `app/Http/Livewire/Segreteria/Trasporti/TrasportoShow.php`
- `resources/views/livewire/segreteria/trasporti/show.blade.php`
- `docs/SPRINT-80-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 81 — REVIEW ONLY (GO da Sprint 80)

Verificare P1-3. Vedi [SPRINT-80-REVIEW-HANDOFF.md](SPRINT-80-REVIEW-HANDOFF.md).

---

## Sprint 82 — ✅ completato

1. **P1-4** — config `xfir_poll_*` separato da `fir_poll_*` (default 20 / 300 ms).
2. **`RentriApiClient::waitXfirTrasmissioneResult()`** — usa config xFIR dedicato.
3. **`RentriXfirTransmissionMessageMapper`** — timeout IT con valori config xFIR.
4. **Test Sprint 82** — 6 test in `tests/Feature/Sprint82/*` (540 test totali).

### File principali

- `config/services.php`, `.env.example`
- `app/Services/Rentri/{RentriApiClient,RentriXfirTransmissionService,RentriXfirTransmissionMessageMapper}.php`
- `docs/SPRINT-82-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 83 — REVIEW ONLY (GO da Sprint 82)

Verificare P1-4. Vedi [SPRINT-82-REVIEW-HANDOFF.md](SPRINT-82-REVIEW-HANDOFF.md).

---

## Sprint 84 — ✅ completato

1. **P1-5** — fixture MASE vidima/xFIR/registro in `tests/fixtures/rentri/mase/`.
2. **Contract test** — campi obbligatori, tipi, enum CARICO/SCARICO su DTO esistenti.
3. **Test Sprint 84** — 7 test in `tests/Feature/Sprint84/*` (547 test totali).

### File principali

- `tests/fixtures/rentri/mase/{vidima-submit,xfir-trasmissione,registro-trasmissione}.json`
- `tests/Feature/Sprint84/RentriMasePayloadContractTest.php`
- `tests/Support/LoadsMaseFixtures.php`
- `docs/SPRINT-84-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 85 — REVIEW ONLY (GO da Sprint 84)

Verificare P1-5. Vedi [SPRINT-84-REVIEW-HANDOFF.md](SPRINT-84-REVIEW-HANDOFF.md).

---

## Sprint 86 — ✅ completato

1. **P2-1** — `RentriRuntimeModeService` label IT stub/live/demo offline + variant badge.
2. **Component** — `x-rentri-api-mode-badge` su TrasportoShow, RENTRI hub, FirIndex, tracking, RentriSettings.
3. **Test Sprint 86** — 8 test Livewire in `tests/Feature/Sprint86/*` (555 test totali).

### File principali

- `app/Domain/Rentri/RentriRuntimeModeService.php`
- `resources/views/components/rentri-api-mode-badge.blade.php`
- `resources/views/livewire/segreteria/{trasporti/show,rentri,fir/index}.blade.php`
- `resources/views/livewire/settings/rentri-settings.blade.php`
- `docs/SPRINT-86-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 87 — REVIEW ONLY (GO da Sprint 86)

Verificare P2-1. Vedi [SPRINT-86-REVIEW-HANDOFF.md](SPRINT-86-REVIEW-HANDOFF.md).

---

## Sprint 88 — ✅ completato

1. **Audit COSE** — mismatch M-88-1 metadati CRM in `payload_firmato` documentato.
2. **`RentriXfirCoseTransmissionMapper`** — strip `api_mode`, `numero_fir`, `firmato_at`, `stub`.
3. **Fixture** — `xfir-cose-sign1.json` + allineamento `xfir-trasmissione.json`.
4. **Test Sprint 88** — 7 test in `tests/Feature/Sprint88/*` (562 test totali).

### File principali

- `app/Services/Rentri/{RentriXfirCoseTransmissionMapper,Dto/RentriXfirTrasmissioneRequest}.php`
- `tests/fixtures/rentri/mase/xfir-cose-sign1.json`
- `docs/SPRINT-88-AUDIT-NOTES.md`
- `docs/SPRINT-88-REVIEW-HANDOFF.md`

---

## Istruzione Sprint 89 — REVIEW ONLY (GO da Sprint 88)

Verificare audit payload_firmato. Vedi [SPRINT-88-REVIEW-HANDOFF.md](SPRINT-88-REVIEW-HANDOFF.md).

---

## Sprint 89 — ✅ completato (REVIEW ONLY)

1. **Review COSE mapper** — 7/7 checklist audit Sprint 88 verificata.
2. **Regression** — Sprint 34/39/84/88 + suite 562 passed.
3. **Report** — `docs/SPRINT-89-REVIEW-REPORT.md` con raccomandazione **GO** Sprint 90.

---

## Sprint 90 — ✅ completato

1. **`docs/GO-LIVE-ENTERPRISE.md`** — checklist post-remediation P0–P2 + smoke commands.
2. **`docs/CICLO-7-ENTERPRISE-AUDIT.md`** — matrice conformità finale ✅ P0/P1/P2.
3. **Banner CHIUSO** — CICLO-7-PIANO, backlog §10, README.
4. **Test Sprint 90** — 7 test doc presence + preflight smoke in `tests/Feature/Sprint90/*` (569 test totali).

### File principali

- `docs/GO-LIVE-ENTERPRISE.md`
- `docs/CICLO-7-ENTERPRISE-AUDIT.md`
- `docs/CICLO-7-PIANO.md`
- `docs/RENTRI_VERTICAL_BACKLOG.md` §10
- `tests/Feature/Sprint90/Cycle7ClosureGoLiveTest.php`

---

## Gap residui post-ciclo 7

Vedi [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md) §6 — validazione live cert sandbox, integration CI opzionale, SLA monitoring, eredità cicli 5–6 (WAF, 2FA, deploy infra).

---

## Riferimenti

- [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md)
- [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §10
