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

    $user = current_user();
    $nav  = $user
        ? '<a href="/app">App</a><a href="/app/account">Account</a><a href="/auth/logout">Log out</a>'
        : '<a href="/auth/login">Sign in</a>';

    // Toast shown after the feedback modal posts (?fb=1 on any page).
    $toast = flash('fb') ? '<div class="toast" role="status">Thanks for the feedback.</div>' : '';

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff4d00">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500;600&family=IBM+Plex+Sans:wght@400;600&display=swap">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="bar">
            <a class="brand" href="/">Leanplate</a>
            <nav class="site-nav">$nav</nav>
        </div>
    </header>
    $toast
    <main class="container">

HTML;
}

function layout_footer(): void
{
    $cfg = config();
    $version = trim((string)($cfg['app_version'] ?? ''));
    $versionHtml = $version !== ''
        ? '<p class="version">v' . htmlspecialchars($version) . '</p>'
        : '';

    $analytics = (string)($cfg['analytics_snippet'] ?? '');

    echo <<<HTML
    </main>
    <footer class="site-footer">
        $versionHtml
    </footer>

HTML;

    feedback_widget();

    echo <<<HTML
    $analytics
    <script src="/assets/js/pwa.js"></script>
</body>
</html>

HTML;
}
