<?php

return [
      'env'      => 'dev', // 'dev' shows errors, 'prod' logs them
      'base_url' => 'http://127.0.0.1:8000', // no trailing slash

      // storage paths (defaults are fine for local dev)
      'db_path'  => __DIR__ . '/../data/app.sqlite',
      'log_path' => __DIR__ . '/../logs/mail.log',

      // mail: smtp (Mailpit/SMTP) | resend (prod) | log (fallback)
      'mail_transport' => 'smtp',
      'mail_from'      => 'no-reply@example.com',
      'smtp_host'      => '127.0.0.1', // Mailpit listens here
      'smtp_port'      => 1025,
      'resend_api_key' => '',

      // stripe: blank secret/price hides the upgrade button
      'stripe_secret_key'     => '',
      'stripe_price_id'       => '',
      'stripe_webhook_secret' => '',

      // google oauth: blank client id/secret hides the Google button.
      // endpoints default to real Google; env vars override for mock-oauth2-server:
      //   GOOGLE_AUTH_ENDPOINT / GOOGLE_TOKEN_ENDPOINT / GOOGLE_USERINFO_ENDPOINT
      'google_client_id'        => '',
      'google_client_secret'    => '',
      'google_auth_endpoint'    =>
  'https://accounts.google.com/o/oauth2/v2/auth',
      'google_token_endpoint'   => 'https://oauth2.googleapis.com/token',
      'google_userinfo_endpoint' =>
  'https://openidconnect.googleapis.com/v1/userinfo',

      // ops: where throttled fatal-error alerts go (blank disables)
      'alert_email' => '',
  ];
