<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();
$url  = stripe_portal_url($user);
if (!$url) {
    header('Location: /app');
    exit;
}
header('Location: ' . $url);
exit;
