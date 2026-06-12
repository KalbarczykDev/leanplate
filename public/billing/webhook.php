<?php

require __DIR__ . '/../../src/bootstrap.php';

$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret  = config()['stripe_webhook_secret'] ?? '';

if (!stripe_verify_webhook($payload, $sig, $secret)) {
    http_response_code(400);
    exit('Invalid signature.');
}

$event = json_decode($payload, true) ?: [];
$type  = $event['type'] ?? '';
$obj   = $event['data']['object'] ?? [];

if ($type === 'checkout.session.completed') {
    // client_reference_id is the user id we set when creating the session.
    $uid      = $obj['client_reference_id'] ?? null;
    $customer = $obj['customer'] ?? null;
    if ($uid) {
        db()->prepare('UPDATE users SET plan = ?, stripe_id = ? WHERE id =
  ?')
            ->execute(['pro', $customer, $uid]);
    }
} elseif ($type === 'customer.subscription.deleted') {
    $customer = $obj['customer'] ?? null;
    if ($customer) {
        db()->prepare('UPDATE users SET plan = ? WHERE stripe_id = ?')
            ->execute(['free', $customer]);
    }
}

http_response_code(200);
echo 'ok';
