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

function smtp_command($socket, string $command, array $okCodes, string $label = ''): string {
    if ($command !== '') fwrite($socket, $command . "\r\n");
    $response = smtp_read_line($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $okCodes, true)) {
        throw new Exception('SMTP error after [' . ($label !== '' ? $label : $command) . ']: ' . trim($response));
    }
    return $response;
}

function smtp_configuration_issue(): string {
    if (trim(SMTP_HOST) === '') return 'host is missing';
    if (SMTP_PORT < 1 || SMTP_PORT > 65535) return 'port is invalid';
    if (!in_array(strtolower(trim(SMTP_SECURE)), ['', 'ssl', 'tls'], true)) return 'secure mode must be ssl, tls, or empty';
    if (trim(SMTP_USER) === '') return 'user is missing';
    if (SMTP_PASS === 'PASTE_EMAIL_PASSWORD_HERE' || SMTP_PASS === '') return 'password is missing';
    if (!filter_var(SMTP_FROM, FILTER_VALIDATE_EMAIL)) return 'from address is invalid';
    if (trim(SMTP_REPLY_TO) !== '' && !filter_var(SMTP_REPLY_TO, FILTER_VALIDATE_EMAIL)) return 'reply-to address is invalid';
    return '';
}

function smtp_send_html(string $to, string $subject, string $html, string $fromName = SMTP_FROM_NAME): bool {
    $configurationIssue = smtp_configuration_issue();
    if ($configurationIssue !== '') {
        error_log('SMTP configuration invalid: ' . $configurationIssue);
        return false;
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('SMTP recipient address is invalid.');
        return false;
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $secureMode = strtolower(trim(SMTP_SECURE));
    $remote = ($secureMode === 'ssl') ? "ssl://{$host}" : $host;

    $socket = @fsockopen($remote, $port, $errno, $errstr, 20);
    if (!$socket) {
        error_log("SMTP connection failed: {$errno} {$errstr}");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_command($socket, '', [220]);
        smtp_command($socket, 'EHLO beyondimagination.co.technology', [250]);

        if ($secureMode === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('SMTP STARTTLS negotiation failed.');
            }
            smtp_command($socket, 'EHLO beyondimagination.co.technology', [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode(SMTP_USER), [334], 'AUTH username');
        smtp_command($socket, base64_encode(SMTP_PASS), [235], 'AUTH password');

        smtp_command($socket, 'MAIL FROM:<' . SMTP_FROM . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);

        $subject = trim((string)preg_replace('/[\r\n]+/', ' ', $subject));
        $fromName = trim((string)preg_replace('/[\r\n]+/', ' ', $fromName));
        $safeSubject = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($subject, 'UTF-8')
            : '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . SMTP_FROM . '>';
        if (trim(SMTP_REPLY_TO) !== '') $headers[] = 'Reply-To: ' . SMTP_REPLY_TO;
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $safeSubject;
        $headers[] = 'Date: ' . date(DATE_RFC2822);
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@beyondimagination.co.technology>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = 'X-Mailer: Beyond ID SMTP';

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

function beyond_verify_url(string $token, string $app = 'beyond_id', string $returnTo = ''): string {
    $baseUrl = 'https://beyondimagination.co.technology';
    if ($app === 'catering') {
        $url = $baseUrl . '/beyond-catering/auth/verify-email.php?token=' . urlencode($token);
    } else {
        $url = $baseUrl . '/beyond-id/auth/verify-email.php?token=' . urlencode($token);
    }
    return $returnTo !== '' ? $url . '&return=' . rawurlencode($returnTo) : $url;
}

function send_verification_email(string $to, string $token, string $app = 'beyond_id', string $name = '', string $returnTo = ''): bool {
    $brand = ($app === 'catering') ? 'Beyond Catering' : 'Beyond ID';
    $verifyUrl = beyond_verify_url($token, $app, $returnTo);
    $safeVerifyUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
    $hello = $name ? 'Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',' : 'Hi,';
    $html = "
    <html><body style='margin:0;background:#0b0b0f;color:#ffffff;font-family:Arial,sans-serif;padding:28px;'>
      <div style='max-width:560px;margin:auto;background:#15151d;border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:28px;'>
        <h2 style='margin:0 0 12px;color:#ff8a1d;'>{$brand}</h2>
        <p>{$hello}</p>
        <p>Please verify your email address to activate your account.</p>
        " . ($app === 'beyond_id' ? "<p>Your Beyond ID connects your profile and sign-in across BIT OS and Beyond apps. After verification, you can sign in and continue to the app you came from.</p>" : "") . "
        <p style='margin:26px 0;'>
          <a href='{$safeVerifyUrl}' style='background:#ff8a1d;color:#111;padding:14px 22px;border-radius:999px;text-decoration:none;font-weight:bold;display:inline-block;'>Verify Email</a>
        </p>
        <p style='font-size:13px;color:#aaa;'>This link expires in 24 hours.</p>
        <p style='font-size:12px;color:#777;word-break:break-all;'>If the button does not work, copy this link:<br>{$safeVerifyUrl}</p>
      </div>
    </body></html>";
    return smtp_send_html($to, "Verify your {$brand} account", $html, $brand);
}

function send_email(string $to, string $subject, string $html): bool {
    return smtp_send_html($to, $subject, $html, SMTP_FROM_NAME);
}

function send_welcome_email(string $to, string $name = ''): bool {
    $safeName = htmlspecialchars($name ?: 'Explorer', ENT_QUOTES, 'UTF-8');
    $home = 'https://beyondimagination.co.technology/beyond-id/dashboard/';
    $html = "<html><body style='margin:0;background:#08080d;color:#fff;font-family:Arial,sans-serif;padding:28px'><div style='max-width:580px;margin:auto;background:#15151f;border:1px solid #303044;border-radius:24px;padding:32px'><p style='color:#a5b4fc;font-weight:bold'>BEYOND ID</p><h1>Welcome, {$safeName}.</h1><p style='color:#c7c7d2;line-height:1.7'>Your Beyond ID connects your profile and sign-in across BIT OS and Beyond apps. Manage your profile, connected apps, and security from your account dashboard.</p><p style='margin:28px 0'><a href='{$home}' style='display:inline-block;background:#7c3aed;color:#fff;text-decoration:none;font-weight:bold;padding:14px 22px;border-radius:999px'>Open your Beyond ID</a></p><p style='color:#88889a;font-size:13px'>One account for BIT OS and Beyond apps.</p></div></body></html>";
    return smtp_send_html($to, 'Welcome to Beyond ID', $html, 'Beyond ID');
}
