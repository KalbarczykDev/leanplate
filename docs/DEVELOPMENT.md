# Development

Local setup, conventions, and the loops you will repeat while building.

## Run it locally

```bash
cp src/config/config.example.php src/config/config.php
php -S 127.0.0.1:8000 -t public scripts/router.php
```

PHP's built-in server is enough for development. The web root is `public/`, so `src/`, `data/`, and `logs/` are never directly reachable. The SQLite file and log files are created on first request.

`scripts/router.php` makes the built-in server behave like the nginx config:
clean URLs (`/feedback`, `/auth/login`, `/app/account`,
`/webhooks/stripe`) resolve to their `.php` files and `/sitemap.xml` hits the
generator, so every link works the same locally and in production.

## The bootstrap-first rule

Every page starts with exactly one line - the bootstrap require, relative to the page's depth:

```php
require __DIR__ . '/../src/bootstrap.php';      // root page: public/index.php
require __DIR__ . '/../../src/bootstrap.php';   // grouped page: public/auth/login.php
```

`bootstrap.php` does the setup that every page needs, in order:

1. Defines `config()` for gitignored environment settings and `app_config()`
   for committed product identity.
2. Loads `lib/mail.php` and `lib/runtime.php`.
3. Ensures `data/` and `logs/` exist and configures errors from `env`.
4. Starts a hardened session and registers throttled fatal-error alerts.
5. Loads shared infrastructure: `lib/db.php`, `lib/http.php`, and
   `lib/layout.php`.
6. Loads the app modules: `app/auth.php`, `app/stripe.php`,
   `app/account.php`, and `app/feedback.php`.

If you forget this line, nothing else will be defined. There is no autoloader by design.

## Product and environment config

`src/config/app.php` is committed and supplies the shared product name,
description, tagline, colors, and links used by the HTML layout and dynamic
PWA manifest. Edit it when starting a product from the template.

`src/config/config.example.php` is committed; your real
`src/config/config.php` is gitignored because it contains environment paths,
deployment URLs, email addresses, and service credentials. The app degrades
cleanly when integration keys are blank:

- `mail_transport = log` (or a blank Resend key) writes mail to `logs/mail.log` instead of sending. Magic links still work, you just read them from the file.
- Blank `stripe_secret_key` or `stripe_price_id` hides the upgrade button (`stripe_enabled()` returns false).
- Blank `google_client_id` or `google_client_secret` hides the Google button (`google_enabled()` returns false).

This means a brand-new clone runs end to end with no external services.
Run `php scripts/check-customization.php` before shipping to find important
template defaults that still need replacement.

## db() and prepared statements

`db()` returns a single shared PDO connection with SQLite WAL,
`busy_timeout = 5000`, and foreign keys enabled. On first use it applies any
pending numbered migrations before returning the connection.

Always use prepared statements with `?` placeholders. Never interpolate user input into SQL.

```php
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
```

## Database migrations

Leanplate uses numbered PHP functions in `src/db/migrations/` instead of an
external migration framework. `src/lib/db.php` requires the files, orders
their functions, and tracks the applied version with SQLite's
`PRAGMA user_version`.

`db_migrate()` acquires a write lock with `BEGIN IMMEDIATE`, reads the current
version, runs newer migrations in order, updates `user_version`, and commits.
If any step fails, the whole migration transaction rolls back. The write lock
prevents two concurrent requests from applying the same migration.

Migration 001 creates the current baseline with `CREATE TABLE IF NOT EXISTS`.
That makes first adoption safe for both an empty database and an existing
Leanplate database whose `user_version` is still 0.

To add a schema change:

1. Create `src/db/migrations/db_migrate_002_add_user_timezone.php`:

   ```php
   <?php

   declare(strict_types=1);

   function db_migrate_002_add_user_timezone(PDO $pdo): void
   {
       $pdo->exec(
           "ALTER TABLE users
            ADD COLUMN timezone TEXT NOT NULL DEFAULT 'UTC'"
       );
   }
   ```

2. Require it near the top of `src/lib/db.php`:

   ```php
   require __DIR__ . '/../db/migrations/db_migrate_002_add_user_timezone.php';
   ```

3. Add it to the ordered map:

   ```php
   $migrations = [
       1 => 'db_migrate_001_initial_schema',
       2 => 'db_migrate_002_add_user_timezone',
   ];
   ```

4. Test once against a fresh database and once against a copy of an existing
   database.

Migrations are append-only after release. Never edit, delete, reorder, or
renumber a migration that may have run anywhere. Do not add down migrations;
restore a backup if a production schema deployment must be reversed. For
SQLite changes that cannot use `ALTER TABLE`, create a replacement table,
copy data with explicit column lists, drop the old table, and rename the new
one inside the migration transaction.

Inspect a database's current version with:

```bash
sqlite3 data/app.sqlite 'PRAGMA user_version;'
```

## Adding a protected page

Create a file in `public/app/` (the authed product surface). Annotated example:

```php
<?php
// public/app/settings.php  ->  /app/settings
require __DIR__ . '/../../src/bootstrap.php';   // grouped page: two levels deep

$user = require_login();                          // redirects to /auth/login if not signed in

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read input, validate, then write via a prepared statement
    $name = trim($_POST['name'] ?? '');
    db()->prepare('UPDATE users SET plan = ? WHERE id = ?')
        ->execute([$name, $user['id']]);
    header('Location: /app/settings');            // redirect after POST (clean URL)
    exit;
}

layout_header('Settings');                        // shared HTML chrome
?>
    <h1>Settings</h1>
    <!-- every dynamic value is escaped -->
    <p><?= htmlspecialchars($user['email']) ?></p>
<?php layout_footer(); ?>
```

`layout_header()`/`layout_footer()` live in `src/lib/layout.php` and wrap every HTML page, so individual pages only emit their own content.

## Shareable URL state

Interactive pages (search, filters, sorting, pagination) must keep their state
in the query string so any view is bookmarkable and shareable. Read state from
`$_GET`, render inputs/links pre-filled, and never hold view state only in the
session.

## Escaping

Every dynamic value printed into HTML goes through `htmlspecialchars()`. No exceptions. The default flags in PHP 8.3 already handle quotes and treat the string as UTF-8.

## Auth internals

Two passwordless paths, both ending at `login_user($email)`, which calls `find_or_create_user()` and `session_regenerate_id(true)`. Because both paths key on the email, a user who first used a magic link and later signs in with Google lands on the same account.

Magic links:

- `create_magic_link($email)` generates a 32-byte random token, stores only its SHA-256 hash with a 15-minute expiry, and returns the raw token to put in the email.
- `verify_magic_link($token)` hashes the token, deletes the row (single-use, deleted before the expiry check so it cannot be replayed), then returns the email if it had not expired.

Google OAuth:

- `auth/google.php` handles both ends of the flow. Without `?code` it stores a random `state` in the session and redirects to the auth endpoint; with `?code` it checks `state` with `hash_equals`, exchanges the code, fetches userinfo, and logs in only if `email_verified` is truthy.

## The add-a-feature loop

1. Append a numbered migration in `src/lib/db.php` if the feature changes the
   schema.
2. Add a function to the right file - reusable infrastructure in `src/lib/`
   and feature behavior in `src/app/` - then require a new module from
   `bootstrap.php`.
3. Add or edit a thin endpoint in `public/`: authenticated product pages under
   `app/`, auth flows under `auth/`, browser billing under `billing/`, and
   machine callbacks under `webhooks/`.
4. Validate input, write through prepared statements, escape all output.
5. Click through it locally with mail going to `logs/mail.log`.

## Conventions

- Plain functions, no classes.
- `snake_case` for functions and DB columns.
- One shared PDO via `db()`.
- Redirect after every successful POST, then `exit`.
- Keep comments short and about why, not what.
- No em-dashes in code comments or docs.

## Local email and OAuth

There is no local mail server or mock OAuth setup. In dev, `mail_transport = log` writes every email (including magic links) to `logs/mail.log`; `tail -f` it and click the link. To test Google login, use real Google credentials with `http://127.0.0.1:8000/auth/google` added as an authorized redirect URI, or just test it on the deployed domain.

## Testing billing locally

Use Stripe test mode plus the Stripe CLI (Stripe cannot reach 127.0.0.1, so the CLI forwards webhooks).

1. Secret key: https://dashboard.stripe.com/test/apikeys → copy `sk_test_…` into `stripe_secret_key`.
2. Price (creates the product inline; must be recurring, checkout uses `mode=subscription`):

   ```bash
   stripe prices create -d "product_data[name]=Pro" -d "unit_amount=900" -d "currency=usd" -d "recurring[interval]=month"
   ```

   Copy the `"id": "price_…"` into `stripe_price_id`.
3. Forward webhooks and keep it running:

   ```bash
   stripe listen --forward-to 127.0.0.1:8000/webhooks/stripe
   ```

   It prints a `whsec_…` → `stripe_webhook_secret`.
4. Sign in, click Upgrade to Pro, pay with card `4242 4242 4242 4242` (any future expiry/CVC). The forwarded `checkout.session.completed` flips the plan to `pro`.

`stripe trigger checkout.session.completed` fakes the event without paying, but with a synthetic `client_reference_id`, so the plan of a real local user only flips on a real test checkout.

## Progressive web app

Every rendered page links to `/manifest`, served by `public/manifest.php`.
The endpoint reads product identity from `src/config/app.php`, launches the
installed app at `/app`, and uses the PNGs in `public/assets/icons/`.

`public/assets/js/pwa.js` registers `public/service-worker.js` for the root
scope. The worker caches only same-origin files below `/assets/`. It
deliberately does not cache HTML, auth, account, billing, feedback, health, or
webhook responses. When a cached asset changes, increment `CACHE_NAME` before
deployment so installed clients replace the old cache.

PWA installation works on `127.0.0.1` during development and requires HTTPS
in production. Use the browser's Application tools to inspect the manifest,
service worker, and `app-static-*` cache.
