<?php

// Start and callback in one file: no ?code means begin the flow.
require __DIR__ . '/../../src/bootstrap.php';

if (!google_enabled()) {
    header('Location: /auth/login');
    exit;
}

if (!isset($_GET['code'])) {
    // CSRF: random state echoed back by the provider and checked below.
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    header('Location: ' . google_auth_url($state));
    exit;
}

$state    = (string)($_GET['state'] ?? '');
$expected = (string)($_SESSION['oauth_state'] ?? '');
unset($_SESSION['oauth_state']); // one-shot

if ($state === '' || !hash_equals($expected, $state)) {
    http_response_code(400);
    exit('Invalid OAuth state.');
}

$tokens = google_exchange_code((string)$_GET['code']);
if (!$tokens) {
    http_response_code(502);
    exit('Token exchange failed.');
}

$info = google_userinfo($tokens['access_token']);
// Trust only a verified email; merge into any existing account on that address.
if (!$info || empty($info['email']) || !filter_var($info['email_verified']
?? false, FILTER_VALIDATE_BOOLEAN)) {
    http_response_code(403);
    exit('Email not verified by Google.');
}

login_user($info['email']);
header('Location: /app');
exit;
