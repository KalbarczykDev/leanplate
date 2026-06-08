<?php

// send_mail() picks transport from config: smtp | resend | log. Failures fall back to log.
declare(strict_types=1);

function send_mail(string $to, string $subject, string $body): bool
{
    $transport = config()['mail_transport'] ?? 'log';
    return match ($transport) {
        'smpt' => mail_smtp($to, $subject, $body),
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
        str_repeat('-', 40),
        $body
    );

    file_put_contents(config()['log_path'], $line, FILE_APPEND | LOCK_EX);
    return true;
}

// Read one SMTP reply, including multiline (continuation lines have '-' at index 3).

function smtp_read($fp): string
{
    $data = '';
    while ($line = fgets($fp, 515)) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }

    }
    return $data;
}

// Minimal plaintext SMTP for Mailhog  relay (no auth, no TLS).
function mail_smtp(string $to, string $subject, string $body): bool
{
    $c    = config();
    $host = $c['smtp_host'] ?? '127.0.0.1';
    $port = (int)($c['smtp_port'] ?? 1025);
    $from = $c['mail_from'] ?? 'no-reply@example.com';

    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) {
        return mail_log($to, $subject, $body);
    }
    // Dot-stuffing: a line that is just "." would end DATA early.
    $body = preg_replace('/^\./m', '..', $body);

    smtp_read($fp);
    fwrite($fp, "EHLO localhost\r\n");
    smtp_read($fp);
    fwrite($fp, "MAIL FROM:<$from>\r\n");
    smtp_read($fp);
    fwrite($fp, "RCPT TO:<$to>\r\n");
    smtp_read($fp);
    fwrite($fp, "DATA\r\n");
    smtp_read($fp);

    $headers = "From: $from\r\nTo: $to\r\nSubject: $subject\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/plain;
  charset=utf-8\r\n";
    fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
    smtp_read($fp);

    fwrite($fp, "QUIT\r\n");
    smtp_read($fp);
    fclose($fp);
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
    curl_close($ch);

    if ($resp !== false && $code >= 200 && $code < 300) {
        return true;
    }
    return mail_log($to, $subject, $body); // keep the message on failure
}
