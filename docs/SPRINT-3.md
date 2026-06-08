# Sprint 3 — Magazzino rifiuti + Registro movimenti UI

## Obiettivo

Dashboard magazzino per codice CER (serbatoi), dettaglio con cronologia `registro_movimenti`, carico manuale con lock pessimistico, e pagina registro movimenti filtrabile.

**Fuori scope:** trasporti, FIR, `magazzino_svuotamenti` (Sprint 6).

## Domain

| Servizio | Percorso | Responsabilità |
|----------|----------|----------------|
| `MagazzinoService` | `app/Domain/Magazzino/MagazzinoService.php` | `listSerbatoi`, `summary`, `contatori`, `getSerbatoioDetail`, `caricoManuale`, `addPeso` (lock), soglie 70%/100% |
| `RegistroService` | `app/Domain/Registro/RegistroService.php` | `list` (paginato), `aggregations`, `cronologiaPerCer` |

### Soglie serbatoio

| Stato | Condizione (% su `limite_kg`) |
|-------|-------------------------------|
| `regolare` | &lt; 70% o limite assente |
| `attenzione` | ≥ 70% e ≤ 100% |
| `superata` | &gt; 100% |

## Livewire (Segreteria)

| Componente | Route | Nome route |
|------------|-------|------------|
| `MagazzinoIndex` | `/segreteria/magazzino` | `segreteria.magazzino` |
| `SerbatoioShow` | `/segreteria/magazzino/{codiceCer}` | `segreteria.magazzino.show` |
| `RegistroMovimentiIndex` | `/segreteria/registro-movimenti` | `segreteria.registro-movimenti` |

Viste: `resources/views/livewire/segreteria/magazzino/{index,show,registro}.blade.php` — stile `gestionale.css`, KPI, badge soglia.

## Autorizzazione

- `MagazzinoPolicy` + gate `magazzino.viewAny`, `magazzino.view`, `magazzino.caricoManuale`
- `RegistroMovimentoPolicy` per elenco registro
- Ruoli: `admin`, `editor`, `segreteria`

## Carico manuale (flusso)

1. `MagazzinoCaricoManuale` (storico)
2. `RegistroMovimento` (`SOURCE_CARICO_MANUALE`, tipo carico)
3. `MagazzinoService::addPeso` (transazione + `lockForUpdate`)

## Test

```bash
php artisan test --filter=Sprint3
```

`tests/Feature/Sprint3/MagazzinoServiceTest.php`:

- carico manuale incrementa giacenza + registro
- due `addPeso` nella stessa transazione sommano correttamente (lock)
- calcolo soglia 70%/100%
- summary contatori attenzione/superata

## Legacy di riferimento

- `backend/app/Http/Controllers/Api/Segreteria/MagazzinoController.php` (index, show, ricarica manuale, soglie — no trasporti)
- `frontend/src/segreteria-magazzino-rifiuti.js`, `segreteria-magazzino-serbatoio.js`

## Prossimi sprint

- Sprint 6: trasporti / svuotamenti / FIR
- Collegare `quantita_in_attesa_kg` quando esiste `magazzino_svuotamenti`
