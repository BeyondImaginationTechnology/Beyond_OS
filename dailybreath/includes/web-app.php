<?php
declare(strict_types=1);

const DAILYBREATH_WEB_VERSION = '2.0';

function dailybreath_web_locale(): string
{
    $requested = strtolower((string)($_GET['lang'] ?? $_COOKIE['dailybreath_language'] ?? $_SESSION['dailybreath_language'] ?? 'en'));
    $locale = in_array($requested, ['en','fr','es'], true) ? $requested : 'en';
    $_SESSION['dailybreath_language'] = $locale;
    $_SESSION['locale'] = $locale;
    if (isset($_GET['lang'])) setcookie('dailybreath_language', $locale, ['expires'=>time()+31536000,'path'=>'/dailybreath/','samesite'=>'Lax']);
    return $locale;
}

dailybreath_web_locale();

function dailybreath_web_date(?string $date = null): string
{
    $time = $date ? strtotime($date) : time();
    $locale = dailybreath_web_locale();
    if ($locale === 'fr') {
        $days = [1=>'lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];
        $months = [1=>'janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        return $days[(int)date('N',$time)].' '.date('j',$time).' '.$months[(int)date('n',$time)].' '.date('Y',$time);
    }
    if ($locale === 'es') {
        $days = [1=>'lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
        $months = [1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return $days[(int)date('N',$time)].', '.date('j',$time).' de '.$months[(int)date('n',$time)].' de '.date('Y',$time);
    }
    return date('l, F j, Y', $time);
}

function dailybreath_web_head(string $title = 'Daily Breath'): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    return '<meta name="theme-color" content="#173f2c">'
        . '<meta name="application-name" content="Daily Breath">'
        . '<meta name="apple-mobile-web-app-capable" content="yes">'
        . '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
        . '<meta name="apple-mobile-web-app-title" content="Daily Breath">'
        . '<link rel="manifest" href="/dailybreath/manifest.webmanifest">'
        . '<link rel="apple-touch-icon" href="/dailybreath/assets/icons/dailybreath-mark-v2.png?v=20260901-2">'
        . '<link rel="icon" type="image/png" href="/dailybreath/assets/icons/dailybreath-mark-v2.png?v=20260901-2">'
        . '<link rel="stylesheet" href="/dailybreath/assets/css/web-app.css?v=' . DAILYBREATH_WEB_VERSION . '">'
        . '<meta property="og:title" content="' . $safeTitle . '">';
}

function dailybreath_web_scripts(): string
{
    return '<script src="/dailybreath/assets/js/locales.js?v=' . DAILYBREATH_WEB_VERSION . '" defer></script>'
        . '<script src="/dailybreath/assets/js/web-app.js?v=' . DAILYBREATH_WEB_VERSION . '" defer></script>';
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
