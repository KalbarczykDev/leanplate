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
    $pdo->exec("CREATE TABLE IF NOT EXISTS users(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    plan TEXT NOT NULL DEFAULT 'free',
    stripe_id TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_tokens (
          token_hash TEXT PRIMARY KEY,
          email      TEXT NOT NULL,
          expires_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id    INTEGER,
        email      TEXT,
        message    TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
}
