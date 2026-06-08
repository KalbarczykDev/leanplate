# Deploy

Shipping Leanplate to a Hetzner VPS running Ubuntu 24.04, with nginx, PHP-FPM, certbot, and Cloudflare in front. Deploys happen over rsync from GitHub Actions.

## 1. Provision the box

Create a small Hetzner Cloud server (the cheapest shared-CPU tier is plenty) with Ubuntu 24.04. Add your SSH public key during creation so you can log in as root the first time.

## 2. Create a non-root deploy user

```bash
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

From here on, log in as `deploy` and use `sudo`.

## 3. Harden SSH

Edit `/etc/ssh/sshd_config`:

```
PermitRootLogin no
PasswordAuthentication no
```

Warning: before you reload sshd, open a SECOND terminal and confirm you can still log in as `deploy` with your key. If you lock yourself out, you will need the Hetzner web console to recover. Once verified:

```bash
sudo systemctl reload ssh
```

## 4. Firewall

Allow only SSH, HTTP, and HTTPS:

```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

## 5. Install nginx and PHP

```bash
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-sqlite3 php8.3-curl certbot python3-certbot-nginx sqlite3
```

## 6. Lay out the app directory

```bash
sudo mkdir -p /var/www/app
sudo chown -R deploy:deploy /var/www/app
mkdir -p /var/www/app/data /var/www/app/logs /var/www/app/backups
```

The rsync deploy will fill in `public/`, `src/`, `scripts/`, and the rest. `data/`, `logs/`, `backups/`, and `src/config.php` are excluded from rsync, so create them on the server and they survive every deploy.

## 7. nginx vhost

Copy `deploy/nginx.conf` to the server, set `server_name` to your domain, then enable it:

```bash
sudo cp /var/www/app/deploy/nginx.conf /etc/nginx/sites-available/app
sudo ln -s /etc/nginx/sites-available/app /etc/nginx/sites-enabled/app
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 8. TLS with certbot

```bash
sudo certbot --nginx -d example.com
sudo certbot renew --dry-run
```

certbot adds the `listen 443` and certificate lines to your vhost and sets up automatic renewal. The dry run confirms renewal will work. If Cloudflare sits in front, set its SSL mode to Full (strict) so it trusts the certbot certificate.

## 9. Create the config by hand

Secrets never go through git. Create `src/config.php` directly on the server, starting from the example, and set `env` to `prod`, the real `base_url`, your Resend key, Stripe keys, and Google credentials.

```bash
cp /var/www/app/src/config.example.php /var/www/app/src/config.php
nano /var/www/app/src/config.php
chmod 640 /var/www/app/src/config.php
sudo chown deploy:www-data /var/www/app/src/config.php
```

Mode 640 with group `www-data` lets PHP-FPM read it but keeps it off-limits to other users.

## 10. Writable data and logs

PHP-FPM runs as `www-data`, so it must own the directories it writes to:

```bash
sudo chown -R www-data:www-data /var/www/app/data /var/www/app/logs
sudo chmod 750 /var/www/app/data /var/www/app/logs
```

## 11. GitHub secrets and deploy key

Generate a dedicated deploy key (do not reuse your personal key):

```bash
ssh-keygen -t ed25519 -f deploy_key -C "github-actions"
```

Add the public key to the server's `deploy` user:

```bash
cat deploy_key.pub >> /home/deploy/.ssh/authorized_keys
```

In the GitHub repo settings, add Actions secrets:

- `SSH_HOST`: the server IP or hostname
- `SSH_USER`: `deploy`
- `SSH_KEY`: the contents of the private `deploy_key`
- `HEALTH_URL`: `https://example.com/health.php` (used by the health-check workflow)

Allow `deploy` to reload PHP-FPM without a password so the workflow's reload step works. Run `sudo visudo` and add:

```
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.3-fpm
```

## 12. First deploy and smoke test

Push to `master`. The `deploy.yml` workflow rsyncs the tree (excluding `.git/`, `data/`, `logs/`, `backups/`, and `src/config.php`) and reloads PHP-FPM. Then check health:

```bash
curl https://example.com/health.php
# {"status":"ok","time":"..."}
```

## 13. Stripe webhook

In the Stripe dashboard, add a webhook endpoint pointing at `https://example.com/webhook.php` and subscribe to `checkout.session.completed` and `customer.subscription.deleted`. Copy the signing secret into `stripe_webhook_secret` in `src/config.php`. The endpoint verifies the signature by hand (HMAC-SHA256 of `{timestamp}.{body}`, 5-minute replay tolerance), so the secret must match exactly. Send a test event from the dashboard and confirm a user's plan flips to `pro`.

## 14. Backups and restore test

Install a cron for the `deploy` user (`crontab -e`):

```
0 3 * * *   /var/www/app/scripts/backup.sh >> /var/www/app/logs/backup.log 2>&1
0 4 * * 0   /var/www/app/scripts/restore-test.sh >> /var/www/app/logs/restore.log 2>&1
```

`backup.sh` takes a WAL-safe online copy, gzips it, and keeps the newest 14. `restore-test.sh` decompresses the latest backup to a temp file and runs `PRAGMA integrity_check`, so you find out a backup is broken before you need it. For real durability, also copy `backups/` off the box (for example with `rclone` to object storage).

## 15. Ongoing ops

- Logs: `logs/php-error.log`, `logs/mail.log`, plus the cron logs above.
- Fatal errors email `alert_email` (throttled to once per 15 minutes).
- Updates: keep the box patched with `sudo apt update && sudo apt upgrade`.
- Certificates renew automatically; the dry run in step 8 confirmed it.

## Final checklist

- [ ] Non-root `deploy` user with sudo
- [ ] Root login and password auth disabled (verified from a second terminal)
- [ ] ufw allows only 22, 80, 443
- [ ] nginx vhost enabled, `nginx -t` passes
- [ ] certbot TLS issued, renew dry run passes
- [ ] `src/config.php` created by hand, mode 640, `env` set to `prod`
- [ ] `data/` and `logs/` owned by `www-data`
- [ ] GitHub secrets set (`SSH_HOST`, `SSH_USER`, `SSH_KEY`, `HEALTH_URL`)
- [ ] Dedicated deploy key in `authorized_keys`
- [ ] `deploy` may reload php8.3-fpm without a password
- [ ] First deploy green, `/health.php` returns ok
- [ ] Stripe webhook added and test event upgrades a user
- [ ] Backup and restore-test crons installed, backups copied off-box
