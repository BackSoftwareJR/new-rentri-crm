# MySQL Production Setup — RENTRI CRM

This guide covers setting up MySQL 8.0+ for the RENTRI CRM in production.

---

## Prerequisites

| Requirement | Version |
|-------------|---------|
| MySQL       | 8.0+    |
| PHP         | 8.2+    |
| `ext-pdo_mysql` | enabled |
| `ext-intl` | enabled (for date formatting) |

---

## 1. Database & User Creation

Connect as MySQL root (or a superuser) and run:

```sql
-- Create the database
CREATE DATABASE rentri_crm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create a dedicated application user (replace strong_password)
CREATE USER 'rentri_crm_user'@'127.0.0.1'
    IDENTIFIED BY 'strong_password_here';

-- Minimum required privileges
GRANT SELECT, INSERT, UPDATE, DELETE,
      CREATE, DROP, INDEX, ALTER,
      CREATE TEMPORARY TABLES, LOCK TABLES,
      REFERENCES, EXECUTE
    ON rentri_crm.*
    TO 'rentri_crm_user'@'127.0.0.1';

-- Apply changes
FLUSH PRIVILEGES;
```

> **Security note:** Never grant `SUPER`, `FILE`, or `GRANT OPTION` to the application user.
> If the app connects from a different host (e.g. a separate app server), replace `127.0.0.1` with
> the application server IP.

---

## 2. `.env` Configuration Block

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rentri_crm
DB_USERNAME=rentri_crm_user
DB_PASSWORD=strong_password_here

# Optional but recommended for strict mode compatibility
DB_STRICT=true
DB_ENGINE=InnoDB
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_TIMEZONE=+00:00
```

---

## 3. Migration Sequence

Run migrations with the `--force` flag to bypass the production environment guard:

```bash
# Run all pending migrations
php artisan migrate --force

# Verify all migrations ran successfully
php artisan migrate:status

# If starting fresh (staging → production initial deploy)
php artisan migrate:fresh --force --seed
```

> **Warning:** `migrate:fresh` drops all tables. Never run it against a production DB with real data.

---

## 4. Recommended Indexes for High-Traffic Tables

The base migration `2026_05_24_100018_add_performance_indexes.php` already adds indexes.
After any large initial data import, consider running `ANALYZE TABLE` on the following:

### `registro_movimenti`

```sql
-- Already present in migration; verify with:
SHOW INDEX FROM registro_movimenti;

-- Additional compound index for date-range + CER queries:
CREATE INDEX IF NOT EXISTS idx_rm_codice_cer_data
    ON registro_movimenti (codice_cer_id, data_movimento);
```

### `rentri_transazioni`

```sql
-- Status + created_at for queue processing queries:
CREATE INDEX IF NOT EXISTS idx_rt_status_created
    ON rentri_transazioni (status, created_at);
```

### `fir` (Formulari di Identificazione Rifiuti)

```sql
-- Stato + data_trasporto for segreteria filtering:
CREATE INDEX IF NOT EXISTS idx_fir_stato_data
    ON fir (stato, data_trasporto);
```

### `vfu_registrations`

```sql
-- Targa lookups (already unique but covering index helps JOINs):
CREATE INDEX IF NOT EXISTS idx_vfu_targa_stato
    ON vfu_registrations (targa, stato);
```

---

## 5. Backup Strategy

### Daily dump with `mysqldump`

```bash
#!/bin/bash
# /usr/local/bin/backup-rentri-crm.sh
set -euo pipefail

BACKUP_DIR="/var/backups/rentri-crm"
DATE=$(date +%Y-%m-%d_%H%M)
FILE="${BACKUP_DIR}/rentri_crm_${DATE}.sql.gz"

mkdir -p "$BACKUP_DIR"

mysqldump \
    --host=127.0.0.1 \
    --user=rentri_crm_user \
    --password=strong_password_here \
    --single-transaction \
    --routines \
    --triggers \
    --set-gtid-purged=OFF \
    rentri_crm \
  | gzip > "$FILE"

# Retain last 14 daily backups
find "$BACKUP_DIR" -name "rentri_crm_*.sql.gz" -mtime +14 -delete

echo "Backup completed: $FILE"
```

Store the password in `/root/.my.cnf` or use `--login-path` instead of embedding it in the script.

Schedule via cron:

```cron
0 2 * * * /usr/local/bin/backup-rentri-crm.sh >> /var/log/rentri-backup.log 2>&1
```

---

## 6. MySQL Performance Configuration

Add or adjust in `/etc/mysql/mysql.conf.d/mysqld.cnf` (Ubuntu/Debian) or `/etc/my.cnf.d/server.cnf`:

```ini
[mysqld]
# --- InnoDB buffer pool (set to 60–70% of available RAM) ---
# Example for a 4 GB VPS:
innodb_buffer_pool_size       = 2G
innodb_buffer_pool_instances  = 2   # one per GB of buffer pool

# --- Redo log ---
innodb_log_file_size          = 256M
innodb_log_buffer_size        = 16M
innodb_flush_log_at_trx_commit = 1  # 1 = ACID-safe (default); 2 = faster, slight data-loss risk

# --- I/O ---
innodb_flush_method           = O_DIRECT
innodb_io_capacity            = 200    # raise to 1000+ for SSD
innodb_io_capacity_max        = 2000

# --- Query cache (disabled in MySQL 8, kept here for reference) ---
# query_cache_type = 0  (already default in MySQL 8)

# --- Connection handling ---
max_connections               = 150
wait_timeout                  = 300
interactive_timeout           = 300
thread_cache_size             = 16

# --- Slow query log ---
slow_query_log                = 1
slow_query_log_file           = /var/log/mysql/slow.log
long_query_time               = 2

# --- Character set ---
character-set-server          = utf8mb4
collation-server               = utf8mb4_unicode_ci
```

Restart MySQL after changes:

```bash
sudo systemctl restart mysql
```

---

## 7. Connection Pooling for Laravel Queue Workers

Laravel does not have built-in connection pooling, but each queue worker maintains a persistent
connection. Key practices:

### `.env` settings for queue workers

```dotenv
# Reduce connection overhead per worker
DB_HOST=127.0.0.1  # use TCP, not socket, for consistent behaviour
QUEUE_CONNECTION=database

# For high-volume queues, consider Redis instead:
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
```

### Supervisor configuration (`/etc/supervisor/conf.d/rentri-queue.conf`)

```ini
[program:rentri-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/rentri-crm/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/log/rentri-queue.log
```

> **Note:** Set `numprocs` to the number of concurrent workers you need. Each worker opens one
> DB connection. Keep `numprocs * max_connections_per_worker < max_connections`.

### Using Laravel Horizon (recommended for production)

If Laravel Horizon is installed, it manages workers automatically. Check status with:

```bash
php artisan horizon:status
```

---

## 8. Switching from SQLite (Dev) to MySQL (Production)

If you have existing SQLite dev data to migrate:

### Option A — fresh start (recommended for production)

```bash
# On the production server — no SQLite data to migrate
php artisan migrate:fresh --force
php artisan db:seed --class=ProductionSeeder   # if applicable
```

### Option B — migrate data from SQLite

1. Export SQLite tables to CSV:

```bash
sqlite3 database/database.sqlite ".mode csv" ".output /tmp/users.csv" "SELECT * FROM users;" ".quit"
# repeat for each table
```

2. Import into MySQL:

```sql
LOAD DATA LOCAL INFILE '/tmp/users.csv'
    INTO TABLE users
    FIELDS TERMINATED BY ',' ENCLOSED BY '"'
    LINES TERMINATED BY '\n'
    IGNORE 1 ROWS;
```

3. Verify row counts and foreign key integrity before going live:

```sql
-- Example check
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM ecommerce_ordini;
```

> **Note:** Timestamps in SQLite may be stored as strings; verify `DATETIME` columns imported correctly.
> Boolean columns (`0/1`) should map correctly to MySQL `TINYINT(1)`.

---

## 9. Post-Deployment Checklist

- [ ] `php artisan migrate:status` — all migrations `Ran`
- [ ] `php artisan config:cache` — configuration cached
- [ ] `php artisan route:cache` — routes cached
- [ ] `php artisan view:cache` — views compiled
- [ ] `php artisan queue:restart` — workers using fresh config
- [ ] Test DB connection: `php artisan tinker --execute="DB::select('SELECT 1')"`
- [ ] Run `php artisan rentri:go-live --dry-run` — all checks green

---

*Last updated: June 2026 — RENTRI CRM Sprint 117.*
