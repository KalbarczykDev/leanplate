<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The delete form posts a "confirm" field; the profile form does not.
    if (isset($_POST['confirm'])) {
        if ($_POST['confirm'] !== 'DELETE') {
            header('Location: /account');
            exit;
        }
        stripe_cancel_subscription($user);
        db()->prepare('DELETE FROM feedback WHERE user_id = ?')->execute([$user['id']]);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
        logout_user();
        header('Location: /?deleted=1');
        exit;
    }
    $name = trim((string)($_POST['display_name'] ?? ''));
    db()->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([$name, $user['id']]);
    header('Location: /account?saved=1');
    exit;
}
$saved = isset($_GET['saved']);
layout_header('Account');
?>
    <p class="kicker">Settings</p>
    <h1>Account</h1>
    <?php if ($saved): ?>
        <p class="notice">Saved.</p>
    <?php endif; ?>
    <div class="card">
        <form method="post" action="/account">
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" value="<?= htmlspecialchars((string)($user['display_name'] ?? '')) ?>">
            <label for="email">Email</label>
            <input id="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            <button class="btn" type="submit">Save</button>
        </form>
    </div>

    <div class="danger-zone">
        <h2>Delete account</h2>
        <p>This permanently deletes your account and cancels any subscription. It cannot be undone.</p>
        <form method="post" action="/account" onsubmit="return confirm('Delete your account permanently?');">
            <label for="confirm">Type DELETE to confirm</label>
            <input id="confirm" name="confirm" autocomplete="off">
            <button class="btn btn-danger" type="submit">Delete my account</button>
        </form>
    </div>
<?php layout_footer(); ?>
