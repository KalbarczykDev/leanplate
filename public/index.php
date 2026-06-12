<?php
require __DIR__ . '/../src/bootstrap.php';

$user = current_user();
layout_header('Leanplate');
?>
    <h1>Leanplate</h1>
    <p class="lede">A small PHP micro-stack. SQLite, magic links, Stripe, no framework.</p>
    <?php if ($user): ?>
        <p>Signed in as <strong><?= htmlspecialchars($user['email']) ?></strong>.</p>
        <p><a class="btn" href="/app">Go to your app</a></p>
    <?php else: ?>
        <p><a class="btn" href="/auth/login">Sign in</a></p>
    <?php endif; ?>
<?php layout_footer(); ?>
