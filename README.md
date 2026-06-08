# Leanplate

A levelsio-style starter for shipping small, profitable apps fast. Raw PHP, SQLite, and a single VPS — no framework, no build step, no Docker.

**Stack:** PHP 8.3 · SQLite (WAL) · raw CSS · Resend (email) · Stripe (payments) · nginx + PHP-FPM · GitHub Actions deploy · Cloudflare

## What you get out of the box

- **Passwordless auth** — magic links + Google OAuth, merged on verified email
- **Payments** — Stripe Checkout + signed webhook handler
- **Email** — Resend via curl (logs locally in dev, no account needed to start)
- **Backups** — consistent SQLite snapshots with rotation
- **Health check + error alerts** — know when something breaks
- **One-command deploy** — `git push` to master ships it

## Quick start (local)

```bash
# 1. Use this template on GitHub ("Use this template" button), then:
git clone https://github.com/YOU/your-app.git
cd your-app

# 2. Create your local config (secrets blank is fine for dev)
cp src/config.example.php src/config.php

# 3. Run it
cd public
php -S 127.0.0.1:8000
```

Open http://localhost:8000. To test login: submit your email, then check `logs/mail.log` for the magic link (in dev, emails are logged instead of sent).

## Project structure

```
.
├── public/          # web root — nginx serves ONLY this
│   ├── index.php        landing page
│   ├── login.php        magic-link request + verify
│   ├── google-*.php     OAuth start + callback
│   ├── dashboard.php    example protected page
│   ├── checkout.php     Stripe redirect
│   ├── webhook.php      Stripe webhook receiver
│   ├── logout.php
│   ├── health.php       uptime endpoint
│   └── style.css
├── src/             # backend, never served directly
│   ├── bootstrap.php    require this first from every page
│   ├── config.php       YOUR SECRETS (gitignored)
│   ├── config.example.php
│   ├── db.php           SQLite connection + schema
│   ├── auth.php         sessions, magic links, Google OAuth
│   ├── mail.php         Resend + error alerts
│   └── stripe.php       checkout + webhook verification
├── data/            # app.db lives here (gitignored)
├── logs/            # gitignored
├── scripts/         # backup.sh, restore-test.sh
├── deploy/          # nginx.conf sample
└── docs/            # DEVELOPMENT.md, DEPLOY.md
```

## Philosophy

- **Read every line.** No framework magic. The whole app fits in your head.
- **One box.** SQLite means no separate DB server to run or back up.
- **No build step.** Edit a `.php` file, refresh. Edit `.css`, refresh.
- **Secrets stay off git.** `src/config.php` is never committed or deployed by CI — you put it on the server once by hand.

## Documentation

- **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** — local workflow, adding pages and features, conventions
- **[docs/DEPLOY.md](docs/DEPLOY.md)** — provisioning a Hetzner VPS (firewall + SSH hardening), nginx, TLS, deploy, and backups

## License

MIT — do whatever you want.
