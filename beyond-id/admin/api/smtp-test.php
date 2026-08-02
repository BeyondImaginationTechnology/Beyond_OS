<?php
declare(strict_types=1);

require __DIR__ . '/../../includes/admin-check.php';
require_once __DIR__ . '/../../../config/mail.php';

header('Content-Type: application/json; charset=utf-8');

$to = strtolower(trim((string)($_POST['email'] ?? $_GET['email'] ?? '')));
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Valid email required.']);
    exit;
}

$sent = send_email(
    $to,
    'Beyond ID SMTP test',
    "<div style='font-family:Arial;padding:24px;background:#10101b;color:#fff'><h2>SMTP test</h2><p>If you received this, Beyond ID SMTP delivery is working.</p><p>Sent at " . htmlspecialchars(date('c'), ENT_QUOTES, 'UTF-8') . "</p></div>"
);

if (!$sent) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'SMTP send failed. Check the PHP error log for the provider response.']);
    exit;
}

echo json_encode(['ok' => true, 'message' => 'SMTP test sent.']);
