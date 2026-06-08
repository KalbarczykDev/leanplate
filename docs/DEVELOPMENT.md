# Development

Local setup, conventions, and the loops you will repeat while building.

## Run it locally

```bash
cp src/config.example.php src/config.php
cd public && php -S 127.0.0.1:8000
```

PHP's built-in server is enough for development. The web root is `public/`, so `src/`, `data/`, and `logs/` are never directly reachable. The SQLite file and log files are created on first request.

## The bootstrap-first rule

Every file in `public/` starts with exactly one line:

```php
require __DIR__ . '/../src/bootstrap.php';
```

`bootstrap.php` does the setup that every page needs, in order:

1. Defines `config()` and loads `src/config.php` once.
2. Ensures `data/` and `logs/` exist.
3. Sets error handling based on `env` (dev shows errors, prod logs them).
4. Starts a hardened session.
5. Requires `db.php`, `mail.php`, and `auth.php`.
6. Registers a throttled fatal-error handler that emails `alert_email` at most once every 15 minutes.

If you forget this line, nothing else will be defined. There is no autoloader by design.

## Config and graceful degradation

`config.example.php` is committed; your real `config.php` is gitignored. The app degrades cleanly when keys are blank:

- `mail_transport = log` (or a blank Resend key) writes mail to `logs/mail.log` instead of sending. Magic links still work, you just read them from the file.
- Blank `stripe_secret_key` or `stripe_price_id` hides the upgrade button (`stripe_enabled()` returns false).
- Blank `google_client_id` or `google_client_secret` hides the Google button (`google_enabled()` returns false).

This means a brand-new clone runs end to end with no external services.

The three Google endpoints can be overridden by environment variables (`GOOGLE_AUTH_ENDPOINT`, `GOOGLE_TOKEN_ENDPOINT`, `GOOGLE_USERINFO_ENDPOINT`). That is how you point OAuth at a local mock server without editing config.

## db() and prepared statements

`db()` returns a single shared PDO connection (SQLite, WAL, busy_timeout 5000). Call it anywhere; it builds the connection and runs the schema on first use.

Always use prepared statements with `?` placeholders. Never interpolate user input into SQL.

```php
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
```

## Adding a table

Add a `CREATE TABLE IF NOT EXISTS` to `db_migrate()` in `src/db.php`. It runs on every connection, so it is safe to keep all tables there. For a column on an existing table, add a guarded `ALTER TABLE` (check `PRAGMA table_info` first, or wrap it so a duplicate-column error is ignored). There is no migration framework; this is intentional for a project this size.

## Adding a protected page

Create a file in `public/`. Annotated example:

```php
<?php
// public/settings.php
require __DIR__ . '/../src/bootstrap.php';   // always first

$user = require_login();                      // redirects to /login.php if not signed in

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // read input, validate, then write via a prepared statement
    $name = trim($_POST['name'] ?? '');
    db()->prepare('UPDATE users SET plan = ? WHERE id = ?')
        ->execute([$name, $user['id']]);
    header('Location: /settings.php');        // redirect after POST
    exit;
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><link rel="stylesheet" href="/style.css"></head>
<body>
    <main class="container">
        <h1>Settings</h1>
        <!-- every dynamic value is escaped -->
        <p><?= htmlspecialchars($user['email']) ?></p>
    </main>
</body>
</html>
```

## Escaping

Every dynamic value printed into HTML goes through `htmlspecialchars()`. No exceptions. The default flags in PHP 8.3 already handle quotes and treat the string as UTF-8.

## Auth internals

Two passwordless paths, both ending at `login_user($email)`, which calls `find_or_create_user()` and `session_regenerate_id(true)`. Because both paths key on the email, a user who first used a magic link and later signs in with Google lands on the same account.

Magic links:

- `create_magic_link($email)` generates a 32-byte random token, stores only its SHA-256 hash with a 15-minute expiry, and returns the raw token to put in the email.
- `verify_magic_link($token)` hashes the token, deletes the row (single-use, deleted before the expiry check so it cannot be replayed), then returns the email if it had not expired.

Google OAuth:

- `google-login.php` stores a random `state` in the session and redirects to the auth endpoint.
- `google-callback.php` checks `state` with `hash_equals`, exchanges the code, fetches userinfo, and logs in only if `email_verified` is truthy.

## The add-a-feature loop

1. Add or change a table in `db_migrate()` if needed.
2. Add a function to the right `src/` file (`auth.php`, `mail.php`, `stripe.php`, or a new file required from `bootstrap.php`).
3. Add or edit a page in `public/`, starting with the bootstrap require.
4. Validate input, write through prepared statements, escape all output.
5. Click through it locally with mail going to `logs/mail.log`.

## Conventions

- Plain functions, no classes.
- `snake_case` for functions and DB columns.
- One shared PDO via `db()`.
- Redirect after every successful POST, then `exit`.
- Keep comments short and about why, not what.
- No em-dashes in code comments or docs.

## Local tooling

### Mailpit (email)

MailHog is dead; use Mailpit.

```bash
brew install mailpit
mailpit            # SMTP on :1025, web UI on http://127.0.0.1:8025
```

Then in `config.php`:

```php
'mail_transport' => 'smtp',
'smtp_host'      => '127.0.0.1',
'smtp_port'      => 1025,
```

Sent mail shows up in the Mailpit UI instead of a real inbox.

### mock-oauth2-server (Google login)

Run a local OpenID provider in Docker:

```bash
docker run -p 8080:8080 ghcr.io/navikt/mock-oauth2-server:2.1.10
```

Point the app at it with environment variables when you start PHP:

```bash
GOOGLE_AUTH_ENDPOINT=http://127.0.0.1:8080/default/authorize \
GOOGLE_TOKEN_ENDPOINT=http://127.0.0.1:8080/default/token \
GOOGLE_USERINFO_ENDPOINT=http://127.0.0.1:8080/default/userinfo \
php -S 127.0.0.1:8000 -t public
```

Set any non-blank `google_client_id` and `google_client_secret` in `config.php` so the button appears. The mock server accepts any login and returns a verified email, which is enough to exercise the whole flow.
