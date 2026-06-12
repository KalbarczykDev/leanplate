<?php
require __DIR__ . '/../../src/bootstrap.php';

$user   = require_login();
$status = $_GET['checkout'] ?? null;
$name   = trim((string)($user['display_name'] ?? ''));
layout_header('Your app');
?>
    <p class="kicker">Dashboard</p>
    <h1>Your app</h1>
    <?php if ($status === 'success'): ?>
        <p class="notice">Payment received. Your plan updates once Stripe confirms.</p>
    <?php elseif ($status === 'cancel'): ?>
        <p class="notice">Checkout canceled.</p>
    <?php endif; ?>
    <?php if (isset($_GET['upgrade'])): upgrade_prompt(); endif; ?>

    <ul class="manifest">
        <?php if ($name !== ''): ?>
        <li><span class="k">Name</span><span class="v"><strong><?= htmlspecialchars($name) ?></strong></span></li>
        <?php endif; ?>
        <li><span class="k">Signed in as</span><span class="v"><strong><?= htmlspecialchars($user['email']) ?></strong></span></li>
        <li><span class="k">Plan</span><span class="v"><strong><?= htmlspecialchars($user['plan']) ?></strong></span></li>
        <li><span class="k">Member since</span><span class="v"><strong><?= htmlspecialchars(substr((string)$user['created_at'], 0, 10)) ?></strong></span></li>
    </ul>

    <?php if ($user['plan'] === 'free' && stripe_enabled()): ?>
        <p><a class="btn" href="/billing/checkout">Upgrade to Pro</a></p>
    <?php endif; ?>
    <p><a href="/account">Account</a><?php if (stripe_enabled() && !empty($user['stripe_id'])): ?> · <a href="/billing/portal">Manage billing</a><?php endif; ?></p>

    <p class="lede">This page is yours: replace the manifest above with your product and start shipping.</p>
<?php layout_footer(); ?>
