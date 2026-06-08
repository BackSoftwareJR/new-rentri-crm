# RENTRI CRM — Gestionale per autodemolitori

CRM Laravel per autodemolitori italiani con integrazione **RENTRI/MASE** (registro rifiuti, FIR digitali, vidima, xFIR), magazzino CER, VFU, bonifica/smontaggio operatore, trasporti, MUD telematico, fatturazione elettronica (SDI), e-commerce ricambi e shop pubblico.

**Stato:** piattaforma enterprise multi-sito — cicli 1–10 chiusi (giugno 2026). Vedi [agent_sprint_plan.md](agent_sprint_plan.md) per il backlog operativo.

---

## Tech stack

| Layer | Tecnologia |
|-------|------------|
| Backend | PHP 8.2+, **Laravel 12** |
| UI | **Livewire 4**, Blade, **Tailwind CSS** |
| Database | **MySQL** (produzione), SQLite (test locali) |
| Cache / Queue | Redis, **Laravel Horizon** |
| Auth | Spatie Permission (ruoli), 2FA TOTP |
| Integrazioni | RENTRI mTLS, Stripe, SDI/FatturaPA, GPS, Web Push |

---

## Quick start

### Requisiti

- PHP 8.2+, Composer 2.x, Node.js 18+
- MySQL 8+ (produzione) oppure SQLite (sviluppo/test)
- Redis consigliato per code e Horizon

### Installazione

```bash
git clone <repo-url> new-rentri-crm
cd new-rentri-crm

composer install
npm install
cp .env.example .env
php artisan key:generate

# SQLite (sviluppo rapido)
touch database/database.sqlite
# In .env: DB_CONNECTION=sqlite e commentare DB_HOST/PORT/DATABASE

php artisan migrate --seed
npm run build
php artisan serve
```

**Login demo:** `admin@example.com` / `password` (ruolo `admin`).

Shortcut setup: `composer run setup` (dopo aver configurato `.env`).

### Avvio code (opzionale)

```bash
# Con Redis
php artisan horizon

# Senza Horizon (sviluppo)
php artisan queue:work
```

---

## Architettura

```
app/
├── Domain/          # Logica di business per modulo (Vfu, Magazzino, Rentri, Fatturazione, …)
├── Http/Livewire/   # Componenti UI (Segreteria, Operatore, Admin, Shop, Settings)
├── Jobs/            # Job asincroni (RENTRI retry, notifiche, export audit, SDI)
├── Services/Rentri/ # Client API MASE, certificati mTLS, registry, FIR/xFIR
├── Models/          # Eloquent + trait BelongsToSito (multi-impianto)
└── Policies/        # Autorizzazioni per risorsa
```

### Pattern principali

- **Domain services** — orchestrazione business (es. `RegistroService`, `VfuAccettazioneService`, `SdiTransmissionService`)
- **Livewire** — interfacce segreteria (`/segreteria/*`), operatore mobile/PWA (`/operatore/*`), admin (`/admin/*`)
- **RENTRI** — `RentriApiClient` con mTLS, job su coda `rentri`, onboarding wizard in Settings Hub
- **Multi-sito** — middleware `sito.scope` + `SitoContext` per isolamento dati per impianto
- **Settings Hub** — `/segreteria/impostazioni` centralizza azienda, Stripe, email, integrazioni, sistema

### Code Horizon

| Coda | Job tipici | Timeout | Tries |
|------|------------|---------|-------|
| `default` | SDI, job generici | 90s | 3 |
| `rentri` | Retry transazioni, sync iniziale CER/FIR | 300s | 5 |
| `notifications` | Email SMTP async | 60s | 3 |
| `exports` | Export audit schedulato | 600s | 2 |

Dashboard: `/horizon` (solo ruolo `admin`).

---

## Funzionalità principali

| Modulo | Route | Descrizione |
|--------|-------|-------------|
| Dashboard KPI | `/segreteria` | Analytics cross-modulo, cache Redis |
| Anagrafiche | `/segreteria/anagrafiche` | Clienti/fornitori, verifica RENTRI |
| VFU & accettazione | `/segreteria/vfu` | Wizard accettazione, import CSV, timeline |
| Bonifica operatore | `/operatore/bonifica` | Wizard bonifica veicolo, PWA offline |
| Smontaggio & ricambi | `/operatore/smontaggio` | Sessioni smontaggio, foto ricambi, vetrina |
| Magazzino & registro | `/segreteria/magazzino` | Giacenze CER, movimenti, trasmissione registro MASE |
| Trasporti & FIR | `/segreteria/trasporti` | Vidima FIR, tracking GPS, firma xFIR |
| RENTRI | `/segreteria/rentri` | Onboarding, health check, transazioni async |
| MUD | `/segreteria/mud` | Dichiarazioni, invio telematico |
| Fatturazione | `/segreteria/fatture` | FatturaPA XML, invio SDI, PDF |
| E-commerce B2B | `/segreteria/ecommerce` | Catalogo ricambi, ordini, Stripe |
| Shop pubblico | `/shop` | Vetrina guest (`SHOP_ENABLED=true`) |
| Admin | `/admin/*` | Utenti, siti, audit, log, pen-test prep |
| Impostazioni | `/segreteria/impostazioni` | Settings Hub unificato |

---

## RENTRI go-live

Per lo switch sandbox → produzione MASE seguire il runbook unificato:

**[docs/PRODUCTION_SWITCH.md](docs/PRODUCTION_SWITCH.md)**

Checklist rapida:

```bash
php artisan rentri:preflight
php artisan rentri:production-switch-check --dry-run
php artisan migrate --force
php artisan horizon:terminate && php artisan queue:restart
```

Documentazione storica sprint: [docs/GO-LIVE-RENTRI.md](docs/GO-LIVE-RENTRI.md), [docs/GO-LIVE-CERT-PRODUZIONE.md](docs/GO-LIVE-CERT-PRODUZIONE.md).

---

## Test

```bash
php artisan test                    # suite completa
php artisan test --filter=SettingsHub
php artisan test --filter=SecurityHeaders
npm run test:e2e                    # smoke Playwright (palestra operativa)
```

I test usano SQLite in-memory (`phpunit.xml`). Per test integrazione RENTRI con cert reale: `RENTRI_INTEGRATION_TEST=true` (mai in CI default).

---

## Deploy

| Documento | Contenuto |
|-----------|-----------|
| [docs/MYSQL_PRODUCTION_SETUP.md](docs/MYSQL_PRODUCTION_SETUP.md) | Setup MySQL, utenti, backup, Horizon |
| [docs/PENDING_MIGRATIONS.md](docs/PENDING_MIGRATIONS.md) | Migrazioni da applicare su staging/prod |
| [docs/PRODUCTION_SWITCH.md](docs/PRODUCTION_SWITCH.md) | Switch produzione RENTRI e servizi |
| [docs/PRE-DEPLOY-CHECKLIST.md](docs/PRE-DEPLOY-CHECKLIST.md) | Checklist go/no-go |
| [docs/HORIZON-SCALING-RUNBOOK.md](docs/HORIZON-SCALING-RUNBOOK.md) | Scaling worker e SMTP |

Sequenza deploy tipica:

```bash
npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate
php artisan queue:restart
php artisan rentri:preflight
```

---

## Variabili ambiente (riepilogo)

### Applicazione

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `APP_ENV` | `local` | `production` / `staging` / `local` |
| `APP_DEBUG` | `true` | **false** in produzione |
| `APP_DEMO_MODE` | `false` | Isolamento dati demo/palestra |
| `DB_CONNECTION` | `mysql` | `sqlite` per dev/test |
| `QUEUE_CONNECTION` | `database` | `redis` + Horizon in produzione |
| `SHOP_ENABLED` | `false` | Shop pubblico `/shop` |

### RENTRI / MASE

| Variabile | Descrizione |
|-----------|-------------|
| `RENTRI_API_STUB` | `false` per API ministeriale live |
| `RENTRI_FIRMA_STUB` | `false` per firma COSE xFIR reale |
| `RENTRI_BASE_URL_SANDBOX` | `https://demoapi.rentri.gov.it` |
| `RENTRI_BASE_URL_PRODUCTION` | `https://api.rentri.gov.it` |
| `RENTRI_INTEGRATION_TEST` | Test manuali con cert reale |

### Integrazioni

| Variabile | Descrizione |
|-----------|-------------|
| `MUD_TELEMATICO_STUB` | Stub invio MUD |
| `TRASPORTO_GPS_STUB` | Stub tracking GPS |
| `ECOMMERCE_PAYMENT_STUB` | Stub pagamenti Stripe |
| `STRIPE_LIVE_MODE` | Modalità Stripe live |
| `SDI_STUB` | Stub invio fatture SDI |
| `NOTIFICATIONS_LIVE` | SMTP reale vs log stub |
| `TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA` | 2FA obbligatorio |

Vedi [.env.example](.env.example) per l'elenco completo commentato.

---

## Ruoli

| Ruolo | Accesso |
|-------|---------|
| `admin` | Tutto + Horizon + `/admin/*` |
| `editor` | Come admin per middleware route |
| `segreteria` | `/segreteria/*` + Settings Hub (lettura; salvataggio solo admin) |
| `operatore` | `/operatore/*` (PWA bonifica, smontaggio, vetrina) |

Seeder: `RolePermissionSeeder` crea ruoli e utenti demo.

---

## Contributing

1. **Branch** — feature da `main`/`develop`: `feature/<modulo>-<descrizione>`
2. **Stile** — seguire convenzioni esistenti; `vendor/bin/pint` prima del commit
3. **Test** — aggiungere test Feature per nuova logica business; `php artisan test` deve passare
4. **Livewire** — componenti sotto `app/Http/Livewire/<Area>/`, view in `resources/views/livewire/`
5. **Domain** — logica in `app/Domain/<Modulo>/`, non nei componenti Livewire
6. **Sicurezza** — upload via `UploadValidation`, nessun `whereRaw` con input utente, CSRF su tutte le route web (eccetto webhook Stripe firmati)
7. **Migrazioni** — nominare `YYYY_MM_DD_HHMMSS_descrizione.php`; documentare in `docs/PENDING_MIGRATIONS.md` se non ancora in prod
8. **PR** — descrizione con test plan; link a doc operativa se tocca go-live

---

## Pacchetti principali

- [Livewire](https://livewire.laravel.com) — UI reattiva
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission) — ruoli
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) — audit
- [laravel/horizon](https://laravel.com/docs/horizon) — queue dashboard
- [stripe/stripe-php](https://github.com/stripe/stripe-php) — pagamenti
- [maatwebsite/excel](https://github.com/SpartnerNL/Laravel-Excel) — export

---

## Storico sprint (riferimento)

Documentazione dettagliata per ciclo MVP → certificazione produzione (sprint 1–120).

### Ciclo 6 — Completamento verticale moduli (sprint 61–75) ✅ CHIUSO

- [docs/CICLO-6-PIANO-MODULI-COMPLETI.md](docs/CICLO-6-PIANO-MODULI-COMPLETI.md)
- [docs/GO-LIVE-CICLO-6.md](docs/GO-LIVE-CICLO-6.md)
- [docs/PERFORMANCE-MONITORING.md](docs/PERFORMANCE-MONITORING.md) — KPI cache Redis, load test
- Load autenticato: `k6 run scripts/k6-authenticated.js`

### Ciclo 7 — Enterprise RENTRI/FIR (sprint 76–90) ✅ CHIUSO

- [docs/CICLO-7-PIANO.md](docs/CICLO-7-PIANO.md)
- [docs/GO-LIVE-ENTERPRISE.md](docs/GO-LIVE-ENTERPRISE.md)
- Smoke: `php artisan test --filter=Sprint90`

### Ciclo 8 — Validazione operativa reale (sprint 91–100) ✅ CHIUSO

- [docs/CICLO-8-PIANO.md](docs/CICLO-8-PIANO.md)
- [docs/GO-LIVE-OPERATIVO.md](docs/GO-LIVE-OPERATIVO.md)
- Smoke: `php artisan test --filter=Sprint100`

### Ciclo 9 — Produzione e gap infra (sprint 101–110) ✅ CHIUSO

- [docs/CICLO-9-PIANO.md](docs/CICLO-9-PIANO.md)
- [docs/GO-LIVE-PRODUZIONE.md](docs/GO-LIVE-PRODUZIONE.md)
- Smoke: `php artisan test --filter=Sprint110`

### Ciclo 10 — RENTRI cert produzione (sprint 111–120) ✅ CHIUSO

- [docs/CICLO-10-PIANO.md](docs/CICLO-10-PIANO.md)
- [docs/GO-LIVE-CERT-PRODUZIONE.md](docs/GO-LIVE-CERT-PRODUZIONE.md)
- Smoke: `php artisan test --filter=Sprint120`

### Altri cicli

- [docs/GO-LIVE-RENTRI.md](docs/GO-LIVE-RENTRI.md) — Ciclo 2 RENTRI produzione
- [docs/GO-LIVE-CICLO-3.md](docs/GO-LIVE-CICLO-3.md) — Demo + gap RENTRI
- [docs/GO-LIVE-360.md](docs/GO-LIVE-360.md) — Perfezionamento 360°

---

## License

Proprietary — internal use.
