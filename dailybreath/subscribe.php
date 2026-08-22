<?php
require_once __DIR__ . '/../includes/ecosystem.php';

$returnToLogin = ($_POST['return_to'] ?? '') === 'dailybreath_login';
$returnToSettings = ($_POST['return_to'] ?? '') === 'settings';
$redirect = static function (string $status, string $message = '') use ($returnToLogin, $returnToSettings): never {
    if ($returnToLogin) {
        header('Location: ../beyond-id/auth/login.php?newsletter=' . $status);
    } elseif ($returnToSettings) {
        header('Location: settings.php?newsletter=' . $status . ($message !== '' ? '&message=' . urlencode($message) : ''));
    } else {
        header('Location: index.php?' . ($status === 'success' ? 'success=1' : 'error=' . urlencode($message)));
    }
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
beyond_require_csrf();

if (!empty($_POST['website'] ?? '')) {
    $redirect('success');
}

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $redirect('error', 'Please enter a valid email address.');
}

try {
    $pdo = beyond_db();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS dailybreath_subscribers (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NULL,email TEXT NOT NULL UNIQUE,source TEXT NOT NULL DEFAULT 'dailybreath_web',status TEXT NOT NULL DEFAULT 'active',ip_address TEXT NULL,user_agent TEXT NULL,created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $sql = "INSERT INTO dailybreath_subscribers (name,email,source,ip_address,user_agent) VALUES (?,?,'dailybreath_web',?,?) ON CONFLICT(email) DO UPDATE SET name=excluded.name,status='active'";
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS dailybreath_subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NULL,email VARCHAR(190) NOT NULL UNIQUE,source VARCHAR(80) DEFAULT 'dailybreath_web',status ENUM('active','unsubscribed') DEFAULT 'active',ip_address VARCHAR(45) NULL,user_agent VARCHAR(255) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $sql = "INSERT INTO dailybreath_subscribers (name,email,source,ip_address,user_agent) VALUES (?,?,'dailybreath_web',?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),status='active'";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name ?: null,
        $email,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);

    $subject = 'Welcome to DailyBreath';
    $message = "Welcome to DailyBreath!\n\nThanks for joining our faith-centered wellness and recovery-support newsletter.\n\nBreathe. Pray. Reflect.\n\nYou can unsubscribe at any time.";
    $headers = "From: DailyBreath <no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'dailybreath.app') . ">\r\n";
    @mail($email, $subject, $message, $headers);

    $redirect('success');
} catch (Throwable $e) {
    $redirect('error', 'Signup is temporarily unavailable. Please try again soon.');
}
