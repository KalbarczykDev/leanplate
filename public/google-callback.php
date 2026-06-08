<?php

// public/google-callback.php
require __DIR__ . '/../src/bootstrap.php';

if (!google_enabled()) {
    header('Location: /login.php');
    exit;
}

$state    = (string)($_GET['state'] ?? '');
$expected = (string)($_SESSION['oauth_state'] ?? '');
unset($_SESSION['oauth_state']); // one-shot

if ($state === '' || !hash_equals($expected, $state)) {
    http_response_code(400);
    exit('Invalid OAuth state.');
}

$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    http_response_code(400);
    exit('Missing authorization code.');
}

$tokens = google_exchange_code($code);
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
header('Location: /dashboard.php');
exit;
