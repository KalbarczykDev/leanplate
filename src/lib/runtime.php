<?php

// Runtime directory, error, session, and fatal-alert setup.
declare(strict_types=1);

function ensure_runtime_directories(array $config): void
{
    $directories = [
        dirname($config['db_path']),
        dirname($config['log_path']),
    ];

    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
    }
}

function configure_errors(array $config): void
{
    error_reporting(E_ALL);

    if (($config['env'] ?? 'prod') === 'dev') {
        ini_set('display_errors', '1');
        return;
    }

    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set(
        'error_log',
        dirname($config['log_path']) . '/php-error.log'
    );
}

function start_app_session(array $config): void
{
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => str_starts_with(
            (string)($config['base_url'] ?? ''),
            'https'
        ),
    ]);

    session_start();
}

function alert_fatal(array $error): void
{
    $config = config();

    $message = sprintf(
        "FATAL type=%d in %s:%d\n%s",
        $error['type'],
        $error['file'],
        $error['line'],
        $error['message']
    );

    error_log($message);

    $recipient = (string)($config['alert_email'] ?? '');

    if ($recipient === '') {
        return;
    }

    // One alert per 15 minutes. The marker timestamp is the throttle.
    $marker = dirname($config['log_path']) . '/.last-alert';
    $now = time();

    if (
        is_file($marker)
        && ($now - (int)@file_get_contents($marker)) < 900
    ) {
        return;
    }

    @file_put_contents($marker, (string)$now);

    @send_mail(
        $recipient,
        'Fatal error on ' . ($config['base_url'] ?? 'app'),
        $message
    );
}

function register_fatal_alerts(): void
{
    register_shutdown_function(function (): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ];

        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        alert_fatal($error);
    });
}
