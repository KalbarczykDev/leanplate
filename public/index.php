<?php
require __DIR__ . '/../src/bootstrap.php';

// Email capture posts back to the landing page itself.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    if ($email) {
        // INSERT OR IGNORE: a duplicate email is a no-op, not an error.
        db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
    }
    header('Location: /?sub=1');
    exit;
}

$user = current_user();
$sub  = isset($_GET['sub']);
layout_header('Leanplate', 'A levelsio-style PHP micro-stack. SQLite, magic links, Stripe, one VPS, no framework.');
?>
    <p class="kicker">PHP micro-stack template</p>
    <h1>Ship a SaaS<br>in a handful of files</h1>
    <p class="lede">Raw PHP, SQLite, magic links, Stripe. No framework, no build step, one cheap VPS. Small enough to read in an afternoon.</p>

    <?php if ($user): ?>
        <p>Signed in as <strong><?= htmlspecialchars($user['email']) ?></strong>.</p>
        <p><a class="btn" href="/app">Go to your app</a></p>
    <?php else: ?>
        <p><a class="btn" href="/auth/login">Sign in</a></p>
    <?php endif; ?>

    <h2>What's inside</h2>
    <ul class="manifest">
        <li><span class="k">Auth</span><span class="v">Passwordless: magic links + Google OAuth, both merge on email</span></li>
        <li><span class="k">Payments</span><span class="v">Stripe Checkout, billing portal, webhook verified by hand</span></li>
        <li><span class="k">Database</span><span class="v">One SQLite file, WAL mode, schema migrates itself</span></li>
        <li><span class="k">Email</span><span class="v">Log file in dev, Resend in prod, degrades gracefully</span></li>
        <li><span class="k">Deploy</span><span class="v">rsync from GitHub Actions to one VPS, nginx + PHP-FPM</span></li>
        <li><span class="k">Ops</span><span class="v">Nightly SQLite backups, restore test, fatal-error email alerts</span></li>
    </ul>

    <?php if (!$user): ?>
        <?php if ($sub): ?>
            <p class="notice">Thanks - you're on the list.</p>
        <?php else: ?>
            <div class="card">
                <form method="post" action="/">
                    <label for="email">Get updates</label>
                    <input id="email" type="email" name="email" required placeholder="you@example.com">
                    <button class="btn btn-secondary" type="submit">Subscribe</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php layout_footer(); ?>
