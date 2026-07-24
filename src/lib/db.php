<?php

// Single shared PDO connection. All queries elsewhere use prepared statements.
declare(strict_types=1);

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

function db_migrate_001_initial_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        plan TEXT NOT NULL DEFAULT 'free',
        stripe_id TEXT,
        display_name TEXT,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS login_tokens (
        token_hash TEXT PRIMARY KEY,
        email TEXT NOT NULL,
        expires_at TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        email TEXT,
        message TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
}
