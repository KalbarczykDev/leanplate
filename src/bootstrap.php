<?php

// Single entry point for every public page.
declare(strict_types=1);

function config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/config/config.php';

    if (!is_file($path)) {
        http_response_code(500);
        exit(
            'Missing src/config/config.php. '
            . 'Copy src/config/config.example.php '
            . 'to src/config/config.php.'
        );
    }

    $config = require $path;

    return $config;
}

// Runtime infrastructure
require __DIR__ . '/lib/mail.php';
require __DIR__ . '/lib/runtime.php';

$cfg = config();

ensure_runtime_directories($cfg);
configure_errors($cfg);
start_app_session($cfg);
register_fatal_alerts();

// Remaining shared infrastructure.
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/http.php';
require __DIR__ . '/lib/layout.php';

// Application features.
require __DIR__ . '/app/auth.php';
require __DIR__ . '/app/stripe.php';
require __DIR__ . '/app/account.php';
require __DIR__ . '/app/feedback.php';
