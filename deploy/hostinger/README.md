# Deploy Hostinger — demolisci.backsoftware.it

## Struttura corretta (best practice Laravel su shared hosting)

```
~/domains/demolisci.backsoftware.it/
│
├── new-rentri-crm/          ← ROOT Laravel (PRIVATA — non accessibile via browser)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── .env                 ← MAI in public_html
│   ├── vendor/
│   ├── storage/
│   ├── artisan
│   └── public/              ← sorgente asset pubblici
│
└── public_html/             ← DOCUMENT ROOT Hostinger (SOLO file pubblici)
    ├── index.php            ← punta a ../new-rentri-crm/
    ├── .htaccess
    ├── build/               ← asset Vite compilati
    ├── favicon.ico
    ├── robots.txt
    └── storage/             ← symlink → ../new-rentri-crm/storage/app/public
```

**Regola:** tutto ciò che è in `new-rentri-crm/` (eccetto ciò che viene copiato in `public_html/`) **non deve** essere raggiungibile via URL.

## Metodo consigliato: public_html separato + index.php custom

Hostinger imposta `public_html` come document root e non sempre permette di cambiarlo.
Quindi:

1. Il codice Laravel resta in `new-rentri-crm/`
2. In `public_html/` ci vanno solo i file di `public/` + `index.php` modificato
3. Lo script `setup-public_html.sh` automatizza la sincronizzazione

## Metodo alternativo: symlink intero public_html

Se Hostinger permette symlink (verificare con `ln -s`):

```bash
cd ~/domains/demolisci.backsoftware.it
mv public_html public_html.bak
ln -sfn new-rentri-crm/public public_html
```

In questo caso **non** serve `index.php` custom — usa quello standard di Laravel.

## Setup iniziale (SSH)

```bash
cd ~/domains/demolisci.backsoftware.it

# 1. App Laravel fuori dal web root
git clone -b staging https://github.com/BackSoftwareJR/new-rentri-crm.git new-rentri-crm
cd new-rentri-crm

# 2. .env produzione (copia dal Mac o nano)
nano .env

# 3. Dipendenze + Laravel
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link   # crea storage/app/public nel progetto
php artisan config:cache && php artisan route:cache && php artisan view:cache

# 4. Build asset (se Node non disponibile: upload public/build dal Mac)
npm ci && npm run build

# 5. Sincronizza public_html
cd ~/domains/demolisci.backsoftware.it
bash new-rentri-crm/deploy/hostinger/setup-public_html.sh
```

## Aggiornamenti (deploy successivi)

```bash
cd ~/domains/demolisci.backsoftware.it/new-rentri-crm
git pull origin staging
composer install --no-dev --optimize-autoloader
npm ci && npm run build   # oppure upload public/build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
cd ~/domains/demolisci.backsoftware.it
bash new-rentri-crm/deploy/hostinger/setup-public_html.sh
```

## Cosa NON mettere in public_html

- `.env`
- `vendor/`
- `app/`, `config/`, `database/`, `routes/`
- `storage/logs/`
- `composer.json`, `artisan`

## Cron (hPanel)

```cron
* * * * * cd ~/domains/demolisci.backsoftware.it/new-rentri-crm && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd ~/domains/demolisci.backsoftware.it/new-rentri-crm && php artisan queue:work database --stop-when-empty --max-time=55 >> /dev/null 2>&1
```
