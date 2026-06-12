<?php

return [
      'env'      => 'dev', // 'dev' shows errors, 'prod' logs them
      'base_url' => 'http://127.0.0.1:8000', // no trailing slash

      // storage paths (defaults are fine for local dev)
      'db_path'  => __DIR__ . '/../../data/app.sqlite',
      'log_path' => __DIR__ . '/../../logs/mail.log',

      // mail: log (dev: magic links land in logs/mail.log) | resend (prod)
      'mail_transport' => 'log',
      'mail_from'      => 'no-reply@example.com',
      'resend_api_key' => '',

      // stripe: blank secret/price hides the upgrade button
      'stripe_secret_key'     => '',
      'stripe_price_id'       => '',
      'stripe_webhook_secret' => '',

      // google oauth: blank client id/secret hides the Google button.
      'google_client_id'        => '',
      'google_client_secret'    => '',

      // ops: where throttled fatal-error alerts go (blank disables)
      'alert_email' => '',

      // social/seo: default Open Graph image (site-relative or absolute URL)
      'og_default_image' => '/assets/og-default.png',

      // analytics: paste a full <script>…</script> snippet; blank emits nothing
      'analytics_snippet' => '',
      // footer version string; blank hides it
      'app_version' => '0.0.1',
      // where feedback notifications go; blank saves feedback without emailing
      'admin_email' => '',
  ];
