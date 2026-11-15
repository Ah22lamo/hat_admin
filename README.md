# HAT Admin — B2B Catalog & Quotation Platform

Back-office and API for **HAT Trading**, a Yemeni wholesaler (est. 2015) supplying toys, school supplies, bags, swimwear and rain gear.

The platform is built around a **quote-request workflow** rather than direct online checkout: customers browse an availability-only catalog, submit a multi-item quote request, and the HAT team prices and converts it into an order.

> **Project status: functional prototype — not production-ready.**
> The catalog, admin panel and import pipeline are substantially complete. Core business tables, role seeding, API authentication and the quote-request admin workflow are incomplete.

## Known flaws at a glance

This project is presented as an unfinished prototype, not a finished product. The main flaws are:

1. **The database is not reproducible from code.** Migrations are missing for `customers`, `quote_requests`, `orders`, `payments` and `audit_logs`; the schema exists only in a SQL dump. `php artisan migrate` cannot build a working application.
2. **The admin panel is not usable on a clean install.** There is no seeder for permissions or role assignments, so every authorisation gate returns `false` and the panel is effectively locked.
3. **The public API is not secured.** Orders and quote requests can be read by sequential ID without authentication or ownership checks, and order totals are accepted from the client.
4. **The core business workflow is incomplete.** Customers can submit quote requests through the API, but staff have no admin screen to triage, price, approve or convert them into orders.
5. **The import subsystem is duplicated and synchronous.** Four overlapping implementations exist, and large imports run inside the HTTP request with no queueing, progress or retry handling.

**What should have been done from the start:** migrations as the single source of truth, seeders for roles and permissions, authentication and ownership checks in the first API contract, one complete vertical slice (submission → approval → order) before widening scope, and a single queued import pipeline.

The full breakdown, including lower-severity issues, is in [Known limitations](#known-limitations). The corrective plan is in [Roadmap](#roadmap), and the retrospective is in [Engineering retrospective](#engineering-retrospective).

---

## Table of contents

- [Known flaws at a glance](#known-flaws-at-a-glance)
- [Overview](#overview)
- [Scope at a glance](#scope-at-a-glance)
- [Features](#features)
- [Tech stack](#tech-stack)
- [Architecture](#architecture)
- [Data model](#data-model)
- [API v1](#api-v1)
- [Getting started](#getting-started)
- [Roles and permissions](#roles-and-permissions)
- [Testing](#testing)
- [Known limitations](#known-limitations)
- [Roadmap](#roadmap)
- [Engineering retrospective](#engineering-retrospective)
- [Repository layout](#repository-layout)
- [Security notes](#security-notes)
- [License](#license)

---

## Overview

HAT Trading sells wholesale quantities to retailers. Publishing prices publicly is not desirable, so the application separates **catalog discovery** from **commercial negotiation**:

1. Staff maintain products, categories, attributes and images in an Arabic-first Filament admin panel.
2. Products are imported in bulk from supplier spreadsheets, including embedded images.
3. Customers browse active, available products through the public API (consumed by the Flutter app in `HAT-FlutterApp`).
4. A customer submits a quote request with contact details and requested quantities.
5. Staff review the request, agree a price, and create an order.

The backend exposes a versioned JSON API (`/api/v1`) and a Filament panel at `/admin`.

**Companion repository:** [hat_customer_app](https://github.com/Ah22lamo/hat_customer_app) — the Flutter customer client that consumes this API.

## Scope at a glance

| Area | State |
|---|---|
| Product / category / attribute management | ✅ Implemented |
| Product image management | ✅ Implemented |
| Excel import with preview and audit trail | ✅ Implemented (synchronous) |
| Admin dashboard and widgets | ✅ Implemented |
| Product reporting + PDF export | ✅ Implemented |
| Public catalog API | ✅ Implemented |
| Quote-request submission API | ✅ Implemented |
| Quote-request review UI | ❌ Not implemented |
| Order / payment management | ⚠️ Partial (orders only) |
| API authentication / customer accounts | ❌ Not implemented |
| Reproducible database setup from migrations | ❌ Broken (see limitations) |
| Automated test coverage | ❌ Effectively none |

## Features

### Catalog management
- Hierarchical categories (`parent_id`) with sort order, active flag and optional category codes.
- Products identified by a business `item_no` (unique), plus optional SKU, barcode and description.
- Availability (`available` / `unavailable`) and active/inactive states, separate from soft deletion.
- Reusable attributes and attribute values (e.g. colour, size, material) linked to products through a pivot table.
- Product images with sort order, primary-image flag, alt text and source metadata.

### Excel product import
A purpose-built import engine (`app/Services/ExcelImport`) rather than a thin wrapper around a spreadsheet package:

- **Analyze first, write later.** The file is parsed into a preview; nothing is persisted until an administrator confirms.
- **Multi-sheet support.** Every worksheet is analysed independently.
- **Header auto-detection.** Scores candidate header rows instead of assuming row 1.
- **Alias-based column mapping.** Recognises English and Arabic headers (`item`, `item no`, `pcs/ctn`, `اسم المنتج`, …).
- **Normalisation and validation.** Values are cleaned and validated per row, with row-level warnings and errors.
- **Duplicate detection.** Detects duplicates inside the file and matches existing products by `item_no`.
- **Create-or-update semantics.** Existing products are updated without overwriting optional fields with empty values.
- **Embedded image extraction.** Images attached to worksheet cells are extracted and linked to products.
- **Human-in-the-loop review.** Staff can complete a missing product name or category before committing.
- **Audit trail.** Every run creates an `import_batches` record, with per-row `import_batch_items` and statuses.

### Admin panel
- Filament 4 panel with full RTL Arabic UI and a custom teal brand theme.
- CRUD resources for products, categories, attributes, attribute values, orders and users.
- Dashboard widgets: product totals, active/inactive split, data-health percentages (category/SKU/images), products needing attention, order statistics, order status and trend charts, category and availability charts, and recent import activity.
- Quick actions for creating a product and opening reports.
- Search, filters, sorting, bulk delete and copyable identifiers in tables.
- Permission gates wired into every resource, page and table action.

### Reporting
- Filterable product report by availability, active status and category.
- Summary counters and an A4 landscape PDF export (DomPDF).
- Report access restricted by the `reports.view` permission.

### Public API
- Active/available-only catalog with category filtering, search and pagination.
- Product details including category, images and attribute values.
- Quote-request creation with customer upsert by phone, product availability validation and atomic writes.
- Order creation from an approved quote request, with row locking and reference generation.
- Consistent `{ success, message?, data }` response envelope.

## Tech stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Admin panel | Filament 4 |
| Authentication | Laravel Sanctum (installed, not yet used by the API) |
| Authorisation | Spatie Laravel Permission 6 |
| Database | MySQL 8 (SQLite configured in `.env.example`) |
| Spreadsheets | Maatwebsite Excel 3 + PhpSpreadsheet |
| PDF | barryvdh/laravel-dompdf 3 |
| Frontend build | Vite 7, Tailwind CSS 4 |
| Testing | PHPUnit 11 (no meaningful coverage) |
| Code style | Laravel Pint |

## Architecture

```
HTTP ──┬── /admin ──► Filament resources/pages/widgets ──► Eloquent models
       │                     │
       │                     └── Excel import engine (analyze → preview → commit)
       │
       └── /api/v1 ──► API controllers ──► Form Requests ──► Eloquent models

Shared: service classes, Eloquent relationships, soft deletes, DB transactions
```

Design choices worth noting:

- **Thin controllers, explicit services.** Import logic is split into focused classes (header detection, column mapping, normalisation, validation, image extraction, database import) instead of one large class.
- **Preview/commit separation** prevents partially imported catalogues and gives staff a chance to correct data.
- **Transactions and row locking** protect quote-to-order conversion from race conditions.
- **Soft deletes** retain history for products and customers.

## Data model

| Table | Purpose | Migration |
|---|---|---|
| `users` | Admin/staff accounts (Spatie roles) | ✅ |
| `categories` | Hierarchical product categories | ✅ |
| `products` | Catalog items, keyed by `item_no` | ✅ |
| `product_images` | Ordered product images | ✅ |
| `attributes`, `attribute_values`, `product_attribute_values` | Product options | ✅ |
| `import_batches`, `import_batch_items` | Import audit trail | ✅ |
| `customers` | Quote/order customers | ❌ Missing |
| `quote_requests` | Submitted quote requests (JSON items) | ❌ Missing |
| `orders` | Commercial orders | ❌ Missing |
| `payments` | Payment records and verification | ❌ Missing |
| `audit_logs` | Intended audit trail | ❌ Missing (table only in SQL dump) |
| `permissions`, `roles`, `model_has_*`, `role_has_permissions` | Spatie RBAC tables | ❌ Seeded via SQL dump only |

The live schema for the missing tables exists in `hat_ecommerce_backup.sql`. It is **not** represented by migrations — see [Known limitations](#known-limitations).

## API v1

Base URL: `/api/v1`. All endpoints currently return JSON and are public.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/categories` | Active root categories with active children |
| `GET` | `/categories/{category}` | Single category with children |
| `GET` | `/products` | Active + available products; supports `category_id`, `search`, `per_page` (max 100) |
| `GET` | `/products/{product}` | Product with category, images and attribute values |
| `POST` | `/quote-requests` | Create a quote request |
| `GET` | `/quote-requests/{quoteRequest}` | Quote request with customer, assignee and order |
| `POST` | `/orders` | Create an order (optionally from an approved quote) |
| `GET` | `/orders/{order}` | Order with customer, quote and payments |

Example quote-request payload:

```json
{
  "customer": {
    "name": "Example Retail Store",
    "phone": "7XXXXXXXX",
    "email": "buyer@example.com",
    "customer_type": "wholesale",
    "address": "Sana'a",
    "notes": "Please confirm carton quantities."
  },
  "requested_items": [
    { "product_id": 12, "quantity": 24 },
    { "product_id": 18, "quantity": 6 }
  ],
  "notes": "Delivery before the school term."
}
```

## Getting started

### Requirements

- PHP 8.2+ with `pdo_mysql`, `zip`, `gd`/`imagick` and the usual Laravel extensions
- Composer 2
- MySQL 8
- Node.js 20+ and npm

### Installation

```bash
git clone <repository-url> HAT-admin
cd HAT-admin

composer install
cp .env.example .env
php artisan key:generate

npm install
npm run build
```

Configure the database in `.env` (the checked-in `.env.example` defaults to SQLite; the application is developed against MySQL):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hat_ecommerce
DB_USERNAME=root
DB_PASSWORD=
```

### Database setup (current, temporary)

`php artisan migrate` **will not produce a working schema**, because several core tables and the RBAC seed data exist only in the SQL dump.

```bash
mysql -u root -p -e "CREATE DATABASE hat_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p hat_ecommerce < hat_ecommerce_backup.sql
```

Then start the application:

```bash
php artisan serve
# Admin panel: http://127.0.0.1:8000/admin
# API:         http://127.0.0.1:8000/api/v1
```

> The SQL dump contains schema and role/permission records but no product data. Import a supplier spreadsheet through **Products → Import Products** to populate the catalog.

### Development

```bash
composer dev
# Runs the PHP server, queue listener, log tail and Vite together
```

## Roles and permissions

The SQL dump defines six roles: **Super Admin**, **Product Manager**, **Warehouse Staff**, **Customer Service**, **Accountant**, **Marketing**.

Permissions are checked throughout the panel (`products.*`, `categories.*`, `attributes.*`, `orders.*`, `users.*`, `reports.view`), but there is **no seeder or migration that creates the permissions or assigns them to roles**. On a fresh installation without the dump, every gate returns `false` and the admin panel is effectively read-only/inaccessible. This is a release blocker, not a design choice.

## Testing

```bash
composer test
# or
php artisan test
```

The suite currently contains only the default Laravel example tests. There is no coverage of the import engine, API contract, order state transitions or permission gates. Adding those tests is the first item in the roadmap.

## Known limitations

These are deliberate notes for reviewers and for the next phase of work — not an exhaustive bug list.

### Critical

| # | Area | Issue | Impact |
|---|---|---|---|
| 1 | Database | No migrations for `customers`, `quote_requests`, `orders`, `payments` or `audit_logs`. `add_currency_to_orders_table` also assumes `orders` already exists. | Fresh installs fail; the SQL dump is the only source of truth, so schema changes are not reviewable or reproducible. |
| 2 | Authorisation | No permission/role seeder. The dump creates roles but no usable permission set. | All Filament gates fail on a clean install; RBAC cannot be provisioned in code. |
| 3 | API security | Public API has no authentication, ownership checks or customer verification. `GET /orders/{id}` and `GET /quote-requests/{id}` expose data by sequential ID. | Anyone can read customer and order data. Sanctum is installed but unused. |
| 4 | Business logic | `POST /orders` trusts a client-supplied `total_amount` and `currency`. | Order totals can be forged; pricing is not derived from an approved quote. |
| 5 | Workflow | No Filament resource for quote requests (or payments/customers). | Incoming requests cannot be triaged, assigned, priced or converted inside the admin panel. |

### Significant

| # | Area | Issue |
|---|---|---|
| 6 | Codebase | Four overlapping import implementations (`Services\ExcelImport`, `Services\Imports`, `Imports\ProductsImport`, `Services\ExcelProductImportService`), plus a malformed nested path `app/Services/Imports/app/Services/Imports/ExcelRowNormalizer.php`. Dead code increases maintenance risk. |
| 7 | Import performance | Imports run synchronously inside the Livewire request; no queue job, chunking or progress reporting. Large files risk request timeouts and memory exhaustion. |
| 8 | Observability | Diagnostic `\Log::info` calls remain in `ExcelAnalyzer`; no structured logging, monitoring or audit observer. |
| 9 | Configuration | `.env` and a full SQL dump are committed. The dump includes customer/order data structures and should never be in a public repository. Credentials should be rotated. |
| 10 | Search | Product search uses leading-wildcard `LIKE`, so indexes cannot be used; no full-text or relevance ranking. |
| 11 | Localisation | Arabic strings are hard-coded across resources and views; no `lang/` files. Adding English or another language requires code edits. |
| 12 | Reporting | Reports cover products only; no date-range order/revenue reporting, CSV/Excel export, or scheduled reports. PDF is generated synchronously. |
| 13 | Quality | No CI pipeline, no static analysis beyond Pint, no factories/seed data for products, and no coverage thresholds. |
| 14 | Orders | Order statuses are edited manually; no transition validation, stock decrement, cancellation rules or payment reconciliation workflow. |

## Roadmap

Prioritised work required to make this production-ready.

### P0 — correctness and security

- [ ] Write migrations for `customers`, `quote_requests`, `orders`, `payments` and `audit_logs`; verify `migrate:fresh --seed` on an empty database.
- [ ] Add `RolePermissionSeeder` and an admin-user seeder; document the permission matrix.
- [ ] Protect the API with Sanctum (customer tokens or phone OTP), policies/ownership checks, and per-endpoint rate limiting.
- [ ] Compute order totals server-side from the approved quote; reject client-supplied amounts.
- [ ] Add Filament resources for quote requests, customers and payments; implement status transitions (assign → review → approve/reject → convert).
- [ ] Remove `.env` and the SQL dump from version control; rotate credentials.

### P1 — reliability and maintainability

- [ ] Consolidate the four import implementations into the `Services\ExcelImport` engine; delete dead code.
- [ ] Move imports to queued jobs with chunking, progress and retry/rollback semantics.
- [ ] Add feature tests for import analysis/commit, API validation, quote-to-order conversion and permission gates; add factories and seeders.
- [ ] Introduce audit logging via model observers; remove diagnostic logging.
- [ ] Add CI (Pint, PHPStan/Larastan, PHPUnit) and a staging deployment.
- [ ] Move UI strings to `lang/ar` and `lang/en` files.

### P2 — product maturity

- [ ] Order/revenue reporting with date filters and CSV/Excel export.
- [ ] Full-text product search and indexed filtering.
- [ ] Stock and availability rules tied to quote approval.
- [ ] Backup, monitoring and error-tracking strategy.
- [ ] API documentation (OpenAPI) and versioning policy.

## Engineering retrospective

What I would do differently on the next iteration:

1. **Treat migrations as the source of truth.** Exporting a production schema to SQL is fast, but it makes the project unreproducible and hides schema drift. Every table should be created by a migration, with seeders for reference data.
2. **Design the API around trust boundaries from day one.** Public reads of orders and quotes were a modelling shortcut; authentication, ownership checks and signed identifiers should be part of the first API contract, not a later hardening pass.
3. **Finish one vertical slice before widening scope.** The catalog and import pipeline are deep, while the quote-request workflow — the actual business value — stops at the API. Shipping submission → triage → approval → order end-to-end would have been more valuable than adding more dashboard widgets.
4. **Choose one import strategy.** The multiple generations of import code reflect changing requirements; committing to the analyzer/importer split earlier would have avoided duplicated logic and a broken file path.
5. **Test the critical path, not the framework.** The default Laravel tests verify nothing about HAT. A handful of feature tests around the import engine and quote-to-order conversion would have caught the highest-risk regressions.
6. **Keep secrets and data dumps out of the repository.** Environment files and database dumps belong in a secret manager or a private backup location, not in Git history.

## Repository layout

```
app/
├── Filament/
│   ├── Pages/            # Excel import, reports
│   ├── Resources/        # Products, categories, attributes, orders, users
│   └── Widgets/          # Dashboard stats and charts
├── Http/
│   ├── Controllers/Api/V1/
│   └── Requests/Api/V1/
├── Imports/              # Legacy Maatwebsite import (not the active engine)
├── Models/
├── Services/
│   ├── ExcelImport/      # Active import engine (analyze → preview → commit)
│   └── Imports/          # Earlier import implementation (dead/duplicated)
└── Providers/Filament/

database/
├── migrations/           # Catalog + import tables (business tables missing)
├── seeders/              # Category seeder only
└── database.sqlite

routes/api.php            # Public v1 API
routes/web.php

resources/views/filament/ # Import and report views / PDF template
hat_ecommerce_backup.sql  # Full schema + RBAC data (temporary setup path)
```

## Security notes

This repository is a prototype and should not be exposed to the public internet in its current state.

- API read endpoints for orders and quote requests have no authentication or ownership checks.
- Phone numbers are used as the customer identity key; there is no verification.
- `.env` and a database dump are committed. Rotate any credentials they contain.
- `APP_DEBUG=true` is the default in `.env.example`; ensure it is disabled in any shared environment.
- No rate limiting, CAPTCHA or abuse protection is configured for public write endpoints.
- File uploads are validated by extension and size, but should also be scanned and stored outside the public web root where appropriate.

## License

No license has been declared. Treat the code as proprietary to HAT Trading until a license is added.
