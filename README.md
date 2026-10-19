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

## Push notifications

Push reminders use Firebase Cloud Messaging (FCM) and require the deployed site
to use HTTPS. In Firebase Console, create a Web App, enable Cloud Messaging,
copy its Web App configuration to the `FIREBASE_*` settings in `.env`, and
create a Web Push certificate key pair. Set the certificate's public key as
`FIREBASE_VAPID_KEY`.

For server sending, create a service-account key with permission to send FCM
messages. Keep its JSON private: base64-encode the file and set the result as
`FIREBASE_SERVICE_ACCOUNT_BASE64` in the hosting environment. Do not commit the
JSON key or share it in chat. On Windows PowerShell, encode a local key file
with:

```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("firebase-service-account.json"))
```

Set `FIREBASE_PROJECT_ID` to the Firebase project ID and set `PUSH_CRON_SECRET`
to a unique random value of at least 32 characters. Generate a value with:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

After changing environment values, clear Laravel's cached configuration if
your deployment uses it. Apply the push tables and build frontend assets when
deploying:

```sh
php artisan migrate --path=database/migrations/2026_10_19_000001_create_push_notification_tables.php
npm run build
```

Users can opt in to device notifications from the bell menu after signing in.
The app sends reminders at 08:00 Asia/Jakarta on the due date, only for
unfinished main tasks. Configure the hosting cron feature to run
`php artisan notifications:send-due-task-pushes` daily at 08:00. If InfinityFree
does not provide scheduled PHP commands, use an external cron service that can
send an HTTPS POST to `/notifications/cron/due-tasks` with an `Authorization`
header using `Bearer` followed by the value configured in `PUSH_CRON_SECRET`.
Keep this endpoint secret and do
not put the token in a public page or URL. The scheduled sender records
successful deliveries so repeating a cron request will not send the same task
reminder twice to the same device.

Push delivery also requires each user to grant browser permission on each
device. iPhone users need iOS 16.4 or newer and must add the site to the Home
Screen before enabling web notifications.

Sessions expire after 15 minutes without a request, and the browser session
cookie expires when the browser is closed. Apply the remember-token activity
migration so the “Ingat saya” option follows the same 15-minute idle timeout:

```sh
php artisan migrate --path=database/migrations/2026_10_17_000001_add_last_used_at_to_remember_tokens.php
```

The old daily-quest toggle endpoint has no corresponding tables in the active
database (`daily_quest` and `achievements` are absent), so it is not exposed as
a working Laravel feature without its intended schema.
