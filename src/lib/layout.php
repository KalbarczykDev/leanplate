<?php

// Shared HTML chrome. Plain functions, no template engine.
declare(strict_types=1);

function layout_header(string $title = 'Leanplate', string $description = '', string $ogImage = ''): void
{
    $cfg  = config();
    $base = rtrim((string)($cfg['base_url'] ?? ''), '/');
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $img  = $ogImage !== '' ? $ogImage : (string)($cfg['og_default_image'] ?? '/assets/og-default.png');
    if ($img !== '' && !preg_match('#^https?://#', $img)) {
        $img = $base . $img;   // og:image must be absolute
    }
    $t  = htmlspecialchars($title);
    $d  = htmlspecialchars($description);
    $u  = htmlspecialchars($base . $path);
    $im = htmlspecialchars($img);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>$t</title>
    <meta name="description" content="$d">
    <meta property="og:title" content="$t">
    <meta property="og:description" content="$d">
    <meta property="og:url" content="$u">
    <meta property="og:type" content="website">
    <meta property="og:image" content="$im">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="$t">
    <meta name="twitter:description" content="$d">
    <meta name="twitter:image" content="$im">
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <main class="container">

HTML;
}

function layout_footer(): void
{
    $cfg     = config();
    $version = trim((string)($cfg['app_version'] ?? ''));
    $ver     = $version !== '' ? '<p class="version">v' . htmlspecialchars($version) . '</p>' : '';
    // Trusted operator config (GA/Plausible/etc.) — intentionally not escaped.
    $snippet = (string)($cfg['analytics_snippet'] ?? '');
    echo <<<HTML
    </main>
    <footer class="site-footer">
        <a href="/feedback">Feedback</a>
        $ver
    </footer>
    $snippet
</body>
</html>

HTML;
}
