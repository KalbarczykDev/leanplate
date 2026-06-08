<?php
require __DIR__ . '/../src/bootstrap.php';

$user   = require_login();
$status = $_GET['checkout'] ?? null;
?>
  <!doctype html>
  <html lang="en">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Dashboard</title>
      <link rel="stylesheet" href="/style.css">
  </head>
  <body>
      <main class="container">
          <h1>Dashboard</h1>

          <?php if ($status === 'success'): ?>
              <p class="notice">Payment received. Your plan updates once
  Stripe confirms.</p>
          <?php elseif ($status === 'cancel'): ?>
              <p class="notice">Checkout canceled.</p>
          <?php endif; ?>

          <p>Signed in as <strong><?= htmlspecialchars($user['email'])
?></strong>.</p>
          <p>Plan: <strong><?= htmlspecialchars($user['plan'])
?></strong></p>

          <?php if ($user['plan'] === 'free' && stripe_enabled()): ?>
              <p><a class="btn" href="/checkout.php">Upgrade to Pro</a></p>
          <?php endif; ?>

          <p><a href="/logout.php">Log out</a></p>
      </main>
  </body>
  </html>
