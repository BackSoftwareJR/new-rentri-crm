# Migrazione legacy → new-rentri-crm

Inventario entità e mapping per import dati dal gestionale legacy (stub MVP — file fixture, no connessione DB legacy).

---

## 1. Ambito Sprint 19–26

| Entità | Priorità | Formato fixture | Stato import stub |
|--------|----------|-----------------|-------------------|
| Anagrafiche | Alta | CSV | ✅ `anagrafiche.csv` |
| Codici CER | Alta | JSON | ✅ `codici_cer.json` |
| VFU | Media | CSV | ✅ `vfu.csv` |
| Movimenti registro | Bassa | JSON | ✅ `movimenti.json` |
| E-commerce ricambi | Bassa | CSV | ✅ `ricambi.csv` |

---

## 2. Mapping tabelle

### Anagrafiche

| Campo legacy (stub CSV) | Colonna `anagrafiche` | Note |
|-------------------------|----------------------|------|
| `legacy_id` | `note` | Prefisso `legacy_id:LEG-A-xxxx` |
| `tipo` | `tipo` | `trasportatore`, `impianto`, `privato`, `agenzia_pratiche` |
| `ragione_sociale` | `ragione_sociale` | obbligatorio |
| `piva` | `piva` | dedup per import |
| `email` | `email` | |
| `citta` | `citta` | |
| — | `gestisce_trasporti` | `false` (default) |

**Dedup import:** stessa `piva` (non vuota) → **skipped**.

### Codici CER

| Campo legacy (stub JSON) | Colonna `codici_cer` | Note |
|--------------------------|---------------------|------|
| `codice` | `codice` | unique, dedup |
| `descrizione` | `descrizione` | |
| `categoria` | `categoria` | `pericoloso` \| `altro` |

**Dedup import:** `codice` già presente → **skipped**.

### VFU (Sprint 21)

| Campo legacy (stub CSV) | Colonna `vfu_registrations` | Note |
|-------------------------|----------------------------|------|
| `legacy_id` | `note` | Prefisso `legacy_id:LEG-V-xxxx` |
| `targa` | `targa` | dedup |
| `stato` | `stato` | enum `VfuStato` |

**Dedup import:** stessa `targa` o stesso `telaio` → **skipped**.

### Movimenti registro (Sprint 23)

| Campo legacy (stub JSON) | Colonna `registro_movimenti` | Note |
|--------------------------|------------------------------|------|
| `legacy_id` | `note` | Prefisso `legacy_id:LEG-M-xxxx` |
| `codice_cer` | `codice_cer_id` | lookup CER esistente |

**Prerequisito:** import `codici_cer` prima.

### Ricambi e-commerce (Sprint 25)

| Campo legacy (stub CSV) | Colonna `ecommerce_prodotti` | Note |
|-------------------------|------------------------------|------|
| `legacy_id` | `descrizione` | Prefisso `legacy_id:LEG-R-xxxx` |
| `codice` | `codice` | unique, dedup |
| `nome` | `nome` | obbligatorio |
| `descrizione` | `descrizione` | append dopo legacy_id |
| `categoria` | `categoria` | default `generico` |
| `prezzo` | `prezzo` | decimal |
| `giacenza` | `giacenza` | integer (valore fixture, no sync magazzino) |
| `targa_vfu` | `vfu_registration_id` | lookup opzionale per targa |
| — | `attivo` | `true` |

**Tabella legacy stimata:** `ricambi` / `prodotti_magazzino`.

**Dedup import:** stesso `codice` → **skipped**.

**Fuori scope:** immagini prodotto, sync giacenza da magazzino fisico, ordini legacy.

---

## 3. Command Artisan

```bash
# Dry-run singola entità
php artisan rentri:import-legacy ricambi --dry-run

# Sequenza completa post-migrate (vedi PRE-DEPLOY-CHECKLIST.md)
php artisan rentri:import-legacy codici_cer
php artisan rentri:import-legacy anagrafiche
php artisan rentri:import-legacy vfu
php artisan rentri:import-legacy movimenti
php artisan rentri:import-legacy ricambi

# Riepilogo record legacy nel DB
php artisan rentri:import-legacy --report
```

Entità supportate: `anagrafiche` | `codici_cer` | `vfu` | `movimenti` | `ricambi`

Fixture default: `database/fixtures/legacy/{entity}.csv|json`

---

## 4. Rollback manuale (Sprint 26)

**Nessun rollback automatico** — procedura manuale in ordine **inverso** all'import.

| Ordine | Entità | Criterio identificazione | Comando / query |
|--------|--------|--------------------------|-----------------|
| 1 | Ricambi | `descrizione` LIKE `legacy_id:%` | `EcommerceProdotto::where('descrizione','like','legacy_id:%')->delete()` |
| 2 | Movimenti | `note` LIKE `legacy_id:%` | `RegistroMovimento::where('note','like','legacy_id:%')->delete()` |
| 3 | VFU | `note` LIKE `legacy_id:%` | `VfuRegistration::where('note','like','legacy_id:%')->delete()` |
| 4 | Anagrafiche | `note` LIKE `legacy_id:%` | `Anagrafica::where('note','like','legacy_id:%')->delete()` |
| 5 | Codici CER | codici presenti in fixture | Delete per `codice` da `codici_cer.json` (verificare FK movimenti/magazzino) |

### Esempio tinker (staging)

```bash
php artisan tinker
```

```php
// Verifica conteggi prima del rollback
app(\App\Domain\Legacy\LegacyImportService::class)->report();

// Rollback ricambi → movimenti → vfu → anagrafiche
\App\Models\EcommerceProdotto::where('descrizione', 'like', 'legacy_id:%')->delete();
\App\Models\RegistroMovimento::where('note', 'like', 'legacy_id:%')->delete();
\App\Models\VfuRegistration::where('note', 'like', 'legacy_id:%')->delete();
\App\Models\Anagrafica::where('note', 'like', 'legacy_id:%')->delete();

// Codici CER: solo se nessun movimento operativo li referenzia
$codici = collect(json_decode(file_get_contents(database_path('fixtures/legacy/codici_cer.json')), true))
    ->pluck('codice');
\App\Models\CodiceCer::whereIn('codice', $codici)->delete();
```

**Attenzione:** eliminare movimenti/CER può lasciare giacenze magazzino inconsistenti — riconciliare manualmente. Eseguire backup DB prima del rollback.

---

## 5. Fuori scope

- Connessione DB legacy produzione
- Rollback automatico
- Immagini ricambi
- Sync magazzino post-import

---

## 6. Sync incrementale (Sprint 72)

```bash
# Sync incrementale da fixture (anagrafiche, CER, movimenti)
php artisan legacy:sync-incremental

# Dry-run
php artisan legacy:sync-incremental --dry-run

# Accoda job Horizon
php artisan legacy:sync-incremental --queue
```

**Entità sync:** `codici_cer` → `anagrafiche` → `movimenti` (fixture, no DB legacy live).

**Diff report:** tabella `legacy_import_sync_runs` + widget dashboard «Ultimo sync incrementale».

---

## 7. Verifica post-import

```bash
php artisan test --filter=LegacyImport
php artisan rentri:import-legacy --report
```

---

*Sprint 26 — 4 giugno 2026.*
