<?php

// Shared HTML chrome. Plain functions, no template engine.
declare(strict_types=1);

function layout_header(string $title = 'Leanplate'): void
{
    $title = htmlspecialchars($title);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$title</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <main class="container">

HTML;
}

function layout_footer(): void
{
    echo <<<HTML
    </main>
</body>
</html>

HTML;
}
