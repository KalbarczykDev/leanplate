<?php

// Stripe Checkout + webhook via raw curl. No SDK.
declare(strict_types=1);

function stripe_enabled(): bool
{
    $c = config();
    return !empty($c['stripe_secret_key']) &&
  !empty($c['stripe_price_id']);
}

// Create a subscription Checkout Session, return its hosted URL.
function stripe_create_checkout(array $user): ?string
{
    $c      = config();
    $fields = [
        'mode'                  => 'subscription',
        'success_url'           => $c['base_url'] . '/app?checkout=success',
        'cancel_url'            => $c['base_url'] . '/app?checkout=cancel',
        'customer_email'        => $user['email'],
        'client_reference_id'   => (string)$user['id'], // maps the webhook back to our user
        'line_items[0][price]'  => $c['stripe_price_id'],
        'line_items[0][quantity]' => 1,
    ];
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_USERPWD        => $c['stripe_secret_key'] . ':', // secret key as basic-auth user
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        return null;
    }
    $data = json_decode($resp, true);
    return $data['url'] ?? null;
}

// Verify Stripe-Signature header by hand: signed payload is "{t}.{body}".
function stripe_verify_webhook(string $payload, string $sigHeader, string
$secret): bool
{
    if ($secret === '' || $sigHeader === '') {
        return false;
    }
    $parts = [];
    foreach (explode(',', $sigHeader) as $kv) {
        [$k, $v] = array_pad(explode('=', $kv, 2), 2, '');
        $parts[$k][] = $v;
    }
    $t    = $parts['t'][0] ?? null;
    $sigs = $parts['v1'] ?? [];
    if (!$t || !$sigs) {
        return false;
    }
    // Reject anything older than 5 minutes to limit replay.
    if (abs(time() - (int)$t) > 300) {
        return false;
    }
    $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
    foreach ($sigs as $s) {
        if (hash_equals($expected, $s)) { // constant-time compare
            return true;
        }
    }
    return false;
}

// Create a Stripe Billing Portal session (cancel, card, invoices). Null when
// the user has no Stripe customer yet or Stripe is unconfigured.
function stripe_portal_url(array $user): ?string
{
    $c = config();
    if (empty($user['stripe_id']) || !stripe_enabled()) {
        return null;
    }
    $fields = [
        'customer'   => $user['stripe_id'],
        'return_url' => $c['base_url'] . '/app',
    ];
    $ch = curl_init('https://api.stripe.com/v1/billing_portal/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_USERPWD        => $c['stripe_secret_key'] . ':',
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        return null;
    }
    $data = json_decode($resp, true);
    return $data['url'] ?? null;
}

// Cancel any active subscriptions for the user's Stripe customer. No-op when
// Stripe is unconfigured or the user has no customer id.
function stripe_cancel_subscription(array $user): void
{
    $c = config();
    if (empty($user['stripe_id']) || !stripe_enabled()) {
        return;
    }
    $ch = curl_init('https://api.stripe.com/v1/subscriptions?customer=' . urlencode((string)$user['stripe_id']) . '&status=active');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $c['stripe_secret_key'] . ':',
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        return;
    }
    $list = json_decode($resp, true);
    foreach ($list['data'] ?? [] as $sub) {
        if (empty($sub['id'])) {
            continue;
        }
        $del = curl_init('https://api.stripe.com/v1/subscriptions/' . $sub['id']);
        curl_setopt_array($del, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_USERPWD        => $c['stripe_secret_key'] . ':',
            CURLOPT_TIMEOUT        => 20,
        ]);
        curl_exec($del);
    }
}
