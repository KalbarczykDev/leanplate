# AGENTS.md

## Project overview

Leanplate is a levelsio-style PHP micro-stack template: plain functions,
SQLite, no framework, no build step. One-person SaaS in a handful of files.

## Development

```bash
cp src/config/config.example.php src/config/config.php
php -S 127.0.0.1:8000 -t public scripts/router.php
```

PHP built-in server serves from `public/`; `scripts/router.php` mimics
nginx clean URLs so extensionless links work locally. Sources (`src/`,
`data/`, `logs/`) are never directly reachable.

- Run `phpstan analyse` before committing; keep it clean at level 5 (config in `phpstan.neon`).

## Conventions

- Plain functions, no classes. No autoloader.
- `snake_case` for functions and DB columns.
- Every page's first line is the bootstrap require, relative to its depth: root pages (`public/index.php`) use `require __DIR__ . '/../src/bootstrap.php';`; grouped pages (`public/auth/login.php`) use `require __DIR__ . '/../../src/bootstrap.php';`.
- Pages are grouped by concern: `public/auth/`, `public/billing/`, `public/app/`. Folders are URL segments; nginx drops the `.php` (clean URLs).
- Prepare statements with `?` placeholders. Never interpolate user input into SQL.
- All dynamic output escaped via `htmlspecialchars()`. No exceptions.
- POST redirect pattern: validate, write via prepared statement, `header('Location: ...')`, `exit`.
- Keep interactive state (search/filter/sort) in the query string so URLs are shareable; read it from `$_GET` and render links/inputs pre-filled.

## Architecture

| Layer     | Files                                                                       |
| --------- | --------------------------------------------------------------------------- |
| Config    | `src/config/config.php` (gitignored), `src/config/config.example.php`        |
| Bootstrap | `src/bootstrap.php` - loads config, ensures `data/`/`logs/`, hardens         |
|           | session, requires `lib/db.php`, `lib/mail.php`, `lib/layout.php`,            |
|           | `app/auth.php`, `app/stripe.php`                                             |
| DB        | `src/lib/db.php` - shared PDO (SQLite, WAL), `db_migrate()` runs schema on   |
|           | every connection                                                            |
| Mail      | `src/lib/mail.php` - pluggable transport (`log` writes to `logs/mail.log`)   |
| Layout    | `src/lib/layout.php` - shared HTML chrome (`layout_header()`/`layout_footer()`) |
| Auth      | `src/app/auth.php` - magic links, Google OAuth, `find_or_create_user()`,     |
|           | `login_user()`, `require_login()`                                           |
| Payments  | `src/app/stripe.php` - Stripe Checkout                                       |
| Pages     | `public/*` (grouped: `auth/`, `billing/`, `app/`)                            |

Config degrades gracefully when keys are blank (mail goes to log, buttons
hide). Google OAuth endpoints are constants in `src/app/auth.php`.

## Adding a feature

1. Add/change tables in `db_migrate()` (`src/lib/db.php`). Use `CREATE TABLE IF NOT EXISTS`. For new columns on existing tables, guard with a check or wrap in error suppression for duplicate-column errors.
2. Add function(s) to the relevant file - reusable infra in `src/lib/`, app domain in `src/app/` - and require it from `bootstrap.php` if needed.
3. Add/edit a page in `public/`, starting with the bootstrap require.
4. Validate input, use prepared statements, escape all output, redirect after
   POST.

## Config keys

| Key                                     | Effect when blank                    |
| --------------------------------------- | ------------------------------------ |
| `mail_transport` (or Resend key)       | Writes mail to `logs/mail.log`       |
| `stripe_secret_key` / `stripe_price_id` | Hides upgrade button                 |
| `google_client_id` / `google_client_secret` | Hides Google button              |
