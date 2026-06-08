# UX Guidelines — CRM RENTRI autodemolitore

Linee guida brevi per coerenza visiva e messaggi in italiano (Sprint 51+).

---

## Colori

| Token | Valore | Uso |
|-------|--------|-----|
| `--color-primary` | `#FF6B00` | CTA primarie, accent logo, KPI priorità |
| `--color-primary-hover` | `#E55F00` | Hover bottoni primari |
| Testo principale | `#0f172a` | Titoli, body |
| Testo secondario | `#64748b` | Lead, hint, label gruppi sidebar |
| Sfondo pagina | `#f8fafc` | Area contenuto segreteria |
| Bordo | `#e2e8f0` | Card, input, separatori |

**Stati semantici**

- Successo: verde `#059669` / sfondo `#ecfdf5` (`.seg-alert-success`)
- Errore: rosso `#dc2626` / sfondo `#fef2f2` (`.seg-alert-error`)
- Avviso: ambra `#d97706` / sfondo `#fffbeb` (`.seg-alert-warning`)

---

## Bottoni

Usare `<x-btn>` con varianti:

| Variante | Quando |
|----------|--------|
| `primary` | Azione principale della pagina (salva, trasmetti, conferma) |
| `secondary` | Azioni secondarie, link navigazione (registro, storico) |
| `ghost` | Azioni terziarie in toolbar |
| `danger` | Elimina, annulla irreversibile |

Regola: **una sola primary** per sezione visiva. Dimensione default `md`; `sm` in tabelle dense.

---

## Messaggi utente (IT)

### Flash / toast

- **Successo:** verbo al passato + esito concreto.  
  Es.: «Trasporto avviato — stato in transito.»
- **Errore:** causa + azione suggerita.  
  Es.: «Nessun movimento non trasmesso nel periodo selezionato.»
- **Avviso:** condizione temporanea o permesso limitato.  
  Es.: «Palestra operativa attiva — i dati produzione sono isolati.»

Evitare codici tecnici in messaggio principale; dettagli API in log o modale secondaria.

### Empty state

Titolo breve + una riga di contesto + CTA primary se applicabile.

Es.: «Nessun trasporto in corso» / «Crea uno svuotamento dal magazzino per avviare una spedizione.»

---

## Layout pagina

- `<x-page-header>` per titolo H1, lead opzionale, back link, slot `actions`.
- Breadcrumb implicito via back link su pagine dettaglio.
- Flash subito sotto header (`@include('livewire.partials.flash-messages')`).

---

## Sidebar segreteria

Gruppi: **Operativo** → **RENTRI** → **Amministrazione**.  
Tooltip `title` su ogni voce (max ~6 parole). Badge «Palestra ON» quando demo attivo.

---

## Mobile operatore

- Touch target minimo **44×44 px** (bottom nav, filtri).
- Contrasto testo/icona ≥ 4.5:1 su sfondo nav.

---

## Componenti riusabili

| Componente | File |
|------------|------|
| Bottone | `resources/views/components/btn.blade.php` |
| Alert | `resources/views/components/alert.blade.php` |
| Header pagina | `resources/views/components/page-header.blade.php` |
| Empty state | `resources/views/components/empty-state.blade.php` |
| Form field | `resources/views/components/form-field.blade.php` |
| Flash | `resources/views/livewire/partials/flash-messages.blade.php` |

CSS condiviso: `resources/css/gestionale.css`.
