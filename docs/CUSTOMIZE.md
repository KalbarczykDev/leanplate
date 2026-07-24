# Customize after cloning

Start with `src/config/app.php`. It is committed and contains the product
identity shared by the HTML layout and PWA manifest. Keep secrets and
environment-specific values in the gitignored `src/config/config.php`.

## Required

- [ ] Change the name, short name, description, tagline, and colors in
  `src/config/app.php`.
- [ ] Replace the landing-page product copy in `public/index.php`.
- [ ] Replace the PNGs in `public/assets/icons/`.
- [ ] Replace `public/assets/favicon.png`.
- [ ] Replace `public/assets/og-default.png`.
- [ ] Review the colors and theme in `public/assets/css/style.css`.
- [ ] Set `base_url`, `mail_from`, and service credentials in
  `src/config/config.php`.
- [ ] Create the Stripe product and price when billing is needed.
- [ ] Update `server_name` and the application directory in the nginx,
  deployment, and workflow files.

## Check for defaults

Run:

```bash
php scripts/check-customization.php
```

It exits with status 1 and lists warnings while important template defaults
remain. It does not print secrets.
