# MyDaily

MyDaily now runs on Laravel 13 and continues to use the existing MySQL database
and application tables. The Laravel front controller is in `public/index.php`.
All active user-facing templates, shared navigation, and reusable UI fragments
are Blade files under `resources/views`.

## Local setup

1. Use PHP 8.3 or newer with `pdo_mysql`, `mysqli`, and `gd` enabled.
2. Run `composer install`.
3. Copy `.env.example` to `.env`, then set the database connection values for
   the existing `daily` database.
4. Run `php artisan key:generate`.
5. Start the application with `php artisan serve`, or configure Apache/Laragon
   to use the project's `public` directory as its document root.

The Laravel bootstrap connects to the existing `daily` database; it does not
create, migrate, or delete application tables. Keep a database backup before
future schema changes. Static styles and the favicon are served from `public/`;
the root Apache rewrite also supports a Laragon document root at the project
directory. Existing accounts and application data remain in place, but users
must sign in again because Laravel uses its own session format.

The authentication, dashboard attendance, tasks (including recurring tasks,
subtasks and Excel import), finance, notes, profile, file manager, calendar
marks, clock settings, and admin database-structure screens use Laravel routes,
Blade views, validation, sessions and database queries.

The old daily-quest toggle endpoint has no corresponding tables in the active
database (`daily_quest` and `achievements` are absent), so it is not exposed as
a working Laravel feature without its intended schema.
