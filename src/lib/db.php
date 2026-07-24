<?php

// Single shared PDO connection. All queries elsewhere use prepared statements.
declare(strict_types=1);

require __DIR__ . '/../db/migrations/db_migrate_001_initial_schema.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $cfg = config();
    $path = $cfg['db_path'];

    $pdo = new PDO('sqlite:' . $path, null, null, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // WAL allows concurrent reads during writes; busy_timeout avoids "database is locked".

    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');

    db_migrate($pdo);
    return $pdo;
}

function db_migrate(PDO $pdo): void
{
    $migrations = [
        1 => 'db_migrate_001_initial_schema',
    ];

    $transactionStarted = false;

    try {
        // Lock before reading the version so concurrent requests cannot run
        // the same migration.
        $pdo->exec('BEGIN IMMEDIATE');
        $transactionStarted = true;

        $currentVersion = (int)$pdo
            ->query('PRAGMA user_version')
            ->fetchColumn();

        foreach ($migrations as $version => $migration) {
            if ($version <= $currentVersion) {
                continue;
            }

            $migration($pdo);

            // The version is an internal integer, never request data.
            $pdo->exec('PRAGMA user_version = ' . (int)$version);
        }

        $pdo->exec('COMMIT');
        $transactionStarted = false;
    } catch (Throwable $error) {
        if ($transactionStarted) {
            $pdo->exec('ROLLBACK');
        }

        throw $error;
    }
}
