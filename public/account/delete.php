<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'DELETE') {
    header('Location: /account');
    exit;
}

stripe_cancel_subscription($user);

db()->prepare('DELETE FROM feedback WHERE user_id = ?')->execute([$user['id']]);
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);

logout_user();
header('Location: /?deleted=1');
exit;
