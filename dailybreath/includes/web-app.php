<?php
declare(strict_types=1);

const DAILYBREATH_WEB_VERSION = '2.3.0';

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

function dailybreath_ensure_content_table(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) return;
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS dailybreath_daily_content (id INTEGER PRIMARY KEY AUTOINCREMENT,publish_date TEXT NOT NULL,tradition TEXT NOT NULL,locale TEXT NOT NULL,passage TEXT NOT NULL,reference TEXT NOT NULL,reflection TEXT NOT NULL DEFAULT '',reader_book TEXT NOT NULL DEFAULT '',reader_chapter INTEGER NOT NULL DEFAULT 1,reader_verse INTEGER NOT NULL DEFAULT 1,theme TEXT NOT NULL DEFAULT 'seasonal',status TEXT NOT NULL DEFAULT 'draft',created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,published_at TEXT NULL,UNIQUE(publish_date,tradition,locale))");
        $initialized = true;
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS dailybreath_daily_content (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,publish_date DATE NOT NULL,tradition VARCHAR(16) NOT NULL,locale VARCHAR(5) NOT NULL,passage TEXT NOT NULL,reference VARCHAR(255) NOT NULL,reflection TEXT NOT NULL,reader_book VARCHAR(120) NOT NULL DEFAULT '',reader_chapter INT UNSIGNED NOT NULL DEFAULT 1,reader_verse INT UNSIGNED NOT NULL DEFAULT 1,theme VARCHAR(32) NOT NULL DEFAULT 'seasonal',status VARCHAR(16) NOT NULL DEFAULT 'draft',created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,published_at TIMESTAMP NULL,UNIQUE KEY uq_dailybreath_content_day(publish_date,tradition,locale),KEY idx_dailybreath_content_status(status,publish_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $initialized = true;
}

function dailybreath_published_content(PDO $pdo, string $date, string $tradition, string $locale): ?array
{
    dailybreath_ensure_content_table($pdo);
    $query = $pdo->prepare("SELECT * FROM dailybreath_daily_content WHERE publish_date=? AND tradition=? AND locale=? AND status='published' LIMIT 1");
    $query->execute([$date, $tradition, $locale]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}
