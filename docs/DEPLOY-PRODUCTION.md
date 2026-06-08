# Deploy produzione — RENTRI CRM

Guida operativa per deploy in **staging/produzione**. Complementa [PRE-DEPLOY-CHECKLIST.md](PRE-DEPLOY-CHECKLIST.md) e [MIGRAZIONE-LEGACY.md](MIGRAZIONE-LEGACY.md).

---

## 1. Template ambiente

Copiare il template produzione:

```bash
cp .env.production.example .env
php artisan key:generate
# Completare DB_*, REDIS_*, RENTRI_*, APP_URL
```

Valori critici:

| Variabile | Produzione | Note |
|-----------|------------|------|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | obbligatorio |
| `APP_KEY` | generata | `php artisan key:generate` |
| `DB_CONNECTION` | `pgsql` | no SQLite |
| `QUEUE_CONNECTION` | `redis` | Horizon |
| `CACHE_STORE` | `redis` | |
| `SESSION_ENCRYPT` | `true` | |
| `RENTRI_API_STUB` | `false` | chiamate reali |
| `RENTRI_ENV` | `production` | dopo certificazione |
| `LOG_LEVEL` | `warning` | |

---

## 2. Sequenza deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force   # solo prima install
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan rentri:preflight
```

Se migrazione legacy necessaria (post go-live dati):

```bash
php artisan rentri:import-legacy anagrafiche --dry-run
php artisan rentri:import-legacy anagrafiche
php artisan rentri:import-legacy codici_cer
php artisan rentri:preflight
```

---

## 3. Comando preflight

Verifica **locale** (nessuna chiamata HTTP RENTRI):

```bash
php artisan rentri:preflight
```

| Check | Criterio |
|-------|----------|
| APP_KEY | valorizzata |
| APP_DEBUG | `false` se `APP_ENV=production` |
| Database | connessione PDO attiva |
| Vite manifest | `public/build/manifest.json` esistente |
| Certificato RENTRI | `rentri_settings.cert_path_encrypted` oppure stub esplicito |
| RENTRI stub | warning se `production` + `RENTRI_API_STUB=true` |

Exit code **0** = nessun fail; **1** = almeno un check fallito.

---

## 4. Post-deploy manuale

- [ ] Rotazione password utenti demo (`admin@`, `segreteria@`, `operatore@`)
- [ ] Wizard RENTRI completato (`/segreteria/impostazioni/rentri`)
- [ ] Horizon attivo (`php artisan horizon` via supervisor)
- [ ] Backup DB schedulato
- [ ] `php artisan test --filter=RouteSmokeTest` su staging
- [ ] Monitoraggio log (`storage/logs/`)

---

## 5. Rollback

1. Ripristino snapshot DB
2. Checkout release precedente + `composer install --no-dev`
3. `php artisan migrate:rollback` (solo se migration reversibili)
4. `php artisan config:clear && php artisan cache:clear`

Rollback automatico: **fuori scope** MVP.

---

## 6. Fuori scope

- Deploy cloud (AWS, Forge, Ploi)
- Terraform / IaC
- Secrets manager (Vault, AWS SM)
- CDN asset statici

---

*Sprint 20 — 4 giugno 2026.*
