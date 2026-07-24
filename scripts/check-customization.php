<?php

declare(strict_types=1);

$app = require __DIR__ . '/../src/config/app.php';
$configPath = __DIR__ . '/../src/config/config.php';
$config = is_file($configPath) ? require $configPath : [];
$nginx = file_get_contents(__DIR__ . '/../deploy/nginx.site.conf');
$warnings = [];

if (($app['name'] ?? '') === 'Leanplate') {
    $warnings[] = 'App name is still Leanplate.';
}

$baseUrl = (string)($config['base_url'] ?? '');
if ($baseUrl === '' || str_contains($baseUrl, '127.0.0.1')) {
    $warnings[] = 'base_url is missing or still points to 127.0.0.1.';
}

$mailFrom = (string)($config['mail_from'] ?? '');
if ($mailFrom === '' || str_ends_with($mailFrom, '@example.com')) {
    $warnings[] = 'mail_from is missing or still uses example.com.';
}

if (is_string($nginx) && str_contains($nginx, 'server_name example.com')) {
    $warnings[] = 'nginx server_name is still example.com.';
}

if ($warnings === []) {
    echo "Customization check passed.\n";
    exit(0);
}

foreach ($warnings as $warning) {
    echo "[WARN] $warning\n";
}

exit(1);
