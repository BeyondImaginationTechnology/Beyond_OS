<?php
declare(strict_types=1);

const DAILYBREATH_WEB_VERSION = '1.7';

function dailybreath_web_head(string $title = 'DailyBreath'): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return '<meta name="theme-color" content="#62558f">'
        . '<meta name="application-name" content="DailyBreath">'
        . '<meta name="apple-mobile-web-app-capable" content="yes">'
        . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
        . '<meta name="apple-mobile-web-app-title" content="DailyBreath">'
        . '<link rel="manifest" href="/dailybreath/manifest.webmanifest">'
        . '<link rel="apple-touch-icon" href="/dailybreath/assets/icons/dailybreath-mark-v2.png?v=20260901-2">'
        . '<link rel="icon" type="image/png" href="/dailybreath/assets/icons/dailybreath-mark-v2.png?v=20260901-2">'
        . '<link rel="stylesheet" href="/dailybreath/assets/css/web-app.css?v=' . DAILYBREATH_WEB_VERSION . '">'
        . '<meta property="og:title" content="' . $safeTitle . '">';
}

function dailybreath_web_scripts(): string
{
    return '<script src="/dailybreath/assets/js/web-app.js?v=' . DAILYBREATH_WEB_VERSION . '" defer></script>';
}

function dailybreath_ensure_web_tables(PDO $pdo): void
{
    $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dailybreath_challenge_progress (user_id INTEGER NOT NULL, challenge_key TEXT NOT NULL, completed_count INTEGER NOT NULL DEFAULT 0, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,challenge_key))');
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS dailybreath_challenge_progress (user_id BIGINT UNSIGNED NOT NULL, challenge_key VARCHAR(160) NOT NULL, completed_count TINYINT UNSIGNED NOT NULL DEFAULT 0, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY(user_id,challenge_key), KEY idx_dailybreath_challenge_updated(updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
