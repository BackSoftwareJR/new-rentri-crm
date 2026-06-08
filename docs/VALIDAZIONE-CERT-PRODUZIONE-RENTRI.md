# Validazione certificato produzione RENTRI

**Sprint 111 · Ciclo 10** · Test E2E verso [api.rentri.gov.it](https://api.rentri.gov.it) con certificato operatore produzione.

**Prerequisito sandbox:** completare [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md) prima del passaggio produzione.

**Runbook switch:** [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md)

---

## 1. Prerequisiti

| Requisito | Dove verificare |
|-----------|-----------------|
| Certificato PKCS#12 **produzione** mTLS | Wizard RENTRI step 2 |
| Certificato firma xFIR produzione | Sezione firma remota |
| `RENTRI_ENV=production` | `.env` |
| `RENTRI_API_STUB=false` · `RENTRI_FIRMA_STUB=false` | `.env` o override live UI step 4 |
| Wizard ambiente «Produzione» | Step 1 |
| Base URL `https://api.rentri.gov.it` | `RENTRI_BASE_URL_PRODUCTION` |
| **demoapi bloccato** in prod | Service `RentriProductionCertValidationService` |

**Sicurezza:** non committare certificati produzione. Usare storage locale o secret CI dedicati (mai workflow default).

---

## 2. UI — Validazione certificato produzione

1. `/segreteria/impostazioni/rentri` — onboarding completato.
2. Sezione **«Validazione certificato produzione»** — verifica prerequisiti.
3. **«Esegui validazione certificato produzione»** — health + codifiche CER su api.rentri.gov.it.
4. Vidima FIR **non** eseguita automaticamente (§5).

---

## 3. Variabili ambiente (integrazione manuale)

```bash
RENTRI_ENV=production
RENTRI_API_STUB=false
RENTRI_FIRMA_STUB=false
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_PRODUCTION_INTEGRATION_TEST=false

# Solo test manuale fuori repo (mai CI default)
RENTRI_PRODUCTION_INTEGRATION_TEST=true
RENTRI_PRODUCTION_CERT_PATH=/path/assoluto/produzione-operatore.p12
RENTRI_PRODUCTION_CERT_PASSWORD=password-keystore
```

| Variabile | Descrizione |
|-----------|-------------|
| `RENTRI_PRODUCTION_INTEGRATION_TEST` | Abilita `RentriProductionIntegrationTest` (default `false`) |
| `RENTRI_PRODUCTION_CERT_PATH` | PKCS#12 produzione fuori repo |
| `RENTRI_PRODUCTION_CERT_PASSWORD` | Password keystore |

**Nota:** distinto da `RENTRI_INTEGRATION_TEST` (sandbox demoapi).

---

## 4. Test integrazione PHPUnit

```bash
cd new-rentri-crm

RENTRI_ENV=production \
RENTRI_API_STUB=false \
RENTRI_FIRMA_STUB=false \
RENTRI_PRODUCTION_INTEGRATION_TEST=true \
RENTRI_PRODUCTION_CERT_PATH=/path/to/produzione.p12 \
RENTRI_PRODUCTION_CERT_PASSWORD=secret \
php artisan test --filter=RentriProductionIntegrationTest
```

Senza env → test **skipped** (suite CI resta verde, **nessuna chiamata api.rentri.gov.it**).

---

## 5. Vidimazione FIR dry-run (manuale produzione)

La validazione automatica Sprint 111 **non esegue vidima** su api.rentri.gov.it.

**Procedura controllata:**

1. Completare validazione produzione (health + codifiche OK).
2. Eseguire `rentri:production-switch-check` SUCCESS.
3. Sincronizzare blocchi FIR da RENTRI.
4. Vidima su blocco di test dedicato — verificare checklist `RentriFirVidimaValidator`.
5. Monitoraggio 48h post-prima-vidima ([RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md)).

---

## 6. Smoke commands Sprint 111

```bash
php artisan test --filter=Sprint111
php artisan test --filter=RentriProductionIntegrationTest   # skipped senza env
php artisan rentri:production-switch-check --dry-run
php artisan rentri:preflight
```

---

## 7. Troubleshooting

| Sintomo | Azione |
|---------|--------|
| demoapi in produzione | Verificare `RENTRI_BASE_URL_PRODUCTION` e ambiente wizard |
| Health OK ma 0 codifiche | Permessi certificato produzione |
| Integration test skipped | Impostare `RENTRI_PRODUCTION_INTEGRATION_TEST` + `RENTRI_PRODUCTION_CERT_PATH` |
| Demo mode blocca prod | `DemoContext` impedisce api.rentri.gov.it in palestra |

---

## Riferimenti

- [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)
- [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md)
- [SPRINT-111-REVIEW-HANDOFF.md](SPRINT-111-REVIEW-HANDOFF.md)
