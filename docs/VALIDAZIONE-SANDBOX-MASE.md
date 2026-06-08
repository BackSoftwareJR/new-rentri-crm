# Validazione sandbox MASE — guida operatore

**Sprint 91 · Ciclo 8** · Test reale verso [demoapi.rentri.gov.it](https://demoapi.rentri.gov.it) con certificato PKCS#12 sandbox.

**MASE non usa API key pubblica:** l'autenticazione avviene via **mTLS** con certificato interoperabilità rilasciato da CA dominio RENTRI o eIDAS.

---

## 1. Prerequisiti

| Requisito | Dove verificare |
|-----------|-----------------|
| Certificato PKCS#12 sandbox (.p12 / .pfx) | Portale RENTRI / supporto operatore |
| Dati operatore (CF, num_iscr_sito) | Wizard impostazioni RENTRI step 1 |
| Ambiente sandbox | `ambiente=sandbox` in impostazioni |
| Rete verso demoapi | Firewall egress HTTPS verso `demoapi.rentri.gov.it` |

**Sicurezza:** non committare certificati reali nel repository. Usare storage locale o secret CI.

---

## 2. Upload certificato via UI

1. Accedere a **Impostazioni RENTRI** (`/segreteria/impostazioni/rentri`).
2. Completare **step 1** (dati operatore) e **step 2** (upload PKCS#12 + password).
3. **Step 3** — «Esegui test connessione» (health + codice CER campione).
4. Sezione **«Validazione reale sandbox MASE»** — «Esegui test reale MASE» per wizard step-by-step completo.

---

## 3. Variabili ambiente (integrazione / CI)

Aggiungere al `.env` locale (mai committare certificati):

```bash
# Default: stub locale, nessuna chiamata MASE
RENTRI_API_STUB=true
RENTRI_INTEGRATION_TEST=false

# Test integrazione manuale o CI gated (Sprint 91+)
RENTRI_INTEGRATION_TEST=true
RENTRI_API_STUB=false
RENTRI_SANDBOX_CERT_PATH=/path/assoluto/sandbox-operatore.p12
RENTRI_SANDBOX_CERT_PASSWORD=password-keystore
```

| Variabile | Descrizione |
|-----------|-------------|
| `RENTRI_INTEGRATION_TEST` | Abilita `RentriIntegrationTest` (default `false`) |
| `RENTRI_SANDBOX_CERT_PATH` | Percorso assoluto PKCS#12 sandbox fuori repo |
| `RENTRI_SANDBOX_CERT_PASSWORD` | Password keystore certificato |
| `RENTRI_API_STUB` | Deve essere `false` per chiamate live demoapi |

**Preflight:** se `RENTRI_SANDBOX_CERT_PATH` è impostato, `php artisan rentri:preflight` verifica che il file sia leggibile.

---

## 4. Test integrazione PHPUnit

```bash
cd new-rentri-crm

# Solo con cert reale fuori repo
RENTRI_API_STUB=false \
RENTRI_INTEGRATION_TEST=true \
RENTRI_SANDBOX_CERT_PATH=/path/to/sandbox.p12 \
RENTRI_SANDBOX_CERT_PASSWORD=secret \
php artisan test --filter=RentriIntegrationTest
```

Test eseguiti quando env presenti:

- Health check (blocchi FIR)
- Codifiche CER
- Fetch blocchi FIR

Senza env → test **skipped** (suite CI resta verde).

---

## 5. Vidimazione FIR dry-run (manuale)

La validazione automatica Sprint 91 **non esegue vidima** per evitare consumo progressivi/blocchi FIR in sandbox.

**Procedura controllata:**

1. Completare validazione sandbox (health + codifiche OK).
2. Sincronizzare blocchi FIR da RENTRI (`/segreteria/fir/blocchi`).
3. Creare trasporto demo con blocco **non esaurito** dedicato a test.
4. Eseguire vidima da dettaglio trasporto — verificare checklist pre-vidima (`RentriFirVidimaValidator`).
5. Verificare protocollo, QR payload e activity log.
6. **Non** procedere a firma/xFIR/trasmissione finché non validati payload e certificato firma xFIR.

Documentazione vidima: [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) · [SPRINT-32-RENTRI-PRODUZIONE.md](SPRINT-32-RENTRI-PRODUZIONE.md).

---

## 6. Smoke commands Sprint 91

```bash
php artisan test --filter=Sprint91
php artisan test --filter=RentriIntegrationTest   # skipped senza env
php artisan rentri:preflight
php artisan rentri:preflight --demo
```

---

## 7. Troubleshooting

| Sintomo | Azione |
|---------|--------|
| «Autenticazione RENTRI fallita» | Verificare certificato scaduto o password errata |
| Health OK ma 0 codifiche CER | Permessi certificato sandbox insufficienti |
| Stub sempre attivo | `RENTRI_API_STUB=true` o palestra `RENTRI_DEMO_NO_HTTP=true` |
| Integration test skipped | Impostare entrambi `RENTRI_INTEGRATION_TEST` e `RENTRI_SANDBOX_CERT_PATH` |

---

## 8. CI gated (GitHub Actions)

Workflow dedicato: `.github/workflows/rentri-sandbox-integration.yml`

**Non esegue su push/PR default** — nessun traffic MASE automatico su `main`.

### Trigger

| Trigger | Quando |
|---------|--------|
| `workflow_dispatch` | Run manuale da tab Actions → «RENTRI Sandbox Integration» |
| Label PR `integration-sandbox` | Aggiungere label alla pull request |

### Repository secrets (Settings → Secrets → Actions)

| Secret | Contenuto |
|--------|-----------|
| `RENTRI_SANDBOX_CERT_BASE64` | PKCS#12 sandbox codificato base64 (una riga, senza header PEM) |
| `RENTRI_SANDBOX_CERT_PASSWORD` | Password keystore certificato |

Generare base64 locale (non committare):

```bash
base64 -i /path/to/sandbox-operatore.p12 | tr -d '\n' | pbcopy   # macOS
# oppure: base64 -w0 sandbox-operatore.p12                       # Linux
```

### Comportamento gated

1. Se **secrets assenti** → job termina con notice «skipped» (exit 0, nessuna chiamata demoapi).
2. Se **secrets presenti** → decode cert in `${RUNNER_TEMP}/rentri-sandbox-*.p12` (chmod 600), export env, esegue:

   ```bash
   php artisan test --filter=RentriIntegrationTest
   ```

3. **Cleanup** — file temp rimosso in step `always()` (cert/password mai loggati).

### Smoke CI locale (simulazione skip)

```bash
php artisan test --filter=RentriIntegrationTest   # skipped senza env
php artisan test --filter=Sprint92                # verifica workflow + doc
```

Il workflow `production.yml` **non** include `RentriIntegrationTest`.

---

## Riferimenti

- [CICLO-8-PIANO.md](CICLO-8-PIANO.md)
- [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)
- [demoapi.rentri.gov.it/docs](https://demoapi.rentri.gov.it/docs)
- [SPRINT-91-REVIEW-HANDOFF.md](SPRINT-91-REVIEW-HANDOFF.md)
- [SPRINT-92-REVIEW-HANDOFF.md](SPRINT-92-REVIEW-HANDOFF.md)
