# Leanplate

A levelsio-style PHP micro-stack template. Raw PHP, SQLite, no framework, no build step. Clone it, copy a config file, and you have passwordless auth, Stripe payments, and a deploy pipeline ready to go.

## What it is

A starting point for a small subscription web app that one person can run on one cheap VPS. It favors plain functions, prepared statements, and boring infrastructure over abstractions. Everything you need fits in a handful of files you can read in an afternoon.

## Stack

- PHP 8.3+
- SQLite
- CSS
- Email: pluggable transport (log file in dev, Resend in prod)
- Payments: Stripe Checkout
- Auth: passwordless (magic links and Google OAuth),
- Deploy: Hetzner VPS , nginx plus PHP-FPM plus certbot, Cloudflare in front
- CI: GitHub Actions plus rsync

## Quick start

```bash
cp src/config/config.example.php src/config/config.php
php -S 127.0.0.1:8000 -t public scripts/router.php
```

Open http://127.0.0.1:8000. With the default config, email is written to `logs/mail.log` (so magic links work without any mail server), and the Stripe and Google buttons stay hidden until you add keys.

## Docs

- `docs/DEVELOPMENT.md` for local setup, conventions, and how to add a page or table.
- `docs/DEPLOY.md` for provisioning a Hetzner box and shipping to it.

## Credits

Feature set and philosophy inspired by Pieter Levels' book *MAKE* (https://readmake.com).

## License

MIT. Use it, change it, ship it.
