# Runbook prep sessioni Redis — staging / multi-istanza HA

**Stato:** Sprint 108 · prep documentale — **Redis session non attivo** in locale/dev default.

**HA admin UI:** `/admin/ha-status` · Runbook: [HA-BACKUP-DRILL-RUNBOOK.md](HA-BACKUP-DRILL-RUNBOOK.md).

---

## Obiettivo

Migrare le sessioni Laravel da `file`/`database` a **Redis** in ambiente staging per testare scalabilità orizzontale e coerenza sessioni multi-istanza prima del go-live.

---

## Prerequisiti staging

| Componente | Versione minima | Note |
|------------|-----------------|------|
| Redis | 6.x+ | ElastiCache o container dedicato |
| PHP ext-redis | 5.x+ | Preferito a predis per performance |
| Laravel | 11.x | Driver `redis` nativo |

---

## Configurazione proposta

### `.env.staging`

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

REDIS_HOST=redis.staging.internal
REDIS_PASSWORD=***
REDIS_PORT=6379

REDIS_CLIENT=phpredis
```

### `config/database.php` — connessione dedicata

```php
'redis' => [
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
    ],
],
```

### `config/session.php`

```php
'connection' => env('SESSION_CONNECTION', 'session'),
```

---

## Checklist rollout staging

1. [ ] Provisionare istanza Redis staging isolata (DB index dedicato, es. `2`).
2. [ ] Verificare `php -m | grep redis` su tutti i nodi app.
3. [ ] Deploy con `SESSION_DRIVER=redis` su **un solo** nodo staging.
4. [ ] Smoke test: login segreteria, navigazione Livewire, logout, CSRF form.
5. [ ] Smoke test: demo mode toggle + isolamento scope demo.
6. [ ] Smoke test: upload certificato RENTRI (session flash + redirect).
7. [ ] Scale a 2+ nodi app dietro load balancer — verificare sessione persistente.
8. [ ] Monitorare memoria Redis e TTL sessioni (`SESSION_LIFETIME`).

---

## Rollback

```env
SESSION_DRIVER=file
```

Riavviare PHP-FPM / Octane. Nessuna migrazione dati sessione necessaria.

---

## Rischi e mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Session loss su failover Redis | Replica Redis + persistence AOF |
| Flash Livewire persi | Test espliciti post-migrazione (Sprint 58 aria-live) |
| Cookie non secure in staging | `SESSION_SECURE_COOKIE=true` + HTTPS obbligatorio |
| Demo mode in session | Verificare `DemoContext` con driver redis |

---

---

## Multi-istanza HA (Sprint 108)

### Perché Redis session

Con **2+ nodi app** dietro load balancer, `file`/`database` session non garantisce coerenza. Redis centralizzato permette:

- Login su nodo A → request su nodo B mantiene sessione
- Livewire CSRF token condiviso
- Demo scope (`DemoContext`) in session condivisa

### Architettura cluster consigliata

| Componente | Config | Note |
|------------|--------|------|
| Redis primary | ElastiCache / Redis 6+ | DB index `2` per session |
| Redis replica | Read replica optional | Failover automatico AWS |
| Session | `SESSION_DRIVER=redis` | `SESSION_CONNECTION=session` |
| Queue | `QUEUE_CONNECTION=redis` | Stesso cluster, DB index `0` |
| Horizon | 1+ worker per nodo | Vedi [HORIZON-SCALING-RUNBOOK.md](HORIZON-SCALING-RUNBOOK.md) |

### Checklist multi-istanza (2 nodi staging)

1. [ ] Deploy identico `.env` su nodo 1 e 2 (eccetto `APP_INSTANCE_ID` opzionale).
2. [ ] `SESSION_DRIVER=redis` su **entrambi** i nodi.
3. [ ] Load balancer **senza** sticky session (Redis gestisce stato).
4. [ ] Login su nodo 1 → navigazione URL che hit nodo 2 (verificare via log `X-Instance-Id`).
5. [ ] Upload cert RENTRI + flash message cross-request.
6. [ ] Logout invalida sessione su entrambi i nodi.
7. [ ] Failover test: stop nodo 1 → app resta usable su nodo 2.
8. [ ] Monitor Redis memory + evicted keys = 0.

### Env aggiuntivi HA

```env
HA_MIN_APP_INSTANCES=2
HA_SESSION_REDIS_REQUIRED=true
DB_BACKUP_SCHEDULE_ENABLED=true
```

Preflight: `HaBackupPreflightService` in `/admin/ha-status`.

---

## Fuori scope (Sprint 58 / 108 app-side)

- Cache applicativa (`CACHE_DRIVER=redis`) — sprint performance successivo.
- Queue Horizon già documentato in Sprint 56.
- Produzione: solo dopo UAT staging 48h senza incidenti.
