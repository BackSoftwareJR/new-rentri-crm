# Sprint 76 — Review handoff (agente 2)

**Destinatario:** agente Sprint 77 · **REVIEW ONLY** — nessuna nuova feature, nessun fix P1.

**Contesto:** Ciclo 7 Enterprise — remediation P0 RENTRI/FIR dopo audit [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md).

---

## Cosa è stato fixato (Sprint 76)

| P0 | Fix | File |
|----|-----|------|
| P0-1 | COSE alg RS256 (RSA) / ES256 (EC) coerente con chiave | `RentriXfirCoseSigner.php` |
| P0-2 | `api_mode` e UI stub/live da `RentriRuntimeModeService` | `RentriRuntimeModeService.php`, `RentriFirQrPayloadBuilder.php`, `RentriRegistryService.php`, `TrasportoShow.php` |
| P0-3 | Stub offline palestra senza cert mTLS | `RentriApiClient.php`, `RentriCertificateService.php` |

---

## Checklist review Sprint 77

### Conformità logica

- [ ] Abilitare passaggio live UI (`RentriLiveModeService::enable`) con `RENTRI_API_STUB=true` in env → badge/messaggi devono dire **live**, non stub.
- [ ] QR payload post-vidima: campo `api_mode` = `live` quando `live_mode_enabled_at` valorizzato.
- [ ] Trasporto show: assenza testo «invio via API stub» dopo passaggio live.
- [ ] Registro trasmissione: `response_json.api_mode` = `live` coerente con runtime.
- [ ] Demo offline (`demo.rentri.offline_no_http=true`) senza cert: `healthCheck()` ritorna OK stub.

### Regression test

```bash
cd new-rentri-crm
php artisan test --filter=Sprint76
php artisan test --filter=Sprint69
php artisan test --filter=Sprint34
php artisan test --filter=Sprint39
php artisan test --filter=Sprint42
php artisan test --filter=Sprint36
php artisan test                              # suite completa 522+
```

### Casi manuali (staging)

1. Impostazioni RENTRI → passaggio produzione (checklist 6 voci) → verificare copy dashboard/trasporto.
2. Vidima FIR sandbox con cert → QR payload JSON contiene `api_mode`.
3. Palestra operativa senza cert caricato → test connessione / health non deve crashare in offline stub.

### Non in scope Sprint 77

- Sync blocchi update (P1-1) → Sprint 78
- Preflight runtime (P1-2) → Sprint 78
- Vidima validator service (P1-3) → Sprint 79
- Modifiche payload MASE → Sprint 81+

---

## Output atteso agente 2

1. **`docs/SPRINT-77-REVIEW-REPORT.md`** con esito ✅/⚠️/❌ per ogni voce checklist.
2. Conferma test count e eventuali regressioni note (senza fix, solo report).
3. Raccomandazione GO/NO-GO per Sprint 78 (P1 blocchi sync + preflight).

---

## Istruzione ESATTA agente 3 (Sprint 78)

**Dopo review Sprint 77 approvata:**

Implementare **P1-1 + P1-2** da audit §3:

1. **`RentriFirBlocchiSync`** — aggiornare `progressivo_ultimo` (e campi MASE) per blocchi già presenti, non solo insert.
2. **`PreflightService` / `DemoPreflightService`** — check `rentri_stub` / `rentri_live` usando `RentriRuntimeModeService`, non solo env.
3. Test Sprint 78 ≥5; 522+ test verdi; aggiornare audit doc status P1-1/P1-2.
4. Handoff `docs/SPRINT-78-REVIEW-HANDOFF.md` per agente review successivo.

**Vincoli:** fix chirurgici, no commit/push, no nuovi moduli fuori RENTRI/FIR.

---

## File toccati Sprint 76

```
app/Domain/Rentri/RentriRuntimeModeService.php
app/Services/Rentri/RentriApiClient.php
app/Services/Rentri/RentriCertificateService.php
app/Services/Rentri/RentriFirQrPayloadBuilder.php
app/Services/Rentri/RentriRegistryService.php
app/Services/Rentri/RentriXfirCoseSigner.php
app/Http/Livewire/Segreteria/Trasporti/TrasportoShow.php
tests/Feature/Sprint76/RentriEnterpriseP0RemediationTest.php
docs/CICLO-7-ENTERPRISE-AUDIT.md
docs/CICLO-7-PIANO.md
docs/RENTRI_VERTICAL_BACKLOG.md (§10)
```
