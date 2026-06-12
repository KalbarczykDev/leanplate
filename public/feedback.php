<?php

// POST-only target for the feedback modal in the site footer (src/lib/layout.php).
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

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
}

// Back to the page the modal was opened on; only the path, never a foreign host.
$back = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '/';
header('Location: ' . $back . '?fb=1');
exit;
