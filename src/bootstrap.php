<?php

// Single entry point for every public page: require __DIR__ .'/../src/bootstrap.php';
declare(strict_types=1);

// config() loads src/config.php once and applies env overrides for Google endpoints.
function config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $path = __DIR__ . '/config.php';
    if (!is_file($path)) {
        http_response_code(500);
        exit('Missing src/config.php. Copy src/config.example.php to
  src/config.php.');
    }
    $config = require $path;
    // Env overrides let mock-oauth2-server replace Google endpoints locally.
    foreach (['google_auth_endpoint', 'google_token_endpoint',
  'google_userinfo_endpoint'] as $k) {
        $env = getenv(strtoupper($k));
        if ($env !== false && $env !== '') {
            $config[$k] = $env;
        }
    }
    return $config;
}

$cfg = config();

// Ensure writable dirs exist before anything tries to log.
foreach ([dirname($cfg['db_path']), dirname($cfg['log_path'])] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

// Error handling depends on env: dev shows, prod logs.
error_reporting(E_ALL);
if (($cfg['env'] ?? 'prod') === 'dev') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname($cfg['log_path']) . '/php-error.log');
}

// Harden the session cookie. Secure flag tracks https in base_url.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => str_starts_with(
        (string)($cfg['base_url'] ?? ''),
        'https'
    ),
]);
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/auth.php';

// Throttled alert on fatal errors so prod incidents page someone without spamming.
function alert_fatal(array $err): void
{
    $c   = config();
    $msg = sprintf(
        "FATAL type=%d in %s:%d\n%s",
        $err['type'],
        $err['file'],
        $err['line'],
        $err['message']
    );
    error_log($msg);

    $to = $c['alert_email'] ?? '';
    if ($to === '') {
        return;
    }
    // One alert per 15 min max; mtime of a marker file is the throttle.
    $marker = dirname($c['log_path']) . '/.last-alert';
    $now    = time();
    if (is_file($marker) && ($now - (int)@file_get_contents($marker)) <
  900) {
        return;
    }
    @file_put_contents($marker, (string)$now);
    @send_mail($to, 'Fatal error on ' . ($c['base_url'] ?? 'app'), $msg);
}

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    // Only real fatals, not warnings/notices.
    if (!in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR,
  E_COMPILE_ERROR], true)) {
        return;
    }
    alert_fatal($err);
});
