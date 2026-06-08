# Sprint 115 — Audit notes: Mobile app operatore PWA

**Data audit:** 4 giugno 2026 · **Ciclo 10**

---

## Implementazione

| Componente | Ruolo |
|------------|--------|
| `OperatoreMobileApiService` | Serializzazione JSON bonifica/ricambi/vetrina |
| `OperatoreApiController` | GET read-only `/operatore/api/*` |
| `OperatorePwaManifestController` | Web manifest installabile |
| `operatore-sw.js` | Service worker shell offline + API 503 stub |
| `config/operatore.php` | Config PWA |
| `docs/OPERATORE-PWA.md` | Strategia cache/offline |

---

## Riferimenti

- [SPRINT-115-REVIEW-HANDOFF.md](SPRINT-115-REVIEW-HANDOFF.md)
- [CICLO-10-PIANO.md](CICLO-10-PIANO.md)
