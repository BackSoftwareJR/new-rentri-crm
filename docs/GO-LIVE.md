# Go-live — RENTRI CRM

Checklist operativa post-deploy e post-import dati legacy. Complementa [PRE-DEPLOY-CHECKLIST.md](PRE-DEPLOY-CHECKLIST.md), [DEPLOY-PRODUCTION.md](DEPLOY-PRODUCTION.md) e [MIGRAZIONE-LEGACY.md](MIGRAZIONE-LEGACY.md).

---

## 1. Sequenza import legacy (staging/produzione)

Eseguire **dopo** `php artisan migrate --force` e **prima** del go-live utenti.

```bash
php artisan rentri:import-legacy codici_cer --dry-run && php artisan rentri:import-legacy codici_cer
php artisan rentri:import-legacy anagrafiche --dry-run && php artisan rentri:import-legacy anagrafiche
php artisan rentri:import-legacy vfu --dry-run && php artisan rentri:import-legacy vfu
php artisan rentri:import-legacy movimenti --dry-run && php artisan rentri:import-legacy movimenti
php artisan rentri:import-legacy ricambi --dry-run && php artisan rentri:import-legacy ricambi
```

Ogni import scrive una voce in **Audit & activity log** (modulo `legacy`) con entità, conteggi `imported`/`skipped` e flag `dry_run`.

### Widget dashboard (Sprint 29)

In app: **Dashboard Segreteria** → sezione **Migrazione legacy** (`#migrazione-legacy`):

- KPI **Record legacy tracciati** — snapshot live da `LegacyImportService::report()`.
- Tabella breakdown per entità (anagrafiche, codici CER, VFU, movimenti, ricambi).
- Link **Audit import legacy** (solo admin) → `/admin/audit?modulo=legacy`.
- Anchor **Checklist go-live** → `#go-live-checklist` con voci operative inline.

Documentazione completa resta in questo file; il widget è il punto di ingresso operativo post-deploy.

---

## 2. Verifica post-import

### Riepilogo conteggi

```bash
php artisan rentri:import-legacy --report
```

Confrontare i totali con le aspettative del gestionale legacy (fixture stub in dev; export reale in staging).

**Output atteso fixture stub** (sequenza completa §1, senza dry-run):

```
Riepilogo import legacy nel database
  anagrafiche:  3
  codici_cer:   2
  vfu:          3
  movimenti:    3
  ricambi:      3

Totale record legacy tracciati: 14
```

In Audit admin (modulo **Migrazione legacy**): **5 eventi**, uno per entità, con dettaglio `entity · imp: N · skp: N · dry-run: no`.

### Audit trail

1. Accedere come **admin** → **Audit & activity log**.
2. Filtrare modulo **Migrazione legacy**.
3. Verificare una voce per ogni entità importata (no dry-run in produzione).
4. Controllare `imported`, `skipped`, `errors_count` nelle proprietà evento.

In alternativa (tinker):

```php
\Spatie\Activitylog\Models\Activity::query()
    ->where('log_name', 'legacy')
    ->orderByDesc('id')
    ->get(['description', 'properties', 'created_at']);
```

---

## 3. Riconciliazione magazzino

L'import legacy **non sincronizza** automaticamente giacenze magazzino.

| Controllo | Azione |
|---------|--------|
| Movimenti registro importati | Verificare saldi vs registro legacy |
| Codici CER nuovi | Controllare FK movimenti/magazzino |
| Ricambi e-commerce | Verificare giacenze `ecommerce_prodotti` |
| VFU collegati | Spot-check targhe e stati |

In caso di scostamenti: correzione manuale via UI magazzino/registro o re-import parziale dopo rollback selettivo.

---

## 4. Rollback manuale (se necessario)

Procedura completa in [MIGRAZIONE-LEGACY.md §4](MIGRAZIONE-LEGACY.md#4-rollback-manuale-sprint-26).

Ordine consigliato (inverso all'import):

1. Ricambi (`legacy_id:` in `descrizione`)
2. Movimenti registro (`legacy_id:` in `note`)
3. VFU
4. Anagrafiche
5. Codici CER (solo se nessuna FK operativa)

**Prerequisito:** backup DB immediatamente prima del rollback.

Dopo rollback:

```bash
php artisan rentri:import-legacy --report   # atteso: conteggi a zero per entità rimosse
```

---

## 5. Checklist go-live finale

- [ ] `php artisan rentri:preflight` verde — vedi anche [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) per API ministeriali
- [ ] `php artisan rentri:import-legacy --report` coerente con dati attesi
- [ ] Audit log: eventi `legacy` per ogni entità importata (no dry-run residui)
- [ ] Magazzino/registro riconciliati manualmente
- [ ] Password demo ruotate o seeder disabilitato
- [ ] `APP_DEBUG=false`, backup DB attivo

---

*Sprint 29 — 4 giugno 2026.*
