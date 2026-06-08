# Runbook prep export audit schedulato — CRM RENTRI

**Stato:** ✅ live (Sprint 73) — export CSV su storage configurabile + download firmato admin.

---

## Obiettivo

Automatizzare export periodico dell'audit log (Spatie Activity Log) e trail RENTRI registro per compliance e backup, senza impatto operativo daytime.

---

## Scope export

| Tipo | Fonte | Formato | Destinatario |
|------|-------|---------|--------------|
| Activity log | `activity_log` | CSV + JSON gzip | Storage S3 `audit-exports/` |
| RENTRI registro | `RentriRegistroAuditExportService` | JSON per trasmissione | Stesso bucket, prefisso `rentri/` |
| Legacy import | Modulo `legacy` | CSV eventi | Mensile |

---

## Scheduling proposto

| Job | Frequenza | Orario (Europe/Rome) | Comando |
|-----|-----------|----------------------|---------|
| Audit log export | Settimanale | Lunedì 02:00 | `audit:export-scheduled` |
| RENTRI audit trail | Post-trasmissione | Event-driven (futuro) | — |
| Retention cleanup | Mensile | 1° del mese 03:00 | `audit:purge-exports` (futuro) |

## Comando live (Sprint 73)

```bash
# Export settimanale (default ultimi 7 gg)
php artisan audit:export-scheduled

# Dry-run
php artisan audit:export-scheduled --dry-run

# Accoda job Horizon
php artisan audit:export-scheduled --queue
```

**Config:** `AUDIT_EXPORT_DISK=audit_exports` (local) o `s3` · `AUDIT_EXPORT_RETENTION_DAYS=90`

---

## Policy accesso

- Solo ruolo `admin` può scaricare export manuali da `/admin/audit`.
- Export schedulato scritto su storage privato; link firmati TTL 24h (futuro).
- Demo mode: export esclude record `demo_mode=true` salvo flag `--include-demo`.

---

## Checklist attivazione

1. [ ] Cron server: `* * * * * php artisan schedule:run`
2. [ ] Storage disk `audit_exports` configurato (S3 o local encrypted)
3. [ ] Notifica email admin su fallimento job
4. [ ] Test dry-run: `php artisan audit:export-scheduled --dry-run`
5. [ ] Verifica indici query (`created_at`, `log_name+created_at`) — Sprint 55

---

## Rollback

Commentare la riga `Schedule::command` in `routes/console.php` e redeploy. Nessuna migrazione dati.

---

## Fuori scope (Sprint 59)

- Implementazione S3 upload reale.
- Cifratura at-rest export (KMS) — Sprint 60 security sign-off.
