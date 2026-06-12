<?php

// Dev router for PHP's built-in server: mimics nginx clean URLs so /feedback
// serves public/feedback.php. Run from the repo root:
//   php -S 127.0.0.1:8000 -t public scripts/router.php
declare(strict_types=1);

$root = realpath(__DIR__ . '/../public');
$path = rawurldecode((string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real files (assets, direct .php hits) as-is.
if ($path !== '/' && is_file($root . $path)) {
    return false;
}

// nginx maps /sitemap.xml to the PHP generator.
if ($path === '/sitemap.xml') {
    require $root . '/sitemap.php';
    return true;
}

// Directory -> its index.php (/account, /app).
$dir = rtrim($path, '/');
if (is_dir($root . $dir) && is_file($root . $dir . '/index.php')) {
    require $root . $dir . '/index.php';
    return true;
}

// Clean URL -> page file (/feedback -> feedback.php, /auth/login -> auth/login.php).
if ($dir !== '' && is_file($root . $dir . '.php')) {
    require $root . $dir . '.php';
    return true;
}

// Explicit 404: the built-in server would otherwise fall back to /index.php.
http_response_code(404);
echo 'Not Found';
return true;
