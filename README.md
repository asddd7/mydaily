# MyDaily

MyDaily now runs on Laravel 13 and continues to use the existing MySQL database
and application tables. The Laravel front controller is in `public/index.php`.
All active user-facing templates, shared navigation, and reusable UI fragments
are Blade files under `resources/views`.

## Local setup

1. Use PHP 8.3 or newer with `pdo_mysql`, `mysqli`, and `gd` enabled.
2. Install Node.js and npm, then run `npm install`.
3. Run `composer install`.
4. Copy `.env.example` to `.env`, then set the database connection values for
   the existing `daily` database.
5. Run `php artisan key:generate`.
6. For local development, run `npm run dev` in one terminal and start the
   application with `php artisan serve` in another. For a production build,
   run `npm run build` before serving the application.

The Laravel bootstrap connects to the existing `daily` database; it does not
create, migrate, or delete application tables. Keep a database backup before
future schema changes. Vite compiles frontend assets from `resources/` into
`public/build/`; the favicon and other static files remain in `public/`. The
root Apache rewrite also supports a Laragon document root at the project
directory. Existing accounts and application data remain in place, but users
must sign in again because Laravel uses its own session format.

Initial table migrations are available under `database/migrations` for setting
up a **new, empty database**. They describe the current `daily` schema. Do not
run `php artisan migrate` against an existing `daily` database: its tables
already exist and these initial migrations are not a baseline/import operation.

The authentication, dashboard attendance, tasks (including recurring tasks,
subtasks and Excel import), finance, notes, profile, file manager, calendar
marks, clock settings, and admin database-structure screens use Laravel routes,
Blade views, validation, sessions and database queries.

## Receipt scanning

The finance page uses Tesseract.js to read receipt images in the user's
browser. No Tesseract executable or OCR PHP extension is required on the web
server. Receipt scans accept JPEG, PNG, and WebP images up to 10 MB. The image
is processed locally and is not uploaded to Laravel; Tesseract.js downloads
its worker, OCR core, and Indonesian/English language data from jsDelivr on
first use and caches language data in the browser. The browser needs internet
access for the initial download.

OCR only fills suggestions in the transaction form. Users must review the
fields and submit the form to save a transaction.

Money transactions can optionally include item names and prices. The browser
calculates the grand total, and the server recalculates it before saving the
transaction and its item details. Apply the new item-table migration when
deploying this change:

```sh
php artisan migrate --path=database/migrations/2026_10_14_000001_create_money_plan_items_table.php
```

Task images require this additive migration:

```sh
php artisan migrate --path=database/migrations/2026_10_16_000001_add_image_to_tugas_table.php
```

Sessions expire after 15 minutes without a request, and the browser session
cookie expires when the browser is closed. Apply the remember-token activity
migration so the “Ingat saya” option follows the same 15-minute idle timeout:

```sh
php artisan migrate --path=database/migrations/2026_10_17_000001_add_last_used_at_to_remember_tokens.php
```

The old daily-quest toggle endpoint has no corresponding tables in the active
database (`daily_quest` and `achievements` are absent), so it is not exposed as
a working Laravel feature without its intended schema.
