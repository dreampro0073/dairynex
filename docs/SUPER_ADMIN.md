# DairyNex Super Admin

The Super Admin console opens at `/platform` after login. It uses the existing prototype layout, styles, Laravel session authentication, role IDs, `clients`, `users`, `plans`, `client_subscriptions` and `audit_logs`. All displayed counts, subscriptions, invoices, tickets and activities come from database queries. No production data or default accounts are seeded.

## Running against the existing database

This checkout had no `.env`, configured database file or cached runtime configuration. `php artisan migrate:status` could not connect to the default missing `database/database.sqlite`. The existing/live database was therefore not migrated or modified. The prototype was downloaded and inspected before implementation.

Point the existing runtime configuration at the intended database, retaining its APP_KEY and other settings. Review the pending migrations and take the normal database backup, then run:

```sh
php artisan migrate:status
php artisan migrate
npm run build
```

`database/migrations/daatabase_super_admin.php` runs after the existing consolidated business migration. It adds password-change controls, agreed subscription prices, SaaS invoices, support tickets and platform settings. It adopts existing structures and preserves rows. Its rollback does not remove history. Running migrations again is safe. Do not replay the SQL reference log after Laravel has applied the changes.

Use an existing supported Super Admin account. If none exists, the existing `php artisan dairynex:create-admin` command prompts for credentials privately. No existing user is automatically promoted. Serve Laravel's `public` directory or use the existing configured application server.

## Features and routes

| Section | Route | Implemented behavior |
| --- | --- | --- |
| Clients / dashboard | `/platform`, `/platform/clients` | Actual active dairies, customers, MRR, overdue clients, onboarding totals, recent audit activity; search by dairy/location/contact, status/plan filters and pagination |
| Dairy management | `/platform/clients/create`, `/{id}`, `/{id}/edit` | Create/edit/view business information; activate/deactivate; customer/driver counts; manage multiple Client Admin accounts; subscription history |
| Login details | Client listing and details buttons | Dairy, account names, email/mobile and dairy-specific login URL; explicit temporary credential generation |
| Subscription & Billing | `/platform/billing` | SaaS invoice snapshots, pending/paid/overdue filters, period/client filters, receipt references, totals and CSV export |
| Plans | `/platform/plans` | Create/edit/search/filter/paginate, price, description/features, customer/user capacities and activation; delete unused plans; preserve referenced plans |
| Client Databases | `/platform/databases` | Real connection probe, database engine, shared tenant architecture, searchable tenant cards and customer/driver counts |
| Usage Statistics | `/platform/usage` | Daily active dairies, deliveries, actual PIN verifications, open support tickets; date-filtered delivery completion report and CSV export |
| Support | `/platform/support` | Create/view/update tickets, priority and status filters, search, open/in-progress/closed workflow; resolution required on closure |
| Settings | `/platform/settings` | Persistent platform name, support email, INR, Asia/Kolkata and notice; notice shared with Client Admin dashboard; accurate channel configuration status |

The existing prototype sidebar, cards, plan cards, tables, forms and responsive drawer are reused. Prototype demonstration role-switching links are excluded from the authenticated console. Tenant roles cannot access platform routes. Client Admin, delivery, product, customer, payment and invoice workflows continue using their existing routes and authorization.

## Temporary testing credentials

1. Open **View Login Details** on the dairy listing or details page.
2. Select an active Client Admin and **Generate / Reset Temporary Password**. Resetting replaces their password and invalidates existing authenticated sessions on their next request.
3. Copy the password from the one-time display. The response is JSON with `Cache-Control: no-store, private`; the password is held in component memory, never in Inertia props, browser history, flash data, audit metadata or database plaintext. Display clears after two minutes, when hidden, when the tab/page is hidden or when the modal closes.
4. Give the tester the displayed login URL and email/mobile. Dairy ID disambiguates duplicate identities and does not grant tenant authorization.
5. On sign-in, the tester must replace the password with a different confirmed password of at least 12 characters before any tenant module is accessible. Temporary credentials expire after 24 hours.

Only securely hashed passwords are stored through the existing User model's hashed cast. Existing passwords and hashes are never returned. Resets are role protected, account/dairy scoped, rate limited and audited with actor, target, dairy, IP and timestamp. Resetting also revokes existing email-reset tokens. New Client Admins receive an undisclosed random credential until a Super Admin explicitly generates a temporary one.

## Billing and subscription policy

- Assignments start today; renewals start after the current period, or today if the last period expired. Upgrade/downgrade replaces the current subscription immediately. A scheduled renewal prevents another overlapping change until explicitly expired.
- Prices and invoice names/periods/amounts are snapshots. Editing a plan does not reprice prior subscriptions/invoices. Legacy subscriptions receive the current plan price as their initial snapshot; no historical invoices are fabricated.
- Each change creates one invoice for the full selected period, from 1 to 36 months. Upgrades/downgrades do not prorate or credit prior invoices. The UI explains this before submission. Zero-price invoices are settled as `NO_CHARGE`.
- Payment recording confirms an already received full payment and preserves the first receipt on retries. There is no payment gateway or partial SaaS payment flow. Customer payments, invoices and ledger records are separate and untouched.
- Plan capacities are checked at assignment/renewal/change. These controls do not rewrite existing tenant provisioning workflows. Referenced plans can be deactivated but cannot be deleted.
- Date-ended subscriptions stop appearing as current without relying on a scheduler. Explicit expiry also ends scheduled renewals. Business activation is managed independently; existing tenant login permissions are preserved.
- Exports stream real filtered data and escape spreadsheet formula prefixes.

## Infrastructure boundaries

The application uses a shared database scoped by `client_id`. There is no configured per-dairy database, disk-usage collector or backup provider in the codebase, so the monitor does not invent sizes or successful backup times. Backup scheduling/restoration and per-tenant storage telemetry require an actual infrastructure integration.

SMS, WhatsApp and automatic onboarding email integrations were absent. Their controls show their actual unavailable status and remain disabled; the backend rejects attempts to falsely enable them. Existing configured email password resets remain supported. Currency/timezone controls reflect the supported INR/Asia/Kolkata platform defaults; the host's existing application timezone configuration is retained for business timestamps.

## Validation

- Full feature/unit suite: 75 tests, including 22 Super Admin tests; 822 assertions; no failures. The installed PHP 8.5 runtime reports pre-existing `PDO::MYSQL_ATTR_SSL_CA` deprecations in framework/database configuration.
- `npm run build`: successful production build.
- PHP syntax: 80 files passed. Diff whitespace checks: passed.
- Headless Chrome with a separate temporary profile and SQLite QA database: all console pages rendered, desktop 1440px and mobile 390px layouts, drawer navigation, credential modal/generation/hiding, actual CSRF-protected temporary login, forced password change and existing Client Admin dashboard; no JavaScript exceptions. QA data was confined to `/private/tmp`, never production.
- Live MySQL execution/concurrency and live deployment remain unverified because no runtime database configuration was available. Native browser clipboard interaction was not automated.

## Changed files

Backend: `app/Http/Controllers/Platform/{ClientController,ConsoleController}.php`, `app/Http/Controllers/Auth/{SessionController,ChangePasswordController}.php`, `app/Http/Controllers/PortalController.php`, `app/Http/Middleware/{EnsureAccountAccess,HandleInertiaRequests}.php`, `app/Models/{Client,User,Plan,ClientSubscription}.php`, `app/Services/PlatformSubscriptionService.php`, `app/Support/{PlatformAudit,PlatformSettings}.php`, `routes/{web,auth,platform}.php`.

Schema/reference: `database/migrations/daatabase_super_admin.php`, `DBmigration.sql`.

Frontend: `resources/js/Layouts/{PlatformLayout,AdminLayout}.vue`, `resources/js/Components/{PlatformStats,LoginDetails}.vue`, `resources/js/Pages/Auth/{Login,ChangePassword}.vue`, `resources/js/Pages/Platform/Clients/{Index,Form,Show}.vue`, `resources/js/Pages/Platform/{Plans,Billing,Databases,Usage,Support,Settings}.vue`, `resources/css/platform.css`.

Tests/documentation: `tests/BusinessTestCase.php`, `tests/Feature/SuperAdminTest.php`, `docs/SETUP.md`, this file. Built assets are generated under the existing ignored `public/build` directory.
