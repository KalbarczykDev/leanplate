<?php
require __DIR__ . '/../src/bootstrap.php';

$user = current_user();
?>
  <!doctype html>
  <html lang="en">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Leanplate</title>
      <link rel="stylesheet" href="/style.css">
  </head>
  <body>
      <main class="container">
          <h1>Leanplate</h1>
          <p class="lede">A small PHP micro-stack. SQLite, magic links,
  Stripe, no framework.</p>
          <?php if ($user): ?>
              <p>Signed in as <strong><?= htmlspecialchars($user['email'])
              ?></strong>.</p>
              <p><a class="btn" href="/dashboard.php">Go to dashboard</a></p>
          <?php else: ?>
              <p><a class="btn" href="/login.php">Sign in</a></p>
          <?php endif; ?>
      </main>
  </body>
  </html>
