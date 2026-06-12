<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();

if (!stripe_enabled()) {
    header('Location: /app');
    exit;
}

$url = stripe_create_checkout($user);
if (!$url) {
    http_response_code(502);
    exit('Could not start checkout. Try again.');
}

header('Location: ' . $url);
exit;
