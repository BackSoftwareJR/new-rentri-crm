# PRODUCTION SWITCH — Runbook Unificato

**Versione:** Sprint 121 · Ciclo 10 · 8 giugno 2026  
**Autore:** Tech Lead / Ops  
**Deadline normativa:** ⚠️ **15 settembre 2026** — sanzioni MASE attive; dual-track FIR cartaceo/digitale termina

> **Relazioni:** Questo documento unifica e sostituisce come riferimento operativo [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md) e le sezioni di switch di [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md). Quei file restano come riferimento storico di sprint.

---

## Indice

1. [Prerequisiti e deadline normativa](#1-prerequisiti-e-deadline-normativa)
2. [Pre-flight checklist completa](#2-pre-flight-checklist-completa)
3. [Step-by-step: variabili ambiente](#3-step-by-step-variabili-ambiente)
4. [Comandi Artisan — ordine esatto](#4-comandi-artisan--ordine-esatto)
5. [Switch RENTRI (passi specifici)](#5-switch-rentri-passi-specifici)
6. [Switch altri servizi](#6-switch-altri-servizi)
7. [Checklist di verifica post-switch](#7-checklist-di-verifica-post-switch)
8. [Procedura di rollback](#8-procedura-di-rollback)
9. [Monitoraggio post-go-live](#9-monitoraggio-post-go-live)
10. [Sign-off](#10-sign-off)

---

## 1. Prerequisiti e deadline normativa

### 1.1 Deadline critica

| Data | Evento |
|------|--------|
| **15 settembre 2026** | Sanzioni MASE attive per mancata trasmissione FIR digitale |
| **15 settembre 2026** | Fine periodo transitorio dual-track (FIR cartaceo + digitale) |
| Entro **1 agosto 2026** | Completare UAT e switch sandbox → produzione con anticipo sufficiente |

> ⚠️ **Non aspettare il 14 settembre.** Il sistema deve essere in produzione almeno 6 settimane prima per validare trasmissioni reali e risolvere eventuali anomalie MASE.

### 1.2 Prerequisiti prima di iniziare

- [ ] Certificato interoperabilità PKCS#12 (mTLS) rilasciato da CA dominio RENTRI o eIDAS — **non scaduto**
- [ ] Certificato firma remota xFIR PKCS#12 — **distinto** dal precedente, **non scaduto**
- [ ] Iscrizione operatore completata su [rentri.gov.it](https://www.rentri.gov.it) (SPID/CNS/CIE)
- [ ] `num_iscr_sito`, CF operatore e P.IVA pronti
- [ ] Sandbox MASE validato: almeno 1 vidima FIR e 1 trasmissione registro completate su `demoapi.rentri.gov.it`
- [ ] Backup DB eseguito e verificato (restore drill)
- [ ] Queue workers attivi e supervisord/Laravel Horizon configurato
- [ ] Build frontend (`npm run build`) completata senza errori

---

## 2. Pre-flight checklist completa

Eseguire **prima** di qualsiasi modifica `.env`.

```bash
# 1. Build assets (se non già fatto)
npm run build

# 2. Preflight automatizzato
php artisan rentri:preflight

# 3. Switch check RENTRI (dry-run, non modifica nulla)
php artisan rentri:production-switch-check --dry-run
```

### 2.1 Check preflight (`rentri:preflight`)

| Check | Fail se | Warn se | Note |
|-------|---------|---------|------|
| `app_key` | APP_KEY vuota | — | Obbligatorio |
| `app_debug` | APP_DEBUG=true in production | in staging | |
| `database` | DB non raggiungibile | — | |
| `vite_manifest` | Build assente | — | |
| `rentri_cert` | Live senza cert mTLS o scaduto | Stub attivo | |
| `rentri_firma_cert` | Firma live senza cert o scaduto | Firma stub attiva | |
| `rentri_operator` | Live senza CF/P.IVA/sito o onboarding incompleto | Stub attivo | |
| `rentri_stub` | — | Stub in production | |
| `rentri_firma_stub` | — | Firma stub in production | |

**Go-live:** tutti `ok` o solo `warn` accettabili. **Nessun `fail` bloccante.**

### 2.2 Check switch check (`rentri:production-switch-check`)

| Gruppo | Chiave | Descrizione |
|--------|--------|-------------|
| `env` | `rentri_env` | `RENTRI_ENV=production` |
| `env` | `api_stub_off` | `RENTRI_API_STUB=false` o override live UI |
| `env` | `firma_stub_off` | `RENTRI_FIRMA_STUB=false` o firma live UI |
| `env` | `runtime_api_live` | Runtime API verso MASE (non stub) |
| `env` | `runtime_firma_live` | Runtime firma xFIR (non stub) |
| `env` | `production_base_url` | `RENTRI_BASE_URL_PRODUCTION` configurato |
| `ui` | `ambiente_produzione` | Impostazioni RENTRI: ambiente = produzione |
| `ui` | `cert_mtls` | Certificato interoperabilità valido e caricato |
| `ui` | `cert_firma` | Certificato firma xFIR valido e caricato |
| `ui` | `dati_operatore` | CF, P.IVA, num_iscr_sito compilati |
| `ui` | `onboarding` | Onboarding completato (step 3) |
| `ui` | `health_ok` | Ultimo health check OK |
| `preflight` | `preflight` | `rentri:preflight` senza FAIL |
| `security` | `waf_block_gate` | WAF in modalità block **[opzionale]** |

**Output atteso:**

```
Switch produzione: PRONTO (dry-run).
```

---

## 3. Step-by-step: variabili ambiente

### Ordine di modifica `.env`

> Eseguire le modifiche **una sezione per volta** e verificare dopo ogni gruppo.

#### Step 3.1 — Ambiente applicazione

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://crm.tuodominio.it
```

#### Step 3.2 — Database e sessioni

```env
DB_CONNECTION=mysql
DB_HOST=<host-prod>
DB_PORT=3306
DB_DATABASE=rentri_crm
DB_USERNAME=<utente-prod>
DB_PASSWORD=<password-prod>

SESSION_DRIVER=database
SESSION_ENCRYPT=true
```

#### Step 3.3 — Logging produzione

```env
LOG_CHANNEL=stack_prod
LOG_STACK_PROD=json_daily,rentri,security,integration,business
LOG_LEVEL=info
LOG_DAILY_DAYS=90
APP_LOG_PERSIST_DB=true
APP_LOG_RETENTION_DAYS=90
```

#### Step 3.4 — Queue e cache (Redis consigliato in produzione)

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=<redis-host>
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379
```

#### Step 3.5 — RENTRI (passi in ordine)

```env
# 1. Ambiente RENTRI
RENTRI_ENV=production
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_BASE_URL_SANDBOX=https://demoapi.rentri.gov.it

# 2. Disabilita stub
RENTRI_API_STUB=false
RENTRI_FIRMA_STUB=false

# 3. Autenticazione mTLS
RENTRI_AUTH_MODE=mtls
RENTRI_VERIFY_SSL=true
RENTRI_HTTP_TIMEOUT=30

# 4. Polling async (valori consigliati produzione)
RENTRI_FIR_POLL_MAX_ATTEMPTS=15
RENTRI_FIR_POLL_INTERVAL_MS=500
RENTRI_REGISTRO_POLL_MAX_ATTEMPTS=15
RENTRI_REGISTRO_POLL_INTERVAL_MS=500
RENTRI_XFIR_POLL_MAX_ATTEMPTS=20
RENTRI_XFIR_POLL_INTERVAL_MS=500

# 5. Retry automatico
RENTRI_RETRY_ENABLED=true
RENTRI_RETRY_MAX_ATTEMPTS=5
RENTRI_RETRY_BASE_DELAY_SECONDS=60
RENTRI_RETRY_MAX_DELAY_SECONDS=3600

# 6. SLA soglie
RENTRI_SLA_P95_LATENCY_SECONDS=120
RENTRI_SLA_DEAD_LETTER_RATE_PERCENT=5
RENTRI_SLA_MAX_AVG_RETRY_COUNT=1

# Mai committare percorsi certificati reali — configurare via UI wizard
# RENTRI_PRODUCTION_CERT_PATH=  (non usare in .env condiviso)
# RENTRI_PRODUCTION_CERT_PASSWORD=  (non usare in .env condiviso)
RENTRI_INTEGRATION_TEST=false
RENTRI_PRODUCTION_INTEGRATION_TEST=false
```

#### Step 3.6 — Demo mode (produzione: off)

```env
APP_DEMO_MODE=false
ALLOW_SESSION_DEMO=false
RENTRI_DEMO_FORCE_SANDBOX=false
RENTRI_DEMO_NO_HTTP=false
```

#### Step 3.7 — SMTP notifiche

```env
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host-prod>
MAIL_PORT=587
MAIL_USERNAME=<smtp-user>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS="noreply@tuodominio.it"
MAIL_FROM_NAME="RENTRI CRM"
NOTIFICATIONS_LIVE=true
NOTIFICATIONS_QUEUE=true
NOTIFICATIONS_SMTP_RATE_LIMIT_PER_MINUTE=30
NOTIFICATIONS_SMTP_DAILY_CAP=500
```

#### Step 3.8 — Stripe e-commerce

```env
ECOMMERCE_PAYMENT_STUB=false
STRIPE_KEY=sk_live_<chiave-live>
STRIPE_WEBHOOK_SECRET=whsec_<secret-live>
STRIPE_CURRENCY=eur
STRIPE_LIVE_MODE=true
STRIPE_DISPUTE_STUB=false
STRIPE_RECONCILIATION_DAYS=30
```

#### Step 3.9 — MUD telematico

```env
MUD_TELEMATICO_STUB=false
MUD_TELEMATICO_ENV=production
# MUD_TELEMATICO_BASE_URL=  (default: usa RENTRI_BASE_URL_PRODUCTION)
MUD_TELEMATICO_TIMEOUT=30
MUD_TELEMATICO_POLL_MAX_ATTEMPTS=15
MUD_TELEMATICO_POLL_INTERVAL_MS=500
```

#### Step 3.10 — GPS tracking trasporti

```env
TRASPORTO_GPS_STUB=false
TRASPORTO_GPS_PROVIDER_URL=https://gps-provider.tuodominio.com/api/v1
TRASPORTO_GPS_API_KEY=<api-key-prod>
TRASPORTO_GPS_FIELD_LAT=latitude
TRASPORTO_GPS_FIELD_LNG=longitude
TRASPORTO_GPS_FIELD_RECORDED_AT=recorded_at
TRASPORTO_GPS_FIELD_SPEED=speed_kmh
TRASPORTO_GPS_TIMEOUT=15
```

#### Step 3.11 — WAF (consigliato)

```env
WAF_MODE=block
WAF_PROVIDER=aws
WAF_SIEM_LOG_GROUP=/aws/waf/rentri-crm-production
```

#### Step 3.12 — 2FA enforcement

```env
TWO_FACTOR_OPTIONAL=false
TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA=true
# Periodo di grazia (opzionale — lasciare 30gg dopo go-live)
TWO_FACTOR_ENFORCE_GRACE_UNTIL=2026-10-15T00:00:00+02:00
```

#### Step 3.13 — HA / Backup

```env
DB_BACKUP_SCHEDULE_ENABLED=true
DB_BACKUP_CRON="0 2 * * *"
DB_BACKUP_RETENTION_DAYS=30
DB_BACKUP_STORAGE_PATH=s3://rentri-crm-backups/production
HA_RPO_MINUTES=60
HA_RTO_MINUTES=240
HA_MIN_APP_INSTANCES=2
```

---

## 4. Comandi Artisan — ordine esatto

Eseguire in sequenza dopo aver salvato `.env`.

```bash
# ── Step 1: Backup istantaneo ────────────────────────────────────────────────
cp .env .env.pre-prod-switch-$(date +%Y%m%d-%H%M)
# Backup DB (adattare al proprio sistema di backup)
mysqldump -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE > backup_pre_switch_$(date +%Y%m%d).sql

# ── Step 2: Pulizia config/cache ─────────────────────────────────────────────
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── Step 3: Migrazioni (force obbligatorio in production) ────────────────────
php artisan migrate --force
# Output atteso: "Nothing to migrate." oppure lista migrazioni eseguite

# ── Step 4: Preflight ────────────────────────────────────────────────────────
php artisan rentri:preflight
# Output atteso: "Preflight completato — nessun errore bloccante."
# Exit code: 0

# ── Step 5: Switch check RENTRI ──────────────────────────────────────────────
php artisan rentri:production-switch-check --dry-run
# Output atteso: "Switch produzione: PRONTO (dry-run)."
# Exit code: 0

# ── Step 6: Switch check Stripe ──────────────────────────────────────────────
php artisan stripe:production-switch-check --dry-run
# Output atteso: "Switch Stripe produzione: PRONTO (dry-run)."
# Exit code: 0

# ── Step 7: Switch check GPS ─────────────────────────────────────────────────
php artisan trasporto:gps-switch-check --dry-run
# Output atteso: "Switch GPS live: PRONTO (dry-run)."
# Exit code: 0

# ── Step 8: Sync codifiche CER da RENTRI ─────────────────────────────────────
php artisan rentri:sync-codifiche
# Output atteso: "Sync codifiche completato — N codici CER aggiornati."

# ── Step 9: Monitor post-deploy ──────────────────────────────────────────────
php artisan rentri:monitor
# Output atteso: "Nessun alert attivo." (exit 0)
# Se alert: verificare sezione §9 (monitoraggio)

# ── Step 10: Riavvio queue workers ───────────────────────────────────────────
php artisan horizon:terminate
# Attende la terminazione graceful dei worker Horizon (o usa queue:restart se non usi Horizon)
php artisan queue:restart
# Supervisord / systemd riavvia Horizon automaticamente dopo horizon:terminate
php artisan horizon:status
# Output atteso: Horizon is running.
```

> **Tutti i comandi devo restituire exit code 0.** Se uno fallisce, **non procedere** al passo successivo.

---

## 5. Switch RENTRI (passi specifici)

### 5.1 Upload certificati in UI

1. Navigare a `/segreteria/impostazioni/rentri`
2. **Step 1 — Dati operatore:**
   - Selezionare ambiente: **Produzione**
   - CF operatore, P.IVA, `num_iscr_sito` (da portale rentri.gov.it)
3. **Step 2 — Certificato interoperabilità mTLS:**
   - Upload file `.p12`/`.pfx`
   - Inserire password keystore
   - Il sistema salva in `storage/app/private/rentri/certificates/`
4. **Step 3 — Certificato firma remota xFIR:**
   - Upload file `.p12`/`.pfx` **distinto** dal precedente
   - Storage: `storage/app/private/rentri/certificates/firma/`

### 5.2 Test connessione sandbox (validazione pre-switch)

```bash
# Con certificati sandbox (opzionale ma raccomandato)
RENTRI_ENV=sandbox RENTRI_API_STUB=false php artisan rentri:preflight
```

Oppure via UI: **Step 3 → Test connessione** → badge «Connesso sandbox» verde.

### 5.3 Vidima FIR test in sandbox

1. Creare trasporto in preparazione
2. Dettaglio trasporto → **Vidima FIR**
3. Verificare protocollo RENTRI, `transazione_id`, QR in UI
4. Storico API: `/segreteria/rentri/transazioni` → tipo `fir`, stato `completata`

### 5.4 Trasmissione registro test in sandbox

1. `/segreteria/rentri` → periodo con movimenti non trasmessi
2. **Trasmetti a RENTRI** → protocollo accettazione nel messaggio success
3. Verificare `locked_at` sui movimenti trasmessi

### 5.5 Attivazione modalità live (UI wizard)

1. Navigare a `/segreteria/impostazioni/rentri?step=4`
2. Verificare checklist unificata: **tutte le voci obbligatorie OK**
3. Cliccare **Attiva modalità live**
4. Verificare activity log:

   ```
   Passaggio modalità live RENTRI (stub disabilitato via UI)
   causer_id: <user_id>
   checklist_summary: { ok: N, total: N }
   ```

5. Badge RENTRI → **Live · api.rentri.gov.it** (verde)

### 5.6 Health check produzione

```bash
# Verifica connessione verso api.rentri.gov.it
php artisan rentri:production-switch-check
# Atteso: produzione attiva: sì
```

Via UI: Impostazioni RENTRI → **Test connessione** → health OK su api.rentri.gov.it.

---

## 6. Switch altri servizi

### 6.1 Stripe (e-commerce pagamenti)

| Variabile | Sandbox | Produzione |
|-----------|---------|------------|
| `ECOMMERCE_PAYMENT_STUB` | `true` | `false` |
| `STRIPE_KEY` | `sk_test_...` | `sk_live_...` |
| `STRIPE_WEBHOOK_SECRET` | `whsec_test_...` | `whsec_live_...` |
| `STRIPE_LIVE_MODE` | `false` | `true` |

**Verifica:**

```bash
php artisan stripe:production-switch-check --dry-run
# Atteso: "Switch Stripe produzione: PRONTO (dry-run)."
```

UI: Hub e-commerce → modalità live; Stripe Dashboard → endpoint webhook live attivo.

**Webhook endpoint da registrare su Stripe Dashboard:**

```
https://crm.tuodominio.it/stripe/webhook
```

**Reconciliation:** `StripeReconciliationReportService` — export CSV mensile da hub e-commerce.

### 6.2 MUD telematico

| Variabile | Stub | Produzione |
|-----------|------|------------|
| `MUD_TELEMATICO_STUB` | `true` | `false` |
| `MUD_TELEMATICO_ENV` | `sandbox` | `production` |

**Verifica:** Creare dichiarazione MUD di test → verificare risposta da `api.rentri.gov.it` (endpoint MUD usa stessa infrastruttura RENTRI).

Portale manuale alternativo: [mudtelematico.it](https://www.mudtelematico.it)

### 6.3 SMTP notifiche email

| Variabile | Stub | Produzione |
|-----------|------|------------|
| `MAIL_MAILER` | `log` | `smtp` |
| `NOTIFICATIONS_LIVE` | `false` | `true` |
| `NOTIFICATIONS_QUEUE` | `false` | `true` |

**Verifica:**

```bash
php artisan tinker
# >>> \Illuminate\Support\Facades\Mail::to('test@example.com')->send(new \App\Mail\TestMail());
# Verificare ricezione email
```

Oppure via UI: Impostazioni → Notifiche → **Invia email di test**.

### 6.4 GPS tracking trasporti

| Variabile | Stub | Produzione |
|-----------|------|------------|
| `TRASPORTO_GPS_STUB` | `true` | `false` |
| `TRASPORTO_GPS_PROVIDER_URL` | `https://gps-provider.example.com/api/v1` | URL reale |
| `TRASPORTO_GPS_API_KEY` | — | chiave produzione |

**Verifica:**

```bash
php artisan trasporto:gps-switch-check --dry-run --probe
# --probe esegue HTTP verso provider live
# Atteso: "Switch GPS live: PRONTO (dry-run)."
```

**Field map** (adattare al provider reale): `flat_default` o `nested_fleet` — vedere `tests/fixtures/gps/position-response.json`.

---

## 7. Checklist di verifica post-switch

Completare entro **2 ore** dal go-live.

### 7.1 RENTRI

- [ ] Badge UI: **Live · api.rentri.gov.it** (verde, non stub)
- [ ] Test connessione in Impostazioni: **Health OK**
- [ ] `php artisan rentri:production-switch-check` → exit 0, produzione attiva: sì
- [ ] Codifiche CER: count > 0 dopo `rentri:sync-codifiche`
- [ ] Vidima FIR produzione: protocollo MASE presente in `/segreteria/rentri/transazioni`
- [ ] Trasmissione registro produzione: protocollo accettazione MASE
- [ ] `php artisan rentri:monitor` → nessun dead-letter, nessun alert critico
- [ ] SLA dashboard: P95 latency < `RENTRI_SLA_P95_LATENCY_SECONDS` (120s)

### 7.2 Stripe

- [ ] Stripe Dashboard: modalità live attiva
- [ ] Webhook endpoint live registrato e attivo
- [ ] Pagamento test (importo €0.01) completato senza errori
- [ ] `stripe:production-switch-check` → exit 0

### 7.3 SMTP

- [ ] Email di test ricevuta correttamente
- [ ] `NOTIFICATIONS_LIVE=true` e `NOTIFICATIONS_QUEUE=true` in `.env`
- [ ] Nessun bounce in coda notifiche

### 7.4 GPS

- [ ] Probe HTTP al provider live: risposta 200 con payload posizione valido
- [ ] Almeno 1 trasporto con posizione GPS aggiornata
- [ ] `trasporto:gps-switch-check --probe` → exit 0

### 7.5 MUD telematico

- [ ] Dichiarazione MUD test inviata senza errori API
- [ ] `MUD_TELEMATICO_STUB=false` e `MUD_TELEMATICO_ENV=production` in `.env`

### 7.6 Infrastruttura generale

- [ ] `php artisan rentri:preflight` → exit 0 (0 fail)
- [ ] Queue workers attivi (Horizon / supervisord)
- [ ] Redis operativo (session, cache, queue)
- [ ] DB backup schedulato attivo (`DB_BACKUP_SCHEDULE_ENABLED=true`)
- [ ] WAF: `WAF_MODE=block` attivo
- [ ] 2FA: enforcement admin/segreteria attivo
- [ ] Activity log: voci switch registrate correttamente

---

## 8. Procedura di rollback

Eseguire in ordine in caso di errori critici, dead-letter non risolvibili o anomalie MASE.

> ⚠️ **Mai** attivare stub in production per aggirare un'indisponibilità MASE — usare coda differita e retry manuale quando il servizio riprende.

### 8.1 Rollback RENTRI (immediato — < 2 minuti)

```bash
# Step 1: Rollback .env
cp .env.pre-prod-switch-<YYYYMMDD-HHMM> .env

# Step 2: Clear config
php artisan config:clear && php artisan config:cache

# Step 3: Verifica
php artisan rentri:preflight
php artisan rentri:monitor
```

Oppure via `.env` manuale:

```env
RENTRI_API_STUB=true
RENTRI_FIRMA_STUB=true
RENTRI_ENV=sandbox
```

Poi:

```bash
php artisan config:clear
php artisan rentri:preflight
```

### 8.2 Rollback via UI (< 1 minuto)

1. `/segreteria/impostazioni/rentri?step=4`
2. Cliccare **Rientra in stub**
3. Activity log: `Rientro modalità stub RENTRI (override UI disattivato)`

### 8.3 Rollback completo (tutti i servizi)

| Step | Azione | Tempo stimato |
|------|--------|--------------|
| 1 | `RENTRI_API_STUB=true` + `RENTRI_FIRMA_STUB=true` | Immediato |
| 2 | UI: «Rientra in stub» (step 4) | Immediato |
| 3 | `ECOMMERCE_PAYMENT_STUB=true` | Immediato |
| 4 | `TRASPORTO_GPS_STUB=true` | Immediato |
| 5 | `MUD_TELEMATICO_STUB=true` | Immediato |
| 6 | `php artisan config:clear && config:cache` | 1–2 min |
| 7 | `php artisan queue:restart` | 1–2 min |
| 8 | `php artisan rentri:preflight && rentri:monitor` | Verifica |
| 9 | Post-mortem entro 24h | Documentare |

### 8.4 Effetti del rollback

- Movimenti già `locked_at` (trasmessi) **non** vengono sbloccati automaticamente — richiedono intervento manuale su DB se necessario
- FIR vidimati in produzione restano validi — il rollback non annulla vidime già confermate da MASE
- Notifiche email: `NOTIFICATIONS_LIVE=false` sospende l'invio; le notifiche in coda vengono esaurite

---

## 9. Monitoraggio post-go-live

### 9.1 Monitoraggio 48h post-switch

```bash
# Ogni 15 minuti (cron produzione — già configurato in Kernel)
php artisan rentri:monitor

# Spot check giornaliero
php artisan rentri:production-switch-check

# SLA check (se soglie configurate)
php artisan rentri:sla-check
```

### 9.2 Segnali da monitorare

| Segnale | Tool / Path | Soglia allerta |
|---------|-------------|----------------|
| Dead-letter RENTRI | `/segreteria/rentri/transazioni` | > 0 |
| SLA P95 latency | Hub RENTRI, `rentri:sla-check` | > 120s |
| Error rate 5xx MASE | Log Laravel (`storage/logs/rentri-*.log`) | > baseline |
| Cert scadenza mTLS | Impostazioni RENTRI → badge scadenza | < 30 giorni |
| Cert scadenza firma xFIR | Impostazioni RENTRI → badge scadenza | < 30 giorni |
| Queue depth | Horizon dashboard / `rentri:monitor` | Backlog crescente |
| Notifiche email bounce | Log `business` | > 5% rate |
| Stripe dispute | Hub e-commerce | Qualsiasi disputa nuova |

### 9.3 Log channels da controllare

```bash
# Log specifico RENTRI (JSON structured)
tail -f storage/logs/rentri-$(date +%Y-%m-%d).log | python3 -m json.tool

# Log sicurezza (eventi cripto, preflight fail)
tail -f storage/logs/security-$(date +%Y-%m-%d).log

# Log integrazioni (chiamate API MASE, Stripe, GPS)
tail -f storage/logs/integration-$(date +%Y-%m-%d).log

# Log business KPI
tail -f storage/logs/business-$(date +%Y-%m-%d).log
```

### 9.4 Alert SLA automatici

Il sistema invia alert automatici via `RentriSlaAlertService` quando:
- Dead-letter rate > `RENTRI_SLA_DEAD_LETTER_RATE_PERCENT` (default 5%)
- P95 latency > `RENTRI_SLA_P95_LATENCY_SECONDS` (default 120s)
- Retry medio > `RENTRI_SLA_MAX_AVG_RETRY_COUNT` (default 1)

Alert visible in:
- Activity log (`/segreteria/audit`)
- Hub RENTRI SLA dashboard
- Email a `BONIFICA_NOTIFY_EMAIL` se configurato

### 9.5 Rotazione certificati (futura)

1. Ottenere nuovo PKCS#12 da CA dominio RENTRI prima della scadenza (> 30 giorni)
2. Impostazioni RENTRI → ricaricare certificato (interoperabilità e/o firma)
3. **Test connessione** immediato → badge verde
4. `php artisan rentri:preflight` — verificare scadenze
5. Revocare certificato precedente su portale MASE quando richiesto

---

## 10. Sign-off

| Ruolo | Nome | Data | Env switch completato | Rollback testato | Note |
|-------|------|------|----------------------|------------------|------|
| Tech Lead | | | ☐ | ☐ | |
| Segreteria RENTRI | | | ☐ | ☐ | |
| Ops / DevOps | | | ☐ | ☐ | |
| Titolare / Responsabile | | | ☐ | — | Approvazione finale |

---

## Riferimenti

| Documento | Contenuto |
|-----------|-----------|
| [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) | Storico sprint 31–106: variabili, smoke test, palestra operativa |
| [RENTRI-PRODUCTION-SWITCH-RUNBOOK.md](RENTRI-PRODUCTION-SWITCH-RUNBOOK.md) | Runbook Sprint 106 (superato da questo documento) |
| [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md) | Checklist validazione sandbox MASE |
| [STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md](STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md) | Riconciliazione pagamenti Stripe |
| [GPS-PROVIDER-PRODUZIONE-RUNBOOK.md](GPS-PROVIDER-PRODUZIONE-RUNBOOK.md) | Configurazione provider GPS live |
| [WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md) | Rollout WAF block mode |
| [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md) | Procedura post-deploy generica |
| [MONITORING-CICLO-3.md](MONITORING-CICLO-3.md) | KPI monitoring ciclo 3 |
| `RentriProductionSwitchService` | Checklist unificata (sorgente dati `rentri:production-switch-check`) |
| `RentriProdReadinessService` | Verifica readiness certificati e onboarding |

---

*Runbook unificato Sprint 121 · Ciclo 10 · 8 giugno 2026.*  
*⚠️ Non eseguire switch produzione senza certificati MASE reali, UAT approvato e sign-off completato.*
