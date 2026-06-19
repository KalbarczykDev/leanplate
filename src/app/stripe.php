<?php

// Stripe Checkout + webhook via raw curl. No SDK.
declare(strict_types=1);

// Reject webhooks whose signed timestamp is older than this, to limit replay.
const STRIPE_WEBHOOK_TOLERANCE = 300; // 5 minutes

function stripe_enabled(): bool
{
    $c = config();
    return !empty($c['stripe_secret_key']) &&
  !empty($c['stripe_price_id']);
}

// One call to the Stripe REST API. Secret key is the basic-auth user.
// Returns the decoded response, or null on transport failure.
// $method: 'GET' | 'POST' | 'DELETE'. POST sends $fields as form-encoded body.
function stripe_api(string $method, string $path, array $fields = []): ?array
{
    $opt = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => config()['stripe_secret_key'] . ':',
        CURLOPT_TIMEOUT        => 20,
    ];
    if ($method === 'POST') {
        $opt[CURLOPT_POST]       = true;
        $opt[CURLOPT_POSTFIELDS] = http_build_query($fields);
    } elseif ($method === 'DELETE') {
        $opt[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }
    $ch = curl_init('https://api.stripe.com/v1/' . $path);
    curl_setopt_array($ch, $opt);
    $resp = curl_exec($ch);
    return $resp === false ? null : json_decode($resp, true);
}

// Create a subscription Checkout Session, return its hosted URL.
function stripe_create_checkout(array $user): ?string
{
    $c    = config();
    $data = stripe_api('POST', 'checkout/sessions', [
        'mode'                  => 'subscription',
        'success_url'           => $c['base_url'] . '/app?checkout=success',
        'cancel_url'            => $c['base_url'] . '/app?checkout=cancel',
        'customer_email'        => $user['email'],
        'client_reference_id'   => (string)$user['id'], // maps the webhook back to our user
        'line_items[0][price]'  => $c['stripe_price_id'],
        'line_items[0][quantity]' => 1,
    ]);
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
    // Reject anything too old to limit replay.
    if (abs(time() - (int)$t) > STRIPE_WEBHOOK_TOLERANCE) {
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
    $data = stripe_api('POST', 'billing_portal/sessions', [
        'customer'   => $user['stripe_id'],
        'return_url' => $c['base_url'] . '/app',
    ]);
    return $data['url'] ?? null;
}

// Cancel any active subscriptions for the user's Stripe customer. No-op when
// Stripe is unconfigured or the user has no customer id.
function stripe_cancel_subscription(array $user): void
{
    if (empty($user['stripe_id']) || !stripe_enabled()) {
        return;
    }
    $list = stripe_api('GET', 'subscriptions?customer=' . urlencode((string)$user['stripe_id']) . '&status=active');
    foreach ($list['data'] ?? [] as $sub) {
        if (!empty($sub['id'])) {
            stripe_api('DELETE', 'subscriptions/' . $sub['id']);
        }
    }
}
