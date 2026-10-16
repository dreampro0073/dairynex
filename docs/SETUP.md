# DairyNex setup and implemented workflows

Current requirement-by-requirement coverage: [MASTER_PROMPT_AUDIT.md](MASTER_PROMPT_AUDIT.md). The entire master prompt is **not** complete; that audit identifies remaining modules and verification limits.

## Database and environment

Use the existing Laravel project. `.env.example` now describes MySQL; the application's actual `.env` and credentials were not changed. Configure your own server, port, database, credentials, application URL and business timezone. Preserve an existing APP_KEY; generate a key only when a new installation has none. Use HTTPS and secure session cookies in production.

All application database changes remain unapplied. Migration execution during development tests used SQLite `:memory:` only. No supplied SQL data was automatically imported, deleted or overwritten.

The migration chain now supports both an empty schema and adoption of an imported DairyNex schema:

1. Guarded starter migrations create missing framework/authentication tables and canonical fresh users. They skip existing tables.
2. `database/migrations/daatabase_migrations.php` is the single DairyNex business migration. It creates absent business tables, reconciles missing starter identity fields, adds authentication support, customer PIN preferences, historical delivery policy snapshots, explicit customer login links, delivery generation/verification controls and ledger reference uniqueness. Existing duplicate ledger references abort without deleting records.

The five earlier dated DairyNex files have been consolidated into this file. Existing migration-history rows for those names can remain: the consolidated migration checks existing structures before applying changes. Laravel discovers the anonymous migration under the requested filename, so the normal `php artisan migrate` command still works. The three existing starter framework migrations remain separate.

Before applying changes, back up and verify the selected database/schema and migration status. Then an authorized operator can run:

```sh
php artisan migrate
php artisan db:seed --class=ReferenceDataSeeder
```

Reference seeding is idempotent and imports no business records or default login. Reference ID conflicts fail for explicit reconciliation. Old manager/accountant IDs and relationships are preserved; those roles cannot authenticate. Schema baselines adopt imported tables rather than verifying/repairing every arbitrary preexisting schema drift. The supplied reference schema and old Laravel starter schema are the supported reconciliation cases.

Migration down methods intentionally preserve adopted business/authentication data and security/history controls. Removal requires a separately reviewed migration/backup process. `DBmigration.sql` is an append-only review/manual reference log, not a standalone ordered import script: its sections were appended as work occurred. Do not replay changes already applied by Laravel. Execute ALTER statements only when the field/index is absent. The Laravel migration chain is the authoritative execution order.

## Administrator provisioning

Fresh installs seed no accounts. An authorized operator can create a platform Super Admin using:

```sh
php artisan dairynex:create-admin
```

To create a Client Admin for an existing active dairy, use `php artisan dairynex:create-admin --client=DAIRY_ID`. The command prompts for name/mobile/email and privately prompts for a confirmed password of at least 12 characters. No credentials are accepted as command arguments or stored in output/audit metadata. New dairy/plan lifecycle management is still a required next phase; imported dairies can be used now.

To explicitly provision an existing legacy account, retaining its ID and relationships:

```sh
php artisan dairynex:provision USER_ID --role=client_admin
```

Choose a verified existing ID and supported role (super_admin/client_admin/delivery_boy/customer). No account was automatically promoted. Tenant accounts require an existing active dairy.

Sign in at `/login` with mobile/password. Supply Dairy ID when a mobile belongs to more than one dairy. Dairy ID selects a credential candidate; authorization always comes from the authenticated user's record. Inactive accounts/dairies and unsupported roles are denied. Password changes invalidate authenticated sessions on the next protected request.

## Customer login and PIN policy

An authorized Client Admin links an existing customer login or creates one from the Customer Details page. Linking checks role, tenant and unique ownership; mobile/email is never used to infer customer access automatically. Existing login links cannot be silently replaced by that endpoint.

Customer PIN verification defaults enabled for new customers, configurable with `DAIRYNEX_DELIVERY_PIN_REQUIRED`. Existing customers default enabled when the column is added. Create/edit, list and details show the setting. New deliveries snapshot the policy; later customer changes never rewrite it. Unknown legacy snapshots remain NULL and block billable completion until a reviewed reconciliation workflow is available.

Only the explicitly linked customer may issue/reissue a delivery PIN. A secure random six-digit PIN is stored as a hash only and returned to that customer in a no-store JSON response. Browser display clears after two minutes. Each reissue invalidates the prior PIN and resets attempts. The generation-time PIN is hashed and discarded; customer issuance replaces it for display. No plaintext PIN is placed in database rows, notifications, URLs, session flashes or audit records.

## Operational workflow

- Client Admin creates a subscription with products, days, morning/evening/both slots and dates. Existing unscheduled customer-form subscriptions retain a compatibility interpretation: daily default quantity in the customer's supply preference.
- Admin assigns each scheduled customer exactly one active route with an active Delivery Boy. Dated routes must match the generated day.
- `/deliveries` generates dates from today through the next 31 days, respects subscription dates/status, ISO weekdays, active products/customers, pauses, approved changes and effective rates. Tenant locking, generation keys and legacy-delivery detection prevent duplicates.
- Customer requests a future permanent quantity change; Client Admin approves/rejects. Approved changes resolve by effective date and refresh pending future planned quantities while retaining rates, PIN policy and completed history. Driver cannot approve changes or directly edit subscriptions.
- Future pause ranges cancel pending generated deliveries in that range without charge. Supply resumes after the end date.
- Assigned Delivery Boy supplies actual quantities and, where required, a valid PIN. Backend uses stored rates and integer-scaled decimal calculations, records verifier/completer and posts one delivery debit. Five failed attempts require customer reissue; failed attempts survive transaction rollback handling. Billable retries are rejected. Skips post no debit or PIN verification. Zero-price products may be delivered without creating a monetary charge.
- Positive credit limits block completion when exceeded; zero means no configured cap. Credit overrides and financial adjustments are not implemented yet.

## Payments, ledger and invoices

`/payments` records authorized receipts, collector and method, with a request UUID and transaction-reference safeguards. Retries do not duplicate payments or ledger credits. Drivers can collect only from customers assigned to them that day. Customers can view only their own records.

`/ledger` calculates opening balance plus ledger debits minus credits. Opening balance cannot be overwritten once ledger activity exists. History records cannot be casually deleted. Authorized adjustment/reversal workflows remain pending.

`/invoices` issues a completed-calendar-month invoice after pending deliveries are finalized. It bills actual quantities and stored historical rates, excludes skips and already billed deliveries, and avoids a second ledger debit. Existing issued monthly invoices are returned on retries. Printing uses the invoice page's Print action.

Payments credit the account immediately but reduce invoice due only through explicit allocation. An existing invoice/payment pair is immutable through the allocation endpoint; retries do not allocate twice. The backend validates remaining receipt funds, invoice due, customer and tenant ownership. Allocation never posts another ledger credit. Late delivery corrections, credit notes, supplemental invoices, discount/tax settings and payment-plan allocations remain pending.

## Notifications and dashboards

`/notifications` lists and marks read only the recipient's own notices. Receipt, invoice and quantity-decision notices are implemented. Additional scheduled events remain pending. No external SMS/WhatsApp provider was assumed.

Admin dashboard now aggregates actual delivery states, collections, ledger balances, recent activity, product sales and payment methods. Delivery/customer portals show only their authorized deliveries. Customer portal navigation includes subscriptions, ledger, invoices and notices. Super Admin still has a limited read-only overview; lifecycle management and full reports remain pending.

## Email resets

Configure a real email transport to deliver password resets. Log mailer reset delivery is blocked so reset tokens are not written into logs. Shared/duplicate emails and unsupported/inactive accounts cannot use email reset; use authorized provisioning instead. Tests use fake notifications. No actual email delivery was verified.

## Verification and remaining work

`php artisan test` uses a forced SQLite memory connection. Tests cover fresh/imported migration paths, authentication/roles/tenancy, customer identity, schedules/pauses/approvals, generation deduplication, actual vs planned quantities, PIN ON/OFF/attempts/ownership, ledger/payment accuracy and retries, invoice calculations/allocation, rates and notification privacy.

MySQL execution/concurrency, rendered desktop/mobile QA and actual email delivery remain unverified. PHP 8.5 produces preexisting PDO::MYSQL_ATTR_SSL_CA deprecation notices in Laravel/framework and database configuration. Vendor files were not edited.

Inventory/stock consumption, walk-in sales, installment plans, Super Admin management, full reporting/settings, comprehensive core-list server pagination, multiple addresses/profiles and financial corrections remain required. See the master audit for the exact partial/pending coverage.

Latest executed checks: 52 tests / 535 assertions / zero failures (preexisting PHP deprecation notices), successful production build, 75 PHP files with no syntax errors, 64 discoverable routes and a clean diff whitespace check. See the audit for remaining acceptance criteria.
