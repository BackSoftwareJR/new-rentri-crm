# Runbook audit accessibilità (axe) — CRM RENTRI

**Ciclo 5 · Sprint 60** · Audit manuale e stub CI per pagine chiave.

---

## Obiettivo

Verificare conformità WCAG 2.1 AA sulle pagine critiche segreteria/operatore prima del go-live, complementando i test automatici PHPUnit (aria-live, focus ring).

---

## Pagine obbligatorie

| # | URL | Ruolo | Focus |
|---|-----|-------|-------|
| 1 | `/login` | pubblico | Form, label, contrasto |
| 2 | `/segreteria` | segreteria | KPI, tour, toggle tema |
| 3 | `/segreteria/vfu` | segreteria | Tabella, empty state |
| 4 | `/segreteria/registro-movimenti` | segreteria | Tabella, print |
| 5 | `/segreteria/rentri` | segreteria | Form periodo, checklist |
| 6 | `/segreteria/impostazioni/rentri` | segreteria | Wizard, upload |
| 7 | `/segreteria/trasporti` | segreteria | Elenco, stati |
| 8 | `/operatore` | operatore | Bottom nav, touch target |
| 9 | `/operatore/bonifica` | operatore | Wizard mobile |

Config pagine: `scripts/a11y-pages.json`.

---

## Metodo A — Browser extension (manuale)

1. Installare [axe DevTools](https://www.deque.com/axe/devtools/) (Chrome/Firefox).
2. Avviare app locale: `php artisan serve`.
3. Login con utente appropriato per ogni area.
4. Per ogni URL in tabella:
   - Aprire pagina
   - axe DevTools → **Scan ALL of my page**
   - Esportare report JSON se critical/serious > 0
5. Temi da testare: **light**, **dark**, **high-contrast** (toggle topbar).

### Soglie accettazione

| Severità axe | Go-live |
|--------------|---------|
| Critical | 0 — bloccante |
| Serious | 0 — bloccante (o ticket con data fix) |
| Moderate | ≤ 5 totali documentati |
| Minor | best effort |

---

## Metodo B — CLI stub (CI future)

**Prerequisiti:** Node 18+, app in esecuzione su `BASE_URL`.

```bash
# Installazione one-time (non in package.json — evitare dep pesante in MVP)
npm install -g @axe-core/cli

# Scan singola pagina (richiede session cookie o pagina pubblica)
axe http://localhost:8000/login --save results/axe-login.json

# Scan multiplo da config (script wrapper)
node scripts/axe-smoke.js
```

Lo script `scripts/axe-smoke.js` verifica solo `/login` e `/up` senza autenticazione — **stub CI**. Scan autenticato richiede Playwright + axe-core (roadmap post go-live).

---

## Checklist manuale complementare

| # | Verifica | ☐ |
|---|----------|---|
| 1 | Tab attraversa tutti i controlli interattivi in ordine logico | |
| 2 | Focus visibile su sidebar, bottoni, input | |
| 3 | Flash messaggi annunciati (aria-live) | |
| 4 | Modali chiudibili con Esc | |
| 5 | Immagini/decorative con `aria-hidden` | |
| 6 | Form errori associati a label (`x-form-field`) | |
| 7 | Contrasto badge stati WCAG AA (Sprint 53) | |
| 8 | Operatore: touch target ≥ 44px | |

---

## Integrazione CI (stub)

File `.github/workflows/a11y-stub.yml` **non incluso** — aggiungere post go-live:

```yaml
# Esempio futuro
- run: node scripts/axe-smoke.js
  env:
    BASE_URL: http://127.0.0.1:8000
```

Job attuale: eseguire manualmente pre-release o in pipeline staging on-demand.

---

## Registro audit

| Data | Ambiente | Pagine | Critical | Serious | Esito | Revisore |
|------|----------|--------|----------|---------|-------|----------|
|      |          |        |          |         |       |          |

---

## Riferimenti

- [UX-GUIDELINES.md](UX-GUIDELINES.md)
- [UAT-UX-360-CHECKLIST.md](UAT-UX-360-CHECKLIST.md) §5
- [LIGHTHOUSE-BUDGET.md](LIGHTHOUSE-BUDGET.md) (categoria Accessibility)
- [GO-LIVE-360.md](GO-LIVE-360.md)
