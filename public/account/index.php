<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['display_name'] ?? ''));
    db()->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([$name, $user['id']]);
    header('Location: /account?saved=1');
    exit;
}
$saved = isset($_GET['saved']);
layout_header('Account');
?>
    <h1>Account</h1>
    <?php if ($saved): ?>
        <p class="notice">Saved.</p>
    <?php endif; ?>
    <form method="post" action="/account">
        <label for="display_name">Display name</label>
        <input id="display_name" name="display_name" value="<?= htmlspecialchars((string)($user['display_name'] ?? '')) ?>">
        <label for="email">Email</label>
        <input id="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        <button class="btn" type="submit">Save</button>
    </form>

    <h2>Delete account</h2>
    <p>This permanently deletes your account and cancels any subscription. It cannot be undone.</p>
    <form method="post" action="/account/delete" onsubmit="return confirm('Delete your account permanently?');">
        <label for="confirm">Type DELETE to confirm</label>
        <input id="confirm" name="confirm" autocomplete="off">
        <button class="btn btn-danger" type="submit">Delete my account</button>
    </form>
<?php layout_footer(); ?>
