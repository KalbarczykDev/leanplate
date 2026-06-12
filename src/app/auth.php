<?php

// Passwordless: magic links + Google OAuth. Both resolve to find_or_create_user(email).
declare(strict_types=1);

// --- users ---

function find_or_create_user(string $email): array
{
    $email = strtolower(trim($email));
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        return $user;
    }
    db()->prepare('INSERT INTO users (email) VALUES (?)')->execute([$email]);

    $stmt->execute([$email]);
    return $stmt->fetch();
}

// --- sessions ---

function login_user(string $email): string
{
    $user = find_or_create_user($email);
    //Regenerate on privilege change to block session fixation.
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    return (string) $user['email'];
}

function current_user(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    static $cached = null;
    if ($cached !== null && (int)$cached['id'] === (int)$_SESSION['uid']) {
        return $cached;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /auth/login');
        exit;
    }
    return $u;
}

// Like require_login(), but a logged-in free user is sent to an upgrade prompt.
// Wrap pro-only pages with this.
function require_pro(): array
{
    $u = require_login();
    if (($u['plan'] ?? 'free') !== 'pro') {
        header('Location: /app?upgrade=1');
        exit;
    }
    return $u;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}


// --- magic links ---

function create_magic_link(string $email): string
{
    $email = strtolower(trim($email));
    $token = bin2hex(random_bytes(32));
    //Store only the hash so a DB leak cannot replay links.
    $hash = hash('sha256', $token);
    $expires = gmdate('Y-m-d H:i:s', time() + 900); //15 min expiry.
    db()->prepare('INSERT OR REPLACE INTO login_tokens(token_hash, email,expires_at) VALUES (?,?,?)')
      ->execute([$hash, $email, $expires]);
    return $token;
}

function verify_magic_link(string $token): ?string
{
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT email, expires_at FROM login_tokens WHERE token_hash = ?');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    //Single-use: delete before checking expiry so it cannot be reused either way.
    db()->prepare('DELETE FROM login_tokens WHERE token_hash = ?')->execute([$hash]);

    if (strtotime($row['expires_at'] . ' UTC') < time()) {
        return null;
    }
    return $row['email'];
}

// --- google oauth ---

function google_enabled(): bool
{
    $c = config();
    return !empty($c['google_client_id']) && !empty($c['google_client_secret']);
}

function google_auth_url(string $state): string
{
    $c = config();
    $params = http_build_query([
      'client_id' => $c['google_client_id'],
    'redirect_uri' => $c['base_url'] . '/auth/google-callback',
    'response_type' => 'code',
    'scope' => 'openid email',
    'state' => $state,
    'access_type' => 'online',
  ]);
    return $c['google_auth_endpoint'] . '?' . $params;
}

function google_exchange_code(string $code): ?array
{
    $c = config();
    $post = http_build_query([
      'code' => $code,
      'client_id' => $c['google_client_id'],
      'client_secret' => $c['google_client_secret'],
      'redirect_uri' => $c['base_url'] . '/auth/google-callback',
      'grant_type' => 'authorization_code',
    ]);
    $ch = curl_init($c['google_token_endpoint']);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $post,
    CURLOPT_TIMEOUT => 15,]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        return null;
    }

    $data = json_decode($resp, true);
    return empty($data['access_token']) ? null : $data;
}

function google_userinfo(string $accessToken): ?array
{
    $c = config();
    $ch = curl_init($c['google_userinfo_endpoint']);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken
      ],
      CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        return null;
    }
    return json_decode($resp, true) ?: null;
}
