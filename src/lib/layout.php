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
        ? '<a href="/app">App</a><a href="/account">Account</a><a href="/auth/logout">Log out</a>'
        : '<a href="/auth/login">Sign in</a>';

    // Toast shown after the feedback modal posts (?fb=1 on any page).
    $toast = isset($_GET['fb']) ? '<div class="toast" role="status">Thanks for the feedback.</div>' : '';

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff4d00">
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
    $cfg     = config();
    $version = trim((string)($cfg['app_version'] ?? ''));
    $ver     = $version !== '' ? '<p class="version">v' . htmlspecialchars($version) . '</p>' : '';
    // Trusted operator config (GA/Plausible/etc.) - intentionally not escaped.
    $snippet = (string)($cfg['analytics_snippet'] ?? '');
    echo <<<HTML
    </main>
    <footer class="site-footer">
        $ver
    </footer>
    <button class="fb-fab" type="button" onclick="document.getElementById('fb-modal').showModal()">Feedback</button>
    <dialog id="fb-modal" class="modal">
        <h2>Feedback</h2>
        <form method="post" action="/feedback">
            <label for="fb-message">What's on your mind?</label>
            <textarea id="fb-message" name="message" required></textarea>
            <label for="fb-email">Email (optional)</label>
            <input id="fb-email" type="email" name="email" placeholder="you@example.com">
            <div class="modal-actions" style="display:flex">
                <button class="btn" type="submit">Send</button>
                <button class="btn btn-secondary" type="button" onclick="this.closest('dialog').close()">Cancel</button>
            </div>
        </form>
    </dialog>
    $snippet
</body>
</html>

HTML;
}

// Reusable "upgrade to Pro" nudge. Hidden when Stripe is unconfigured.
function upgrade_prompt(): void
{
    if (!stripe_enabled()) {
        return;
    }
    echo '<div class="upgrade">'
       . '<p>This feature needs Pro.</p>'
       . '<p><a class="btn" href="/billing/checkout">Upgrade to Pro</a></p>'
       . '</div>';
}
