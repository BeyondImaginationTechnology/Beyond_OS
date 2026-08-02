<?php
require_once __DIR__ . '/smtp.php';

function smtp_read_line($socket): string {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_command($socket, string $command, array $okCodes): string {
    if ($command !== '') fwrite($socket, $command . "\r\n");
    $response = smtp_read_line($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $okCodes, true)) {
        throw new Exception('SMTP error after [' . $command . ']: ' . trim($response));
    }
    return $response;
}

function smtp_send_html(string $to, string $subject, string $html, string $fromName = SMTP_FROM_NAME): bool {
    if (SMTP_PASS === 'PASTE_EMAIL_PASSWORD_HERE' || SMTP_PASS === '') {
        error_log('SMTP_PASS is not configured in /config/smtp.php');
        return false;
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $remote = (SMTP_SECURE === 'ssl') ? "ssl://{$host}" : $host;

    $socket = @fsockopen($remote, $port, $errno, $errstr, 20);
    if (!$socket) {
        error_log("SMTP connection failed: {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_command($socket, '', [220]);
        smtp_command($socket, 'EHLO beyondimagination.co.technology', [250]);

        if (SMTP_SECURE === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            smtp_command($socket, 'EHLO beyondimagination.co.technology', [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode(SMTP_USER), [334]);
        smtp_command($socket, base64_encode(SMTP_PASS), [235]);

        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $safeSubject = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($subject, 'UTF-8')
            : '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . SMTP_FROM . '>';
        $headers[] = 'Reply-To: ' . SMTP_REPLY_TO;
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $safeSubject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'X-Mailer: Beyond OS SMTP';

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $html;
        $message = preg_replace('/^\./m', '..', $message);

        fwrite($socket, $message . "\r\n.\r\n");
        smtp_command($socket, '', [250]);
        smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        error_log($e->getMessage());
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }
}

function beyond_verify_url(string $token, string $app = 'beyond_id'): string {
    $baseUrl = 'https://beyondimagination.co.technology';
    if ($app === 'catering') {
        return $baseUrl . '/beyond-catering/auth/verify-email.php?token=' . urlencode($token);
    }
    return $baseUrl . '/beyond-id/auth/verify-email.php?token=' . urlencode($token);
}

function send_verification_email(string $to, string $token, string $app = 'beyond_id', string $name = ''): bool {
    $brand = ($app === 'catering') ? 'Beyond Catering' : 'Beyond ID';
    $verifyUrl = beyond_verify_url($token, $app);
    $hello = $name ? 'Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',' : 'Hi,';
    $html = "
    <html><body style='margin:0;background:#0b0b0f;color:#ffffff;font-family:Arial,sans-serif;padding:28px;'>
      <div style='max-width:560px;margin:auto;background:#15151d;border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:28px;'>
        <h2 style='margin:0 0 12px;color:#ff8a1d;'>{$brand}</h2>
        <p>{$hello}</p>
        <p>Please verify your email address to activate your account.</p>
        <p style='margin:26px 0;'>
          <a href='{$verifyUrl}' style='background:#ff8a1d;color:#111;padding:14px 22px;border-radius:999px;text-decoration:none;font-weight:bold;display:inline-block;'>Verify Email</a>
        </p>
        <p style='font-size:13px;color:#aaa;'>This link expires in 24 hours.</p>
        <p style='font-size:12px;color:#777;word-break:break-all;'>If the button does not work, copy this link:<br>{$verifyUrl}</p>
      </div>
    </body></html>";
    return smtp_send_html($to, "Verify your {$brand} account", $html, $brand);
}

function send_email(string $to, string $subject, string $html): bool {
    return smtp_send_html($to, $subject, $html, SMTP_FROM_NAME);
}

function send_welcome_email(string $to, string $name = ''): bool {
    $safeName = htmlspecialchars($name ?: 'Explorer', ENT_QUOTES, 'UTF-8');
    $home = 'https://beyondimagination.co.technology/';
    $html = "<html><body style='margin:0;background:#08080d;color:#fff;font-family:Arial,sans-serif;padding:28px'><div style='max-width:580px;margin:auto;background:#15151f;border:1px solid #303044;border-radius:24px;padding:32px'><p style='color:#a5b4fc;font-weight:bold'>BEYOND OS 2.0</p><h1>Welcome, {$safeName}.</h1><p style='color:#c7c7d2;line-height:1.7'>Your Beyond ID is your key to the entire ecosystem. Your Beyond Wallet is ready with a shared bit$ balance for every app.</p><p style='margin:28px 0'><a href='{$home}' style='display:inline-block;background:#7c3aed;color:#fff;text-decoration:none;font-weight:bold;padding:14px 22px;border-radius:999px'>Explore Beyond OS</a></p><p style='color:#88889a;font-size:13px'>One ID. One wallet. Every Beyond experience.</p></div></body></html>";
    return smtp_send_html($to, 'Welcome to Beyond OS 2.0', $html, 'Beyond OS');
}
