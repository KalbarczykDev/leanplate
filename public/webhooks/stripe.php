<?php

require __DIR__ . '/../../src/bootstrap.php';

$payload = file_get_contents('php://input');
$signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

if (
    !is_string($payload)
    || !stripe_handle_webhook($payload, $signature)
) {
    http_response_code(400);
    exit('Invalid webhook.');
}

http_response_code(200);
echo 'ok';
