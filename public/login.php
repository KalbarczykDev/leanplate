<?php
require __DIR__ . '/../src/bootstrap.php';

$sent  = false;
$error = null;

// Step 2: user clicked the emailed link.
if (isset($_GET['token'])) {
    $email = verify_magic_link((string)$_GET['token']);
    if ($email) {
        login_user($email);
        header('Location: /dashboard.php');
        exit;
    }
    $error = 'That link is invalid or expired. Request a new one.';
}

// Step 1: user asked for a link.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(
        trim($_POST['email'] ?? ''),
        FILTER_VALIDATE_EMAIL
    );
    if (!$email) {
        $error = 'Enter a valid email address.';
    } else {
        $token = create_magic_link($email);
        $link  = config()['base_url'] . '/login.php?token=' .
  urlencode($token);
        send_mail($email, 'Your sign-in link', "Click to sign
  in:\n\n$link\n\nThis link expires in 15 minutes.");
        $sent = true;
    }
}
?>
  <!doctype html>
  <html lang="en">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Sign in</title>
      <link rel="stylesheet" href="/style.css">
  </head>
  <body>
      <main class="container">
          <h1>Sign in</h1>

          <?php if ($error): ?>
              <p class="error"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <?php if ($sent): ?>
              <p class="notice">Check your email for a sign-in link.</p>
          <?php else: ?>
              <form method="post" action="/login.php">
                  <label for="email">Email</label>
                  <input id="email" type="email" name="email" required
  autofocus
                         placeholder="you@example.com">
                  <button class="btn" type="submit">Send magic link</button>
              </form>

              <?php if (google_enabled()): ?>
                  <p class="or">or</p>
                  <p><a class="btn btn-secondary"
  href="/google-login.php">Continue with Google</a></p>
              <?php endif; ?>
          <?php endif; ?>
      </main>
  </body>
  </html>
