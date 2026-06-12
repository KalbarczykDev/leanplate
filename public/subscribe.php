<?php
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    if ($email) {
        // INSERT OR IGNORE: a duplicate email is a no-op, not an error.
        db()->prepare('INSERT OR IGNORE INTO subscribers (email) VALUES (?)')->execute([$email]);
    }
    header('Location: /subscribe?sent=1');
    exit;
}
$sent = isset($_GET['sent']);
layout_header('Subscribe');
?>
    <h1>Stay in the loop</h1>
    <?php if ($sent): ?>
        <p class="notice">Thanks - you're on the list.</p>
    <?php else: ?>
        <form method="post" action="/subscribe">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required autofocus placeholder="you@example.com">
            <button class="btn" type="submit">Subscribe</button>
        </form>
    <?php endif; ?>
<?php layout_footer(); ?>
