# Growth Features

Features from Pieter Levels' _MAKE_ worth adding **when your product needs
them** - deliberately not in the core template, to keep it lean. Each notes the
MAKE page reference.

- **Patronage / "support development" button** - an in-app donate
  (recurring or one-off) that unlocks nothing; helps niche apps survive.
- **Job board** - paid B2B postings ($49–$299) on a niche audience.
- **Community / forum / chat** - discussion, profiles, network
  effects; people pay for connections.
- **PayPal as a second payment option** - reaches non-credit-card
  countries; Levels saw ~40% more conversions.
- **Push notifications** - build on the existing service worker only when the
  product has a time-sensitive reason to re-engage users; abuse leads users to
  disable them.
- **Self-served native ads / sponsor slots** - on-brand, labeled
  "Sponsored"; pays far more than ad networks.
- **Static-page caching** - pre-render public hot pages to flat HTML to
  survive traffic spikes. This is separate from the PWA service worker, which
  caches only static assets.
- **Dynamic per-page social share images** - auto-generate the
  `og:image` per page for more social clicks.
- **Public API with keys** - drives referrals; gate it with
  registration so it can't be abused/cloned.
- **Uptime / error monitoring + SMS alerts** wire UptimeRobot or
  Cronitor to the existing `/health` endpoint.
