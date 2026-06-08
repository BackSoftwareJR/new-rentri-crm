# Lighthouse CI budget — CRM RENTRI

**Ciclo 5 · Sprint 60** · Soglie performance e accessibilità per pagine chiave.

---

## Obiettivo

Definire budget misurabili prima del go-live produzione. Validazione iniziale su **staging** con Chrome Lighthouse (manuale o CI).

---

## Soglie minime (mobile, simulated 4G)

| Categoria | Score minimo | Note |
|-----------|--------------|------|
| **Performance** | ≥ 70 | Accettabile per app Livewire interna |
| **Accessibility** | ≥ 90 | Allineato WCAG via axe |
| **Best Practices** | ≥ 85 | HTTPS, console errors |
| **SEO** | ≥ 80 | App privata — bassa priorità |

### Metriche Core Web Vitals (target)

| Metrica | Target | Bloccante se |
|---------|--------|--------------|
| LCP | ≤ 3.0 s | > 4.0 s |
| INP / TBT proxy | ≤ 300 ms | > 500 ms |
| CLS | ≤ 0.1 | > 0.25 |

---

## Pagine da misurare

| Pagina | Path | Priorità |
|--------|------|----------|
| Login | `/login` | P0 |
| Dashboard | `/segreteria` | P0 |
| Registro | `/segreteria/registro-movimenti` | P1 |
| RENTRI | `/segreteria/rentri` | P1 |
| Operatore home | `/operatore` | P1 |

---

## Esecuzione manuale

1. Chrome DevTools → Lighthouse → Mobile → Analyze.
2. Ripetere 3 volte, registrare mediana.
3. Testare con tema light e dark.

```bash
# CLI opzionale (richiede npm install -g lighthouse)
lighthouse http://localhost:8000/login --only-categories=performance,accessibility --output=json --output-path=./results/lighthouse-login.json
```

---

## Config stub CI

File: `scripts/lighthouse-budget.json`

Integrazione `@lhci/cli` **non attiva** in ciclo 5 — config pronta per pipeline staging:

```bash
# Futuro
npx @lhci/cli autorun --config=scripts/lighthouserc.json
```

---

## Esclusioni note

- **Livewire** — hydration aggiunge JS; TBT su pagine autenticate può essere più alto del login.
- **Horizon / admin** — fuori budget go-live operatore.
- **PDF preview iframe** — escluso da scan automatico.

---

## Registro misurazioni

| Data | Pagina | Perf | A11y | LCP | Esito | Note |
|------|--------|------|------|-----|-------|------|
|      | /login |      |      |     |       |      |

---

## Riferimenti

- [A11Y-AUDIT-RUNBOOK.md](A11Y-AUDIT-RUNBOOK.md)
- [GO-LIVE-360.md](GO-LIVE-360.md) §3.3
- k6 load: `scripts/k6-smoke.js`
