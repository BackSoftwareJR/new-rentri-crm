# Deploy piattaforma demo RENTRI

Guida per un'**istanza CRM demo** isolata dalla produzione. Complementa [CICLO-3-PIANO-COMPLETO.md](CICLO-3-PIANO-COMPLETO.md) e [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) (produzione).

---

## 1. Pattern consigliato

| Istanza | URL | Env file | DB |
|---------|-----|----------|-----|
| **Produzione** | `crm.example.it` | `.env` | `rentri_crm_prod` |
| **Demo** | `demo.crm.example.it` | `.env.demo` | `rentri_crm_demo` |

Non usare toggle sessione sulla stessa istanza prod: `APP_DEMO_MODE` è **per deploy**, non per utente.

---

## 2. Esempio `.env.demo`

```env
APP_NAME="RENTRI CRM Demo"
APP_ENV=demo
APP_DEBUG=true
APP_URL=https://demo.crm.example.it

# --- Demo platform ---
APP_DEMO_MODE=true
RENTRI_DEMO_FORCE_SANDBOX=true
RENTRI_DEMO_NO_HTTP=false

# DB dedicato (consigliato)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rentri_crm_demo
DB_USERNAME=postgres
DB_PASSWORD=secret

# RENTRI — integrazione live sandbox MASE (palestra operativa)
RENTRI_DEMO_LIVE_SANDBOX=true
RENTRI_BASE_URL_SANDBOX=https://demoapi.rentri.gov.it
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_API_STUB=false
RENTRI_FIRMA_STUB=true
RENTRI_AUTH_MODE=mtls
RENTRI_VERIFY_SSL=true

# Demo offline (senza rete verso MASE)
# RENTRI_DEMO_NO_HTTP=true
# RENTRI_API_STUB=false

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

---

## 3. Bootstrap istanza demo

```bash
cd new-rentri-crm
cp .env.demo.example .env   # oppure .env.demo locale (non committato)
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed
npm ci && npm run build
php artisan rentri:demo-seed
```

### Comandi utili

| Comando | Uso |
|---------|-----|
| `php artisan rentri:demo-seed` | Crea fixture walkthrough (idempotente) |
| `php artisan rentri:demo-seed --fresh` | Reset + rigenera scenario |
| `php artisan rentri:demo-reset` | Elimina solo dati `is_demo=true` |
| `php artisan rentri:preflight --demo` | Health check post-deploy istanza demo |
| `php artisan rentri:preflight --demo --require-seed` | Come sopra + verifica fixture seed |

---

## 4. Walkthrough UI

1. Login segreteria → **Dashboard** → card «Prova flusso RENTRI»
2. Step guidati: impostazioni → blocchi FIR → trasporto → vidima/firma → registro
3. Banner giallo persistente: «Modalità DEMO»

**Certificato sandbox MASE (obbligatorio per integrazione reale):** upload in `/segreteria/impostazioni/rentri` — CER, blocchi FIR e vidima da `demoapi.rentri.gov.it`.

Con `RENTRI_DEMO_LIVE_SANDBOX=true` (default) la palestra operativa **non** usa fixture JSON locali.

Con `RENTRI_DEMO_NO_HTTP=true` tutte le chiamate restano stub locali — utile solo per demo offline/CI senza rete.

---

## 5. Sicurezza

- Demo **non** può scrivere record `is_demo=false` (`DemoIsolationException`)
- `RentriApiClient` in demo **blocca** host `api.rentri.gov.it`
- Non condividere DB prod/demo sulla stessa istanza senza isolamento `is_demo`

### 5.1 Toggle sessione «Palestra operativa» (Ciclo 4)

Su **istanza condivisa** (staging/local) è possibile attivare lo scope demo **senza redeploy**:

| Controllo | Comportamento |
|-----------|---------------|
| `ALLOW_SESSION_DEMO=true` | Abilita toggle sidebar per admin/segreteria |
| `APP_ENV=production` senza flag | Toggle bloccato (🔒) |
| Modale conferma | Obbligatoria prima di activate |
| Disattivazione | `session()->forget('demo_mode_active')` → scope prod |

Deploy dedicato (`APP_DEMO_MODE=true`) resta il pattern consigliato per demo pubblica Internet.

Guida utente: [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) · Piano: [CICLO-4-PIANO.md](CICLO-4-PIANO.md).

---

## 6. CI / staging demo (GitHub Actions)

Workflow: [`.github/workflows/demo-staging.yml`](../.github/workflows/demo-staging.yml)

Trigger: push/PR su branch `demo`, tag `feature/demo-*`, o `workflow_dispatch`.

### Bootstrap pipeline

```bash
cp .env.demo.example .env          # template committato (vedi anche §2)
php artisan key:generate
composer install
npm ci && npm run build
php artisan migrate --force
php artisan db:seed
php artisan rentri:demo-seed --fresh
php artisan rentri:preflight --demo --require-seed
php artisan test
```

Il job usa PostgreSQL 16 come service container (`DB_*` in `.env.demo.example`).

### Health check demo

| Comando | Quando |
|---------|--------|
| `php artisan rentri:preflight --demo` | Post-deploy: verifica `APP_DEMO_MODE`, sandbox forzata, DB, manifest Vite, `/up` |
| `php artisan rentri:preflight --demo --require-seed` | Dopo `rentri:demo-seed`: fallisce se fixture walkthrough assenti |

Check demo-specifici (vs preflight produzione):

- `demo_mode` — `APP_DEMO_MODE=true` obbligatorio
- `demo_env` — `APP_ENV=production` bloccato
- `demo_sandbox` — `RENTRI_DEMO_FORCE_SANDBOX=true` obbligatorio
- `demo_seed` — warn o fail con `--require-seed`
- `framework_health` — GET interno `/up`

Smoke UI manuale post-deploy: login segreteria → banner «Modalità DEMO» + card «Prova flusso RENTRI».

---

*Deploy demo — Ciclo 3 Sprint 44.*
