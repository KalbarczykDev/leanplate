# Development

Local setup, conventions, and the loops you will repeat while building.

## Run it locally

```bash
cp src/config/config.example.php src/config/config.php
cd public && php -S 127.0.0.1:8000
```

PHP's built-in server is enough for development. The web root is `public/`, so `src/`, `data/`, and `logs/` are never directly reachable. The SQLite file and log files are created on first request.

Note: clean URLs (`/auth/login`) are served by nginx in production. The built-in server does not strip `.php`, so locally you reach grouped pages by their file path (`/auth/login.php`).

## The bootstrap-first rule

Every page starts with exactly one line - the bootstrap require, relative to the page's depth:

```php
require __DIR__ . '/../src/bootstrap.php';      // root page: public/index.php
require __DIR__ . '/../../src/bootstrap.php';   // grouped page: public/auth/login.php
```

`bootstrap.php` does the setup that every page needs, in order:

1. Defines `config()` and loads `src/config/config.php` once.
2. Ensures `data/` and `logs/` exist.
3. Sets error handling based on `env` (dev shows errors, prod logs them).
4. Starts a hardened session.
5. Requires `lib/db.php`, `lib/mail.php`, `lib/layout.php`, `app/auth.php`, and `app/stripe.php`.
6. Registers a throttled fatal-error handler that emails `alert_email` at most once every 15 minutes.

If you forget this line, nothing else will be defined. There is no autoloader by design.

## Config and graceful degradation

`src/config/config.example.php` is committed; your real `src/config/config.php` is gitignored. The app degrades cleanly when keys are blank:

- `mail_transport = log` (or a blank Resend key) writes mail to `logs/mail.log` instead of sending. Magic links still work, you just read them from the file.
- Blank `stripe_secret_key` or `stripe_price_id` hides the upgrade button (`stripe_enabled()` returns false).
- Blank `google_client_id` or `google_client_secret` hides the Google button (`google_enabled()` returns false).

This means a brand-new clone runs end to end with no external services.

## db() and prepared statements

`db()` returns a single shared PDO connection (SQLite, WAL, busy_timeout 5000). Call it anywhere; it builds the connection and runs the schema on first use.

Always use prepared statements with `?` placeholders. Never interpolate user input into SQL.

```php
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
```

## Adding a table

Add a `CREATE TABLE IF NOT EXISTS` to `db_migrate()` in `src/lib/db.php`. It runs on every connection, so it is safe to keep all tables there. For a column on an existing table, add a guarded `ALTER TABLE` (check `PRAGMA table_info` first, or wrap it so a duplicate-column error is ignored). There is no migration framework; this is intentional for a project this size.

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

- `auth/google-login.php` stores a random `state` in the session and redirects to the auth endpoint.
- `auth/google-callback.php` checks `state` with `hash_equals`, exchanges the code, fetches userinfo, and logs in only if `email_verified` is truthy.

## The add-a-feature loop

1. Add or change a table in `db_migrate()` if needed.
2. Add a function to the right file - reusable infra in `src/lib/` (`db.php`, `mail.php`, `layout.php`), app domain in `src/app/` (`auth.php`, `stripe.php`), or a new file required from `bootstrap.php`.
3. Add or edit a page in `public/` (grouped under `auth/`, `billing/`, `app/`), starting with the bootstrap require.
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

There is no local mail server or mock OAuth setup. In dev, `mail_transport = log` writes every email (including magic links) to `logs/mail.log`; `tail -f` it and click the link. To test Google login, use real Google credentials with `http://127.0.0.1:8000/auth/google-callback` added as an authorized redirect URI, or just test it on the deployed domain.
