<?php

// send_mail() picks transport from config: resend | log. Failures fall back to log.
declare(strict_types=1);

function send_mail(string $to, string $subject, string $body): bool
{
    $transport = config()['mail_transport'] ?? 'log';
    return match ($transport) {
        'resend' => mail_resend($to, $subject, $body),
        default => mail_log($to, $subject, $body)
    };
}

// Append to a file. Always works, so it is the universal fallback.
function mail_log(string $to, string $subject, string $body): bool
{
    $line = sprintf(
        "[%s] TO=%s SUBJECT=%s BODY=%s\n\n",
        gmdate('c'),
        $to,
        $subject,
        $body
    );

    file_put_contents(config()['log_path'], $line, FILE_APPEND | LOCK_EX);
    return true;
}

// Resend transactional API via raw curl (no SDK).
function mail_resend(string $to, string $subject, string $body): bool
{
    $c   = config();
    $key = $c['resend_api_key'] ?? '';
    if ($key === '') {
        return mail_log($to, $subject, $body); // blank key degrades to log
    }
    $payload = json_encode([
        'from'    => $c['mail_from'] ?? 'no-reply@example.com',
        'to'      => [$to],
        'subject' => $subject,
        'text'    => $body,
    ]);
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($resp !== false && $code >= 200 && $code < 300) {
        return true;
    }
    return mail_log($to, $subject, $body); // keep the message on failure
}
