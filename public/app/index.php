<?php
require __DIR__ . '/../../src/bootstrap.php';

$user   = require_login();
$status = $_GET['checkout'] ?? null;
layout_header('Your app');
?>
    <h1>Your app</h1>
    <?php if ($status === 'success'): ?>
        <p class="notice">Payment received. Your plan updates once Stripe confirms.</p>
    <?php elseif ($status === 'cancel'): ?>
        <p class="notice">Checkout canceled.</p>
    <?php endif; ?>
    <p>Signed in as <strong><?= htmlspecialchars($user['email']) ?></strong>.</p>
    <p>Plan: <strong><?= htmlspecialchars($user['plan']) ?></strong></p>
    <?php if ($user['plan'] === 'free' && stripe_enabled()): ?>
        <p><a class="btn" href="/billing/checkout">Upgrade to Pro</a></p>
    <?php endif; ?>
    <p><a href="/auth/logout">Log out</a></p>
<?php layout_footer(); ?>
