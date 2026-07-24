<?php

declare(strict_types=1);

// True when a one-shot status flag is present in the query string,
// e.g. after a POST redirect to ...?saved=1.
function flash(string $key): bool
{
    return isset($_GET[$key]);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="'
        . htmlspecialchars(csrf_token())
        . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';

    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Bad request (invalid CSRF token). Reload the page and try again.');
    }
}
