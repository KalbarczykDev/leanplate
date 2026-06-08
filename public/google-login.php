<?php

require __DIR__ . '/../src/bootstrap.php';

if (!google_enabled()) {
    header('Location: /login.php');
    exit;
}
// CSRF: random state echoed back by the provider and checked in the callback.
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

header('Location: ' . google_auth_url($state));
exit;
