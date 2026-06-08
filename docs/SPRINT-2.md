# Sprint 2 — VFU Accettazione + Bonifica Operatore

**Stato:** ✅ Completato (core) — aggiornato 25 maggio 2026  
**App:** `new-rentri-crm/` (Laravel 12 + Livewire 4)

## Riepilogo

| Parte | Focus | Stato |
|-------|--------|-------|
| **2A** | Segreteria — wizard accettazione VFU, documenti, carico CER `16.01.04*` | ✅ |
| **2B** | Operatore — lista bonifica, wizard pericolosi/altri, magazzino + registro | ✅ |

## Route aggiunte / confermate

### Segreteria (VFU)

| Metodo | URI | Nome route |
|--------|-----|------------|
| GET | `/segreteria/vfu` | `segreteria.vfu.index` |
| GET | `/segreteria/vfu/nuovo` | `segreteria.vfu.create` |
| GET | `/segreteria/vfu/{vfuRegistration}` | `segreteria.vfu.show` |
| GET | `/segreteria/vfu/{vfuRegistration}/modifica` | `segreteria.vfu.edit` |

### Operatore (Bonifica)

| Metodo | URI | Nome route |
|--------|-----|------------|
| GET | `/operatore/bonifica` | `operatore.bonifica` |
| GET | `/operatore/bonifica/{vfu}` | `operatore.bonifica.wizard` |

## Flussi end-to-end

### Segreteria

1. `GET /segreteria/vfu/nuovo` — wizard 4 step (dati veicolo → documenti → certificato PDF → conferma).
2. `completeAccettazione()` → stato **`accettato`**, `data_accettazione` impostata.
3. Carico automatico: `registro_movimenti` (tipo carico, CER `16.01.04*`) + **`MagazzinoService::addPeso`**.

### Operatore

1. `GET /operatore/bonifica` — VFU con stato `accettato`, `attesa_bonifica` o `in_bonifica`.
2. `GET /operatore/bonifica/{vfu}` — wizard: fase pericolosi (30 gg) → altri rifiuti.
3. `completePericolosi` / `completeBonifica` → carichi su registro + giacenza `magazzino_rifiuti`; VFU → **`bonificato`**.

## Allineamento stati VFU

- **Accettazione:** `completeAccettazione` usa `VfuStato::Accettato` (compatibile con enum legacy `attesa_bonifica`).
- **Bonifica lista:** `BonificaService::queryVeicoliDaBonificare` include `accettato`, `attesa_bonifica`, `in_bonifica`.
- **Start wizard:** `startBonifica` porta `accettato` / `attesa_bonifica` → `in_bonifica`.

## Domain layer

| File | Ruolo |
|------|--------|
| `app/Domain/Vfu/VfuAccettazioneService.php` | Bozza, KPI, accettazione, carico registro + magazzino |
| `app/Domain/Vfu/VfuDocumentService.php` | Upload documenti e certificato |
| `app/Domain/Bonifica/BonificaService.php` | Lista, fasi, complete pericolosi/bonifica |
| `app/Domain/Bonifica/BonificaMovimentoService.php` | Sync movimenti, carichi registro + magazzino |
| `app/Domain/Magazzino/MagazzinoService.php` | `addPeso()` con lock pessimistico |

## Livewire & viste

| Componente | Vista |
|------------|-------|
| `Segreteria\Vfu\VfuIndex` | `livewire/segreteria/vfu/index.blade.php` |
| `Segreteria\Vfu\VfuAccettazioneWizard` | `livewire/segreteria/vfu/wizard.blade.php` |
| `Segreteria\Vfu\VfuShow` | `livewire/segreteria/vfu/show.blade.php` |
| `Operatore\Bonifica` | `livewire/operatore/bonifica.blade.php` |
| `Operatore\BonificaWizard` | `livewire/operatore/bonifica-wizard.blade.php` |

Layout: `segreteria` (`SegreteriaPage`, sidebar key `vfu`); `operatore` (`OperatorePage`, nav key `bonifica`).

## Seed & demo

| Email | Password | Ruolo |
|-------|----------|-------|
| `segreteria@example.com` | `password` | segreteria |
| `operatore@example.com` | `password` | operatore |

VFU demo bonifica (opzionale, local/testing):

```bash
php artisan db:seed --class=BonificaTestSeeder
```

## Test

```bash
cd new-rentri-crm
php artisan migrate:fresh --seed
php artisan test
npm run build
```

**Suite Sprint 2 (11 test):**

- `tests/Feature/Sprint2/VfuAccettazioneTest.php` (5)
- `tests/Feature/Sprint2/BonificaServiceTest.php` (3)
- `tests/Feature/Sprint2/BonificaHttpTest.php` (3)

**Totale progetto:** 24 test, 49 asserzioni (inclusi Sprint 0–1 ed esempi).

## Fuori scope (Sprint 3+)

- UI magazzino segreteria (altro agente)
- Email/PDF bonifica pericolosi e certificato definitivo
- Invio agenzia reale (stub presente)
