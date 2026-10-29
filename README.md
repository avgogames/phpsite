# Bolt & Nut Hardware

A small online hardware store (screws, bolts, anchors, hinges, hand tools) built with plain PHP 8.2+ and PostgreSQL 16. No framework and no runtime dependencies.

**Features:** catalog with categories, full-text search, sorting and pagination · product pages with live stock · session cart · sign-in checkout with transactional, oversell-proof order placement · customer order history · admin dashboard, product management and order status workflow · CSRF, CSP, login throttling and hardened sessions.

The design is described in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) (system architecture, data model, file structure, UI architecture, security, operations).

## Quick start (local PHP + PostgreSQL)

Requirements: PHP 8.2+ with `pdo_pgsql` and `mbstring`, PostgreSQL 14+.

```bash
# 1. Database
createuser store --pwprompt          # use "store" to match .env.example
createdb hardware_store --owner store

# 2. Config
cp .env.example .env                 # edit DB_* and ADMIN_* as needed

# 3. Schema, demo catalog and the first admin
php bin/migrate.php
php bin/seed.php

# 4. Run
php -S localhost:8000 -t public public/index.php
```

Open http://localhost:8000. Sign in to the admin at http://localhost:8000/admin with `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env` (default `admin@example.com` / `change-me-now`; change it).

`composer install` is optional: it only adds PHPUnit. Without it the app uses a built-in autoloader.

## Quick start (Docker)

```bash
docker compose up -d --build
docker compose exec app php bin/migrate.php
docker compose exec app php bin/seed.php
```

Open http://localhost:8080.

## Common tasks

| Task | Command |
| --- | --- |
| Apply new migrations | `php bin/migrate.php` |
| List migration status | `php bin/migrate.php --status` |
| Create or promote an admin | `php bin/create-admin.php you@example.com "Your Name"` (prompts for the password) |
| Unit + integration tests | `createdb hardware_store_test --owner store && composer install && vendor/bin/phpunit` |
| End-to-end smoke test | start the server, then `tests/smoke.sh http://localhost:8000` |

Integration tests rebuild the schema of the database named in `phpunit.xml` (`hardware_store_test`) and refuse to run against a database whose name does not end in `_test`.

## Adding a migration

Create the next numbered file, for example `database/migrations/002_add_product_weight.sql`, with plain SQL. `bin/migrate.php` runs each pending file in its own transaction and records it in `schema_migrations`. Never edit a migration that has already run in production; add a new one.

## Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…` (enables `Secure` cookies and HSTS).
- Point the web server's document root at `public/` only. `.htaccess` handles Apache; for nginx use `try_files $uri /index.php?$query_string;`.
- Use a dedicated database role with a strong password, and run `php bin/migrate.php` on every deploy.
- Create the admin with `bin/create-admin.php` instead of leaving `ADMIN_PASSWORD` in `.env`.
- Make `storage/logs` writable by the web server user and ship `app.log` to your log system.
- Back up PostgreSQL (`pg_dump` or managed snapshots).

## Project layout

```
config/routes.php     URL → controller map
public/               document root: index.php front controller, CSS, JS
src/Core/             router, request/response, views, session, CSRF, validation, DB
src/Controllers/      storefront, cart, checkout, auth, account, Admin/
src/Services/         Cart, OrderService, Auth, Money (business rules)
src/Repositories/     all SQL
templates/            layouts, partials and pages
database/             migrations/ and seeds/
bin/                  migrate, seed, create-admin
tests/                PHPUnit (Unit, Integration) and smoke.sh
```
