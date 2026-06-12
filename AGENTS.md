# AGENTS.md

## Project overview

Leanplate is a levelsio-style PHP micro-stack template: plain functions,
SQLite, no framework, no build step. One-person SaaS in a handful of files.

## Development

```bash
cp src/config.example.php src/config.php
cd public && php -S 127.0.0.1:8000
```

PHP built-in server serves from `public/`. Sources (`src/`, `data/`,
`logs/`) are never directly reachable.

- Run `phpstan analyse` before committing; keep it clean at level 5 (config in `phpstan.neon`).

## Conventions

- Plain functions, no classes. No autoloader.
- `snake_case` for functions and DB columns.
- Every `public/*` file starts with: `require __DIR__ . '/../src/bootstrap.php';` (always first line).
- Prepare statements with `?` placeholders. Never interpolate user input into SQL.
- All dynamic output escaped via `htmlspecialchars()`. No exceptions.
- POST redirect pattern: validate, write via prepared statement, `header('Location: ...')`, `exit`.

## Architecture

| Layer     | Files                                                                       |
| --------- | --------------------------------------------------------------------------- |
| Config    | `src/config.php` (gitignored), `src/config.example.php`                     |
| Bootstrap | `src/bootstrap.php` — loads config, ensures `data/`/`logs/`, hardens        |
|           | session, requires `db.php`, `mail.php`, `auth.php`                          |
| DB        | `src/db.php` — shared PDO (SQLite, WAL), `db_migrate()` runs schema on      |
|           | every connection                                                            |
| Auth      | `src/auth.php` — magic links, Google OAuth, `find_or_create_user()`,        |
|           | `login_user()`, `require_login()`                                           |
| Mail      | `src/mail.php` — pluggable transport (`log` writes to `logs/mail.log`)      |
| Payments  | `src/stripe.php` — Stripe Checkout                                          |
| Pages     | `public/*`                                                                  |

Config degrades gracefully when keys are blank (mail goes to log, buttons
hide).

## Adding a feature

1. Add/change tables in `db_migrate()` (`src/db.php`). Use `CREATE TABLE IF NOT EXISTS`. For new columns on existing tables, guard with a check or wrap in error suppression for duplicate-column errors.
2. Add function(s) to the relevant file in `src/` and require it from
   `bootstrap.php` if needed.
3. Add/edit a page in `public/`, starting with the bootstrap require.
4. Validate input, use prepared statements, escape all output, redirect after
   POST.

## Config keys

| Key                                     | Effect when blank                    |
| --------------------------------------- | ------------------------------------ |
| `mail_transport` (or Resend key)       | Writes mail to `logs/mail.log`       |
| `stripe_secret_key` / `stripe_price_id` | Hides upgrade button                 |
| `google_client_id` / `google_client_secret` | Hides Google button              |

Google OAuth endpoints can be overridden via `GOOGLE_AUTH_ENDPOINT`,
`GOOGLE_TOKEN_ENDPOINT`, `GOOGLE_USERINFO_ENDPOINT` env vars.
