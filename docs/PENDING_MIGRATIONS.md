# Pending database migrations (since 2026-06-08)

Migrations in this batch were added from **2026-06-08** onward and must be applied on **staging** and **production** before deploying application code that depends on them. Laravel runs migrations in filename order; use a single `migrate` command unless you need to roll back.

**Count:** 36 migrations

## Inventory (run order)

| # | Filename | Description | Dependencies |
|---|----------|-------------|--------------|
| 1 | `2026_06_08_100000_mud_sprint65_invio_telematico.php` | Adds MUD telematic submission fields (`inviata_at`, `invio_protocollo`, `invio_risposta`) on `mud_dichiarazioni`. | Requires existing `mud_dichiarazioni` table. |
| 2 | `2026_06_08_143240_add_active_last_login_to_users_table.php` | Adds `active` and `last_login_at` on `users` for admin user management. | Requires `users`. |
| 3 | `2026_06_08_181000_create_stripe_disputes_table.php` | Creates `stripe_disputes` linked to e-commerce orders. | Requires `ecommerce_ordini`. |
| 4 | `2026_06_08_200000_sprint102_smontaggio_workflow.php` | Creates `smontaggio_sessions` and `smontaggio_ricambi` for operator dismantling workflow. | Requires `vfu_registrations`, `users`. |
| 5 | `2026_06_08_200001_sprint102_notifications_table.php` | Creates Laravel `notifications` table (UUID, morphs, read_at). | None (standard notifications schema). |
| 6 | `2026_06_08_210000_create_fatture_table.php` | Creates `fatture` (invoicing header: cliente, totals, stato, optional VFU link). | Requires `anagrafiche`, `vfu_registrations`. |
| 7 | `2026_06_08_210001_create_righe_fattura_table.php` | Creates `righe_fattura` line items for invoices. | Requires `fatture` (#6). |
| 8 | `2026_06_08_220000_create_company_settings_table.php` | Creates `company_settings` key/value store for company branding and config. | None. |
| 9 | `2026_06_08_230000_add_rentri_verification_to_anagrafiche_table.php` | Adds RENTRI verification timestamp, enrollment number, and outcome on `anagrafiche`. | Requires `anagrafiche`. |
| 10 | `2026_06_09_100000_add_two_factor_to_users_table.php` | Adds `two_factor_secret` and `two_factor_confirmed_at` on `users`. | Requires `users`. |
| 11 | `2026_06_09_100000_ecommerce_sprint96_stripe_gateway.php` | Adds Stripe checkout fields on `ecommerce_ordini` (`payment_gateway`, session id, checkout URL). | Requires `ecommerce_ordini`. |
| 12 | `2026_06_09_110000_add_two_factor_recovery_codes_to_users_table.php` | Adds `two_factor_recovery_codes` JSON on `users`. | Requires `users`; best after #10 (2FA columns). |
| 13 | `2026_06_09_120000_add_rottamato_at_to_vfu_registrations_table.php` | Adds `rottamato_at` on `vfu_registrations`. | Requires `vfu_registrations`. |
| 14 | `2026_06_09_130000_add_email_proprietario_to_vfu_registrations_table.php` | Adds owner email on `vfu_registrations`. | Requires `vfu_registrations`. |
| 15 | `2026_06_09_140000_add_mase_fields_to_vfu_registrations_table.php` | Adds MASE/accettazione wizard fields (identity doc, nationality, foreign plate, etc.) on `vfu_registrations`. | Requires `vfu_registrations`. |
| 16 | `2026_06_09_150000_add_standalone_fields_to_trasporti_table.php` | Adds standalone transport FKs and fields (trasportatore, VFU, FIR blocco, targa, conducente, data). | Requires `trasporti`, `anagrafiche`, `vfu_registrations`, `fir_blocchi` (or equivalent FIR table). |
| 17 | `2026_06_09_160000_add_ecommerce_ordine_id_to_fatture_table.php` | Links `fatture` to `ecommerce_ordini`. | Requires `fatture` (#6), `ecommerce_ordini`. |
| 18 | `2026_06_09_160000_add_pec_proprietario_to_vfu_registrations_table.php` | Adds owner PEC on `vfu_registrations`. | Requires `vfu_registrations`; after #14 if using column order after email. |
| 19 | `2026_06_10_100000_create_siti_table.php` | Creates `siti` (multi-impianto / site registry). | None. |
| 20 | `2026_06_10_100000_rentri_sprint69_live_mode.php` | Adds RENTRI live-mode timestamps on `rentri_settings`. | Requires `rentri_settings`. |
| 21 | `2026_06_10_100000_stripe_webhook_events_sprint103.php` | Creates `stripe_webhook_events` for idempotent Stripe webhook processing. | Requires `ecommerce_ordini`. |
| 22 | `2026_06_10_100000_trasporti_sprint98_gps_tracking.php` | Adds GPS last position and tracking timestamp on `trasporti`. | Requires `trasporti`. |
| 23 | `2026_06_10_100001_add_sito_relations.php` | Adds nullable `sito_id` FK on `users` and `rentri_settings`. | Requires `siti` (#19). |
| 24 | `2026_06_10_110000_create_push_subscriptions_table.php` | Creates `push_subscriptions` for Web Push (VAPID). | Requires `users`. |
| 25 | `2026_06_10_120000_add_soglia_minima_kg_to_magazzino_rifiuti_table.php` | Adds low-stock threshold `soglia_minima_kg` on `magazzino_rifiuti`. | Requires `magazzino_rifiuti`. |
| 26 | `2026_06_10_120000_make_ecommerce_ordini_user_id_nullable.php` | Makes `ecommerce_ordini.user_id` nullable for guest checkout. | Requires `ecommerce_ordini`, `users`; needs `doctrine/dbal` or Laravel column change support. |
| 27 | `2026_06_10_130000_add_fatturapa_fields_to_fatture_table.php` | Adds FatturaPA XML path and `sdi_stato` on `fatture`. | Requires `fatture` (#6). |
| 28 | `2026_06_10_130000_add_sito_id_to_vfu_registrations_table.php` | Adds `sito_id` FK on `vfu_registrations`. | Requires `siti` (#19). |
| 29 | `2026_06_10_130001_add_sito_id_to_trasporti_table.php` | Adds `sito_id` FK on `trasporti`. | Requires `siti` (#19). |
| 30 | `2026_06_10_130002_add_sito_id_to_registro_movimenti_table.php` | Adds `sito_id` FK on `registro_movimenti`. | Requires `siti` (#19). |
| 31 | `2026_06_10_130003_add_sito_id_to_fatture_table.php` | Adds `sito_id` FK on `fatture`. | Requires `siti` (#19), `fatture` (#6). |
| 32 | `2026_06_10_140000_add_operatore_assegnato_id_to_vfu_registrations_table.php` | Adds assigned operator FK on `vfu_registrations`. | Requires `vfu_registrations`, `users`. |
| 33 | `2026_06_10_140001_add_sito_id_to_magazzino_rifiuti_table.php` | Adds `sito_id` and changes unique index to `(codice_cer_id, sito_id)` on `magazzino_rifiuti`. | Requires `siti` (#19), `magazzino_rifiuti`; may backfill default site in migration. |
| 34 | `2026_06_11_100000_sprint71_bonifica_operatore.php` | Adds `checklist_pericolosi` on `bonifica_vfu` and `ecommerce_prodotto_foto_operatore` table. | Requires `bonifica_vfu`, `ecommerce_prodotti`, `users`. |
| 35 | `2026_06_12_100000_sprint72_legacy_import_sync.php` | Creates `legacy_import_sync_runs` audit table for legacy import jobs. | Requires `users`. |
| 36 | `2026_06_13_100000_sprint73_audit_export_runs.php` | Creates `audit_export_runs` for audit log export metadata. | Requires `users`. |

## Deployment commands

Run from the application root on the target environment **after** backup and **before** or with the code release (maintenance window recommended for column-type changes).

### Staging / production (recommended)

```bash
cd /path/to/new-rentri-crm

# Optional: confirm pending migrations
php artisan migrate:status | grep -E '2026_06_0[89]|2026_06_1[0-3]' || true

# Apply all pending migrations in order (includes the 36 above if not yet run)
php artisan migrate --force

# Post-check
php artisan migrate:status
```

### Run only through a specific migration (emergency / partial)

Use only if ops requires stopping at a known good point. Filenames must match exactly.

```bash
php artisan migrate --force --path=database/migrations/2026_06_08_100000_mud_sprint65_invio_telematico.php
# ... repeat per file in table order, or prefer single migrate --force
```

### Rollback last batch (use with caution)

```bash
php artisan migrate:rollback --step=1 --force
```

## Notes for deployment

- **`migrate --force`** is required in production when `APP_ENV=production`.
- Migration **#26** alters column nullability; ensure DB user can modify `ecommerce_ordini.user_id` and that backups exist.
- Migration **#33** may re-seed or assign a default `sito_id` for existing warehouse rows; review `SitoSeeder` / default site after migrate.
- After **#19** and sito FK migrations, run **`php artisan db:seed --class=SitoSeeder`** if the environment has no default site (only if seeder is part of your release process).
- Same-timestamp files (e.g. four files on `2026_06_10_100000_*`) rely on Laravel’s alphabetical ordering; do not rename without updating this doc.

