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
    curl_close($ch);
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
