<?php

require __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json');
try {
    db()->query('SELECT 1');
    echo json_encode(['status' => 'ok', 'time' => gmdate('c')]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
