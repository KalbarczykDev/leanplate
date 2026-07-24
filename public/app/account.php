<?php
require __DIR__ . '/../../src/bootstrap.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        if (($_POST['confirm'] ?? '') !== 'DELETE') {
            header('Location: /app/account');
            exit;
        }
        delete_account($user);
        header('Location: /?deleted=1');
        exit;
    }

    if ($action === 'save') {
        $name = trim((string)($_POST['display_name'] ?? ''));
        update_account((int)$user['id'], $name);
        header('Location: /app/account?saved=1');
        exit;
    }

    header('Location: /app/account');
    exit;
}
$saved = flash('saved');
layout_header('Account');
?>
    <p class="kicker">Settings</p>
    <h1>Account</h1>
    <?php if ($saved): ?>
        <p class="notice">Saved.</p>
    <?php endif; ?>
    <div class="card">
        <form method="post" action="/app/account">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
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
        <form method="post" action="/app/account" onsubmit="return confirm('Delete your account permanently?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <label for="confirm">Type DELETE to confirm</label>
            <input id="confirm" name="confirm" autocomplete="off">
            <button class="btn btn-danger" type="submit">Delete my account</button>
        </form>
    </div>
<?php layout_footer(); ?>
