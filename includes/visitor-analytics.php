<?php
declare(strict_types=1);

/**
 * Privacy-conscious first-party visitor traffic helpers.
 * IP addresses are retained briefly for the private admin view. Full
 * user-agent strings, URL queries, and referrer queries are never stored.
 */

function beyond_analytics_private_root(): string
{
    if (function_exists('beyond_private_root')) {
        return beyond_private_root();
    }
    $configured = getenv('BEYOND_VAR_PATH');
    if (is_string($configured) && trim($configured) !== '') {
        return rtrim($configured, DIRECTORY_SEPARATOR);
    }
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var';
}

function beyond_analytics_hash_key(): string
{
    static $key = null;
    if (is_string($key) && $key !== '') return $key;

    $directory = beyond_analytics_private_root() . DIRECTORY_SEPARATOR . 'analytics';
    $file = $directory . DIRECTORY_SEPARATOR . 'visitor-hash.key';
    try {
        if (is_file($file)) {
            $loaded = trim((string)file_get_contents($file));
            if (strlen($loaded) >= 32) return $key = $loaded;
        }
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create private analytics directory.');
        }
        $generated = bin2hex(random_bytes(32));
        if (file_put_contents($file, $generated, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write analytics hash key.');
        }
        @chmod($file, 0640);
        return $key = $generated;
    } catch (Throwable $exception) {
        error_log('Analytics private key fallback: ' . $exception->getMessage());
        return $key = hash('sha256', __FILE__ . '|' . php_uname('n'));
    }
}


function beyond_analytics_limit(string $value, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}

function beyond_analytics_client_ip(): ?string
{
    // These headers are set by the supported edge proxies. Generic
    // X-Forwarded-For is intentionally ignored because clients can spoof it.
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];
    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate === '' || filter_var($candidate, FILTER_VALIDATE_IP) === false) continue;
        $packed = @inet_pton($candidate);
        return $packed === false ? $candidate : (inet_ntop($packed) ?: $candidate);
    }
    return null;
}

function beyond_analytics_hash(string $value): string
{
    return hash_hmac('sha256', $value, beyond_analytics_hash_key());
}

function beyond_analytics_current_visitor_hash(): ?string
{
    $visitorId = (string)($_COOKIE['beyond_visitor_id'] ?? '');
    if (!preg_match('/^[a-f0-9]{32,64}$/', $visitorId)) return null;
    return beyond_analytics_hash('visitor|' . $visitorId);
}

function beyond_analytics_cookie(string $name, int $ttl): string
{
    $value = isset($_COOKIE[$name]) && preg_match('/^[a-f0-9]{32,64}$/', (string)$_COOKIE[$name])
        ? (string)$_COOKIE[$name]
        : bin2hex(random_bytes(24));
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    setcookie($name, $value, [
        'expires' => time() + $ttl,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
    return $value;
}

function beyond_analytics_clean_path(?string $path): string
{
    $path = is_string($path) ? trim($path) : '/';
    if ($path === '' || $path[0] !== '/') $path = '/';
    $clean = parse_url($path, PHP_URL_PATH);
    if (!is_string($clean) || $clean === '') $clean = '/';
    $clean = preg_replace('#/+#', '/', $clean) ?: '/';
    return beyond_analytics_limit($clean, 500);
}

function beyond_analytics_should_track_path(string $path): bool
{
    $lower = strtolower($path);
    $blockedPrefixes = [
        '/api/', '/server/admin/', '/beyond-id/admin/', '/beyond-french/admin/',
        '/dailybreath/admin/', '/admin/', '/assets/', '/sql/', '/tools/', '/docs/',
    ];
    foreach ($blockedPrefixes as $prefix) {
        if (str_starts_with($lower, $prefix)) return false;
    }
    return !preg_match('/\.(?:css|js|map|json|xml|txt|sql|zip|pdf|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|mp3|mp4|webm)$/i', $lower);
}

function beyond_analytics_app_slug(string $path): string
{
    $segment = strtolower(trim(explode('/', trim($path, '/'))[0] ?? ''));
    if ($segment === '') return 'beyond-os';
    $aliases = [
        'dailybreath' => 'daily-breath',
        'dashboard' => 'beyond-id',
        'academy' => 'beyond-learn',
        'coding-school' => 'beyond-code',
        'app-store' => 'app-store',
    ];
    return beyond_analytics_limit($aliases[$segment] ?? $segment, 80);
}

function beyond_analytics_client_profile(string $userAgent, int $screenWidth = 0): array
{
    $ua = strtolower($userAgent);
    $isBot = preg_match('/bot|crawler|spider|slurp|headless|preview|facebookexternalhit|uptimerobot|monitoring/i', $userAgent) === 1;
    $device = 'desktop';
    if (preg_match('/ipad|tablet|kindle|silk/i', $userAgent)) $device = 'tablet';
    elseif (preg_match('/mobile|iphone|ipod|android/i', $userAgent) || ($screenWidth > 0 && $screenWidth < 760)) $device = 'mobile';

    $browser = 'Other';
    if (str_contains($ua, 'edg/')) $browser = 'Edge';
    elseif (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) $browser = 'Opera';
    elseif (str_contains($ua, 'firefox/')) $browser = 'Firefox';
    elseif (str_contains($ua, 'chrome/') || str_contains($ua, 'crios/')) $browser = 'Chrome';
    elseif (str_contains($ua, 'safari/')) $browser = 'Safari';

    $os = 'Other';
    if (str_contains($ua, 'windows')) $os = 'Windows';
    elseif (str_contains($ua, 'android')) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/', $ua)) $os = 'iOS';
    elseif (str_contains($ua, 'mac os')) $os = 'macOS';
    elseif (str_contains($ua, 'linux')) $os = 'Linux';

    return ['is_bot' => $isBot, 'device' => $device, 'browser' => $browser, 'os' => $os];
}

function beyond_analytics_referrer(?string $referrer): array
{
    if (!is_string($referrer) || trim($referrer) === '') return [null, null];
    $parts = parse_url($referrer);
    if (!is_array($parts)) return [null, null];
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host === '') return [null, null];
    $currentHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')) ?? '');
    if ($host === $currentHost || ('www.' . $host) === $currentHost || $host === ('www.' . $currentHost)) {
        return ['internal', beyond_analytics_clean_path((string)($parts['path'] ?? '/'))];
    }
    return [beyond_analytics_limit($host, 190), null];
}

function beyond_analytics_vancouver_bounds(int $days = 1, int $offsetDays = 0): array
{
    $tz = new DateTimeZone('America/Vancouver');
    $utc = new DateTimeZone('UTC');
    $end = new DateTimeImmutable('tomorrow', $tz);
    if ($offsetDays !== 0) $end = $end->modify(($offsetDays > 0 ? '+' : '') . $offsetDays . ' days');
    $start = $end->modify('-' . max(1, $days) . ' days');
    return [$start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s')];
}

function beyond_analytics_count(PDO $pdo, string $expression, string $startUtc, string $endUtc, string $extra = '', array $params = []): int
{
    $sql = "SELECT {$expression} FROM visitor_traffic WHERE occurred_at>=? AND occurred_at<? {$extra}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$startUtc, $endUtc], $params));
    return (int)$stmt->fetchColumn();
}

function beyond_analytics_summary(PDO $pdo, int $days): array
{
    [$todayStart, $todayEnd] = beyond_analytics_vancouver_bounds(1);
    [$rangeStart, $rangeEnd] = beyond_analytics_vancouver_bounds($days);
    $views = beyond_analytics_count($pdo, 'COUNT(*)', $rangeStart, $rangeEnd);
    $sessions = beyond_analytics_count($pdo, 'COUNT(DISTINCT session_hash)', $rangeStart, $rangeEnd);
    $activeStmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT session_hash) FROM visitor_traffic
         WHERE COALESCE(last_seen_at, occurred_at)>=?'
    );
    $activeStmt->execute([gmdate('Y-m-d H:i:s', time() - 5 * 60)]);
    return [
        'active_now' => (int)$activeStmt->fetchColumn(),
        'today_views' => beyond_analytics_count($pdo, 'COUNT(*)', $todayStart, $todayEnd),
        'today_visitors' => beyond_analytics_count($pdo, 'COUNT(DISTINCT visitor_hash)', $todayStart, $todayEnd),
        'range_views' => $views,
        'range_visitors' => beyond_analytics_count($pdo, 'COUNT(DISTINCT visitor_hash)', $rangeStart, $rangeEnd),
        'range_sessions' => $sessions,
        'signed_in_views' => beyond_analytics_count($pdo, 'COUNT(*)', $rangeStart, $rangeEnd, 'AND user_id IS NOT NULL'),
        'pages_per_session' => $sessions > 0 ? round($views / $sessions, 1) : 0.0,
        'start_utc' => $rangeStart,
        'end_utc' => $rangeEnd,
    ];
}

function beyond_analytics_daily_trend(PDO $pdo, int $days): array
{
    $days = max(1, min(30, $days));
    $tz = new DateTimeZone('America/Vancouver');
    $utc = new DateTimeZone('UTC');
    $today = new DateTimeImmutable('today', $tz);
    $rows = [];
    $stmt = $pdo->prepare('SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors FROM visitor_traffic WHERE occurred_at>=? AND occurred_at<?');
    for ($i = $days - 1; $i >= 0; $i--) {
        $start = $today->modify('-' . $i . ' days');
        $end = $start->modify('+1 day');
        $stmt->execute([$start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s')]);
        $metric = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['views' => 0, 'visitors' => 0];
        $rows[] = [
            'date' => $start->format('Y-m-d'),
            'label' => $start->format($days <= 14 ? 'D' : 'M j'),
            'views' => (int)$metric['views'],
            'visitors' => (int)$metric['visitors'],
        ];
    }
    return $rows;
}

function beyond_analytics_grouped(PDO $pdo, string $column, string $startUtc, string $endUtc, int $limit = 10, string $where = ''): array
{
    $allowed = ['path', 'app_slug', 'referrer_host', 'device_type', 'browser', 'operating_system'];
    if (!in_array($column, $allowed, true)) return [];
    $limit = max(1, min(25, $limit));
    $sql = "SELECT {$column} AS label, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
            FROM visitor_traffic
            WHERE occurred_at>=? AND occurred_at<? {$where}
            GROUP BY {$column}
            ORDER BY views DESC
            LIMIT {$limit}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startUtc, $endUtc]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
