<?php
require __DIR__ . '/../../src/bootstrap.php';

$sent  = false;
$error = null;

// Step 2: user clicked the emailed link.
if (isset($_GET['token'])) {
    $email = verify_magic_link((string)$_GET['token']);
    if ($email) {
        login_user($email);
        header('Location: /app');
        exit;
    }
    $error = 'That link is invalid or expired. Request a new one.';
}

// Step 1: user asked for a link.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $error = 'Enter a valid email address.';
    } else {
        $token = create_magic_link($email);
        $link  = config()['base_url'] . '/auth/login?token=' . urlencode($token);
        send_mail($email, 'Your sign-in link', "Click to sign in:\n\n$link\n\nThis link expires in 15 minutes.");
        $sent = true;
    }
}
layout_header('Sign in');
?>
    <p class="kicker">No passwords here</p>
    <h1>Sign in</h1>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($sent): ?>
        <p class="notice">Check your email for a sign-in link.</p>
    <?php else: ?>
        <div class="card">
            <form method="post" action="/auth/login">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required autofocus placeholder="you@example.com">
                <button class="btn" type="submit">Send magic link</button>
            </form>
            <?php if (google_enabled()): ?>
                <p class="or">or</p>
                <p><a class="btn btn-secondary" href="/auth/google">Continue with Google</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php layout_footer(); ?>
