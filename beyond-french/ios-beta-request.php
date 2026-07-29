<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ios-beta.php');
    exit;
}
if (!french_verify_csrf()) {
    http_response_code(419);
    exit('Session expired. Please return and try again.');
}
if (trim((string)($_POST['website'] ?? '')) !== '') {
    header('Location: thank-you.php?product=ios&status=new');
    exit;
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
    header('Location: ios-beta.php?error=email#join-beta');
    exit;
}

$status = 'new';
try {
    $now = date(DATE_ATOM);
    $statement = sqlite_db()->prepare('INSERT INTO french_subscribers(id,name,email,preferred_language,consent_at,created_at) VALUES(?,?,?,?,?,?)');
    $statement->execute([bin2hex(random_bytes(8)), 'iOS Beta Request', $email, 'French', $now, $now]);
} catch (PDOException $error) {
    if ((string)$error->getCode() === '23000' || str_contains($error->getMessage(), 'UNIQUE')) {
        $status = 'existing';
    } else {
        error_log('Beyond French iOS beta signup save failed: ' . $error->getMessage());
        header('Location: ios-beta.php?error=save#join-beta');
        exit;
    }
}

if ($status === 'new') {
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeTime = htmlspecialchars(date('F j, Y \a\t g:i A T'), ENT_QUOTES, 'UTF-8');
    $html = '<div style="font-family:Arial,sans-serif;padding:28px;background:#07152f;color:#fff">'
        . '<h2 style="margin-top:0">New Beyond French iOS beta request</h2>'
        . '<p><strong>Email:</strong> <a style="color:#8fc0ff" href="mailto:' . $safeEmail . '">' . $safeEmail . '</a></p>'
        . '<p><strong>Requested:</strong> ' . $safeTime . '</p>'
        . '<p style="color:#bdc8dc">Source: Beyond French 2.0 iOS beta landing page</p>'
        . '</div>';
    if (!smtp_send_html('admin@beyondimagination.co.technology', 'New Beyond French iOS beta request', $html, 'Beyond French')) {
        error_log('Beyond French iOS beta admin notification could not be sent for ' . $email);
    }
}

header('Location: thank-you.php?product=ios&status=' . rawurlencode($status));
exit;
