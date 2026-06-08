# Pre-deploy checklist — RENTRI CRM

Checklist operativa prima di un deploy in staging/produzione. **Nessun deploy automatico** — solo verifiche manuali e test automatizzati.

---

## 1. Prerequisiti server

| Componente | Minimo | Note |
|------------|--------|------|
| PHP | 8.2+ | estensioni: pdo, mbstring, openssl, json, fileinfo |
| Composer | 2.x | `composer install --no-dev --optimize-autoloader` in prod |
| Node.js | 20+ | solo per build asset (`npm ci && npm run build`) |
| Database | PostgreSQL 15+ | SQLite accettabile solo per smoke locale |
| Redis | consigliato | Horizon/queue in prod; locale può usare `QUEUE_CONNECTION=database` |

---

## 2. Variabili d'ambiente

Copiare `.env.example` → `.env` e impostare:

### Applicazione

```env
APP_NAME="RENTRI CRM"
APP_ENV=production          # staging | production
APP_KEY=                    # php artisan key:generate
APP_DEBUG=false             # true solo in staging controllato
APP_URL=https://crm.example.it
```

### Database (produzione)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rentri_crm
DB_USERNAME=...
DB_PASSWORD=...
```

### Sessione, cache, code

```env
SESSION_DRIVER=database     # o redis in prod ad alto traffico
QUEUE_CONNECTION=redis      # database accettabile per MVP
CACHE_STORE=redis         # database accettabile per MVP
```

### RENTRI

```env
RENTRI_ENV=sandbox          # sandbox finché non certificato prod
RENTRI_API_STUB=false       # false per chiamate HTTP reali
RENTRI_BASE_URL=            # opzionale; default da config/services.php
RENTRI_CLIENT_ID=
RENTRI_CLIENT_SECRET=
RENTRI_CERT_PATH=           # path assoluto certificato .p12 (prod)
RENTRI_KEY_PATH=            # se separato dalla chiave nel p12
```

> **Stub locale:** `RENTRI_API_STUB=true` evita HTTP verso RENTRI. Certificato operatore configurabile da **Segreteria → Impostazioni RENTRI** (wizard onboarding).

### Audit

```env
ACTIVITYLOG_ENABLED=true
```

### Horizon (opzionale)

```env
HORIZON_PATH=horizon
# Redis richiesto se QUEUE_CONNECTION=redis
```

---

## 3. Procedura installazione

```bash
cd new-rentri-crm
composer install --no-dev --optimize-autoloader   # prod
cp .env.example .env
php artisan key:generate
# configurare .env (§2)
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Utenti demo seeder** (cambiare password in prod):

| Email | Ruolo | Password default |
|-------|-------|------------------|
| admin@example.com | admin | password |
| segreteria@example.com | segreteria | password |
| operatore@example.com | operatore | password |

---

## 4. Certificato RENTRI

1. Accedere come **segreteria** o **admin**.
2. Aprire `/segreteria/impostazioni/rentri`.
3. Completare wizard: dati operatore → upload certificato (stub accetta path sandbox) → health check.
4. Verificare `rentri_settings` in DB (`num_iscr_sito`, certificato configurato).
5. In prod: montare volume sicuro per `.p12` e impostare permessi file restrittivi.

---

## 5. Horizon / queue (stub MVP)

| Ambiente | Setup |
|----------|--------|
| Locale | `QUEUE_CONNECTION=database`; Horizon opzionale (`php artisan horizon`) |
| Staging/prod | Redis + `php artisan horizon` come servizio systemd/supervisor |

Verifica rapida:

```bash
php artisan queue:work --once    # smoke job singolo
# oppure
php artisan horizon:status       # se Horizon attivo
```

Dashboard Horizon: `/horizon` — solo ruolo **admin**.

---

## 6. Smoke test manuale (HTTP)

Con `php artisan serve` (o URL deploy):

### Login e home per ruolo

| Utente | POST `/login` → redirect atteso |
|--------|----------------------------------|
| admin@example.com | `/admin/audit` |
| segreteria@example.com | `/segreteria` |
| operatore@example.com | `/operatore` |

### Route critiche (status atteso)

| Route | Ruolo | HTTP |
|-------|-------|------|
| `/login` | guest | 200 |
| `/segreteria` | segreteria | 200 |
| `/segreteria/vfu` | segreteria | 200 |
| `/segreteria/rentri` | segreteria | 200 |
| `/segreteria/magazzino` | segreteria | 200 |
| `/operatore` | operatore | 200 |
| `/operatore/bonifica` | operatore | 200 |
| `/operatore/vetrina` | operatore | 200 |
| `/admin/audit` | admin | 200 |
| `/segreteria` | operatore | **403** |
| `/operatore` | segreteria | **403** |
| `/admin/audit` | segreteria | **403** |

### Comandi curl (esempio)

```bash
# Health framework
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/up
# Atteso: 200

# Login page
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/login
# Atteso: 200
```

> Per route autenticate usare session cookie dopo login browser, oppure i test automatizzati (§7).

---

## 7. Preflight automatizzato

```bash
php artisan rentri:preflight
```

Verifica locale (no HTTP RENTRI): `APP_KEY`, `APP_DEBUG`, DB, manifest Vite, certificato RENTRI. Dettaglio sequenza deploy: [DEPLOY-PRODUCTION.md](DEPLOY-PRODUCTION.md).

Template env produzione: `.env.production.example`.

---

## 8. Migrazione dati legacy (post-migrate)

Sequenza consigliata — eseguire dry-run prima di ogni entità in staging:

```bash
php artisan rentri:import-legacy codici_cer --dry-run && php artisan rentri:import-legacy codici_cer
php artisan rentri:import-legacy anagrafiche --dry-run && php artisan rentri:import-legacy anagrafiche
php artisan rentri:import-legacy vfu --dry-run && php artisan rentri:import-legacy vfu
php artisan rentri:import-legacy movimenti --dry-run && php artisan rentri:import-legacy movimenti
php artisan rentri:import-legacy ricambi --dry-run && php artisan rentri:import-legacy ricambi
php artisan rentri:import-legacy --report
```

Dettaglio mapping e rollback manuale: [MIGRAZIONE-LEGACY.md](MIGRAZIONE-LEGACY.md).  
Checklist post-import e riconciliazione: [GO-LIVE.md](GO-LIVE.md).

### Verifica audit import

Dopo ogni import reale (non dry-run), controllare il registro audit:

1. Admin → **Audit & activity log** → filtro modulo **Migrazione legacy**.
2. Una voce per entità con `imported` / `skipped` / `dry_run: false`.
3. Dry-run in staging deve comunque comparire con `(dry-run)` in descrizione.

```bash
php artisan tinker --execute="
\Spatie\Activitylog\Models\Activity::where('log_name','legacy')->count();
"
```

---

## 9. Smoke test automatizzati

```bash
php artisan test --filter=RouteSmokeTest
php artisan test
npm run build
php artisan rentri:preflight
php artisan rentri:import-legacy --report
```

File: `tests/Feature/Smoke/RouteSmokeTest.php` — assert RBAC su ~12 route senza browser.

---

## 10. Checklist go / no-go

- [ ] `APP_DEBUG=false` in produzione
- [ ] `APP_KEY` impostata
- [ ] Migrazioni eseguite (`migrate --force`)
- [ ] Asset Vite compilati (`public/build/manifest.json` presente)
- [ ] Password utenti demo **rotated** o seeder disabilitato
- [ ] RENTRI: stub disabilitato se ambiente reale; certificato valido
- [ ] Import legacy verificato (`rentri:import-legacy --report`) se applicabile
- [ ] Audit import legacy verificato (modulo `legacy` in Audit admin) se applicabile
- [ ] `php artisan rentri:preflight` verde
- [ ] `php artisan test` verde
- [ ] Backup DB configurato (fuori scope app)
- [ ] Log rotation / `LOG_LEVEL=warning` in prod

---

## 11. Fuori scope (Sprint 18)

- Pipeline CI/CD
- Docker produzione
- Rollback import automatico
- Load test / pen test

---

*Aggiornato — Sprint 27, 4 giugno 2026.*
