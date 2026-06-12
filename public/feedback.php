<?php
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string)($_POST['message'] ?? ''));
    $email   = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: null;
    if ($message !== '') {
        $user = current_user();
        db()->prepare('INSERT INTO feedback (user_id, email, message) VALUES (?, ?, ?)')
            ->execute([$user['id'] ?? null, $email, $message]);
        $admin = config()['admin_email'] ?? '';
        if ($admin !== '') {
            @send_mail($admin, 'New feedback', $message . "\n\nfrom: " . ($email ?? 'anonymous'));
        }
        header('Location: /feedback?sent=1');
        exit;
    }
}
$sent = isset($_GET['sent']);
layout_header('Feedback');
?>
    <h1>Feedback</h1>
    <?php if ($sent): ?>
        <p class="notice">Thanks for the feedback.</p>
    <?php else: ?>
        <form method="post" action="/feedback">
            <label for="message">What's on your mind?</label>
            <textarea id="message" name="message" required autofocus></textarea>
            <label for="email">Email (optional)</label>
            <input id="email" type="email" name="email" placeholder="you@example.com">
            <button class="btn" type="submit">Send</button>
        </form>
    <?php endif; ?>
<?php layout_footer(); ?>
