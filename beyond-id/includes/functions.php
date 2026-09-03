<?php
declare(strict_types=1);

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function log_activity(PDO $pdo, ?int $user_id, string $action): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        error_log('Activity log failed: ' . $e->getMessage());
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function beyond_rate_limit_client_ip(): string {
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function beyond_rate_limit_table(PDO $pdo): void {
    static $ready = [];
    $connection = spl_object_id($pdo);
    if (isset($ready[$connection])) return;
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec('CREATE TABLE IF NOT EXISTS auth_rate_limits (
            bucket_key TEXT PRIMARY KEY,
            attempts INTEGER NOT NULL DEFAULT 0,
            window_started_at INTEGER NOT NULL,
            blocked_until INTEGER NOT NULL DEFAULT 0,
            updated_at INTEGER NOT NULL
        )');
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS auth_rate_limits (
            bucket_key CHAR(64) PRIMARY KEY,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            window_started_at BIGINT UNSIGNED NOT NULL,
            blocked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
            updated_at BIGINT UNSIGNED NOT NULL,
            KEY idx_auth_rate_limits_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    $ready[$connection] = true;
}

function beyond_rate_limit_key(string $action, string $identity): string {
    return hash('sha256', strtolower(trim($action)) . '|' . beyond_rate_limit_client_ip() . '|' . strtolower(trim($identity)));
}

/** @return array{allowed:bool,retry_after:int} */
function beyond_rate_limit_consume(PDO $pdo, string $action, string $identity, int $limit, int $windowSeconds, int $blockSeconds): array {
    $limit = max(1, $limit);
    $windowSeconds = max(1, $windowSeconds);
    $blockSeconds = max(1, $blockSeconds);
    $now = time();
    $key = beyond_rate_limit_key($action, $identity);
    try {
        beyond_rate_limit_table($pdo);
        $query = $pdo->prepare('SELECT attempts,window_started_at,blocked_until FROM auth_rate_limits WHERE bucket_key=? LIMIT 1');
        $query->execute([$key]);
        $row = $query->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row && (int)$row['blocked_until'] > $now) {
            return ['allowed'=>false, 'retry_after'=>max(1, (int)$row['blocked_until'] - $now)];
        }
        $windowStarted = $row ? (int)$row['window_started_at'] : $now;
        $attempts = $row ? (int)$row['attempts'] : 0;
        if ($windowStarted <= $now - $windowSeconds) {
            $windowStarted = $now;
            $attempts = 0;
        }
        $attempts++;
        $blockedUntil = $attempts > $limit ? $now + $blockSeconds : 0;
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = 'INSERT INTO auth_rate_limits(bucket_key,attempts,window_started_at,blocked_until,updated_at) VALUES(?,?,?,?,?)
                    ON CONFLICT(bucket_key) DO UPDATE SET attempts=excluded.attempts,window_started_at=excluded.window_started_at,blocked_until=excluded.blocked_until,updated_at=excluded.updated_at';
        } else {
            $sql = 'INSERT INTO auth_rate_limits(bucket_key,attempts,window_started_at,blocked_until,updated_at) VALUES(?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE attempts=VALUES(attempts),window_started_at=VALUES(window_started_at),blocked_until=VALUES(blocked_until),updated_at=VALUES(updated_at)';
        }
        $pdo->prepare($sql)->execute([$key, $attempts, $windowStarted, $blockedUntil, $now]);
        if ($blockedUntil > 0) {
            error_log('Authentication throttle activated for action=' . $action . ' ip=' . beyond_rate_limit_client_ip());
            return ['allowed'=>false, 'retry_after'=>$blockSeconds];
        }
        if (random_int(1, 100) === 1) {
            $pdo->prepare('DELETE FROM auth_rate_limits WHERE updated_at<?')->execute([$now - 7 * 86400]);
        }
        return ['allowed'=>true, 'retry_after'=>0];
    } catch (Throwable $exception) {
        error_log('Authentication throttle unavailable: ' . $exception->getMessage());
        return ['allowed'=>true, 'retry_after'=>0];
    }
}

function beyond_rate_limit_clear(PDO $pdo, string $action, string $identity): void {
    try {
        beyond_rate_limit_table($pdo);
        $pdo->prepare('DELETE FROM auth_rate_limits WHERE bucket_key=?')->execute([beyond_rate_limit_key($action, $identity)]);
    } catch (Throwable $exception) {
        error_log('Authentication throttle cleanup failed: ' . $exception->getMessage());
    }
}

function safe_return_path(?string $path, string $fallback = '../dashboard/'): string {
    if (!$path || preg_match('/[\\x00-\\x1F\\x7F\\\\]/', $path)) return $fallback;
    $decoded = rawurldecode($path);
    if (!str_starts_with($decoded, '/') || str_starts_with($decoded, '//') || str_contains($decoded, '\\')) return $fallback;
    $parts = parse_url($decoded);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return $fallback;
    return $path;
}

function register_session(PDO $pdo, int $userId): void {
    try {
        $token = hash('sha256', session_id());
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + 30 * 86400);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = "INSERT INTO user_sessions
                (user_id,session_token_hash,ip_address,user_agent,last_seen_at,expires_at)
                VALUES (?,?,?,?,?,?)
                ON CONFLICT(user_id,session_token_hash) DO UPDATE SET
                ip_address=excluded.ip_address,user_agent=excluded.user_agent,last_seen_at=excluded.last_seen_at,expires_at=excluded.expires_at,revoked_at=NULL";
        } else {
            $sql = "INSERT INTO user_sessions
                (user_id,session_token_hash,ip_address,user_agent,last_seen_at,expires_at)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),last_seen_at=VALUES(last_seen_at),expires_at=VALUES(expires_at),revoked_at=NULL";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $token, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? 'Browser', 0, 500), $now, $expiresAt]);
    } catch (Throwable $e) {
        error_log('Session registry unavailable: ' . $e->getMessage());
    }
}

function touch_session(PDO $pdo, int $userId): void {
    try {
        $token = hash('sha256', session_id());
        $stmt = $pdo->prepare("UPDATE user_sessions SET last_seen_at=? WHERE user_id=? AND session_token_hash=? AND revoked_at IS NULL");
        $stmt->execute([date('Y-m-d H:i:s'), $userId, $token]);
    } catch (Throwable $e) {}
}

function create_notification(PDO $pdo, int $userId, string $title, string $body, ?string $url = null, string $type = 'system'): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id,type,title,body,action_url) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $type, $title, $body, $url]);
    } catch (Throwable $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
    }
}

function unread_notification_count(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND read_at IS NULL");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

function beyond_profile_slug(string $value): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    return substr($slug, 0, 40);
}

function beyond_app_catalog(): array {
    return [
        'beyond-id' => ['name' => 'Beyond ID', 'url' => '/beyond-id/dashboard/', 'mark' => 'ID', 'permissions' => ['profile:read', 'security:manage', 'notifications:read']],
        'beyond-math' => ['name' => 'Beyond Math', 'url' => '/beyond-math/', 'mark' => 'BM', 'permissions' => ['profile:read', 'progress:write', 'wallet:read']],
        'beyond-french' => ['name' => 'Beyond French', 'url' => '/beyond-french/', 'mark' => 'FR', 'permissions' => ['profile:read', 'progress:write']],
        'dailybreath' => ['name' => 'DailyBreath', 'url' => '/dailybreath/', 'mark' => 'DB', 'permissions' => ['profile:read', 'streaks:write']],
        'beyond-health' => ['name' => 'Beyond Health', 'url' => '/beyond-health/', 'mark' => 'H', 'permissions' => ['profile:read', 'wellness:write']],
        'beyond-tv' => ['name' => 'Beyond TV', 'url' => '/beyond-tv/', 'mark' => 'TV', 'permissions' => ['profile:read', 'watchlist:write']],
        'beyond-catering' => ['name' => 'Beyond Catering', 'url' => '/contact.php?topic=catering', 'mark' => 'CA', 'permissions' => ['profile:read', 'business:write']],
        'beyond-baby-names' => ['name' => 'Beyond Baby Names', 'url' => '/beyond-baby-names/', 'mark' => 'BN', 'permissions' => ['profile:read', 'favorites:write']],
        'beyond-tattoo' => ['name' => 'Beyond Tattoo', 'url' => '/beyond-tattoo/', 'mark' => 'TT', 'permissions' => ['profile:read', 'private-journal:write']],
        'beyond-space' => ['name' => 'Beyond Space', 'url' => '/beyond-space/', 'mark' => 'SP', 'permissions' => ['profile:read', 'progress:write']],
        'beyond-ancient' => ['name' => 'Beyond Ancient', 'url' => '/beyond-ancient/', 'mark' => 'AN', 'permissions' => ['profile:read', 'progress:write']],
        'beyond-market' => ['name' => 'Beyond Market', 'url' => '/beyond-market/', 'mark' => 'MK', 'permissions' => ['profile:read', 'wallet:read', 'commerce:write']],
        'beyond-sell' => ['name' => 'Beyond Sell', 'url' => '/beyond-sell/', 'mark' => 'SL', 'permissions' => ['profile:read', 'seller:write', 'wallet:read']],
    ];
}

function beyond_app_meta(string $slug): array {
    $catalog = beyond_app_catalog();
    return $catalog[$slug] ?? [
        'name' => ucwords(str_replace('-', ' ', $slug)),
        'url' => '/' . $slug . '/',
        'mark' => strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $slug), 0, 2) ?: 'AP'),
        'permissions' => ['profile:read'],
    ];
}

function beyond_badges_for_user(array $user, array $profile, array $academyBadges = []): array {
    $badges = [];
    if (!empty($user['email_verified'])) $badges[] = ['label' => 'Email verified', 'type' => 'verified'];
    if (!empty($profile['profile_completed_at'])) $badges[] = ['label' => 'Profile complete', 'type' => 'profile'];
    if (!empty($profile['creator_verified_at'])) $badges[] = ['label' => 'Creator verified', 'type' => 'creator'];
    if (!empty($profile['seller_verified_at'])) $badges[] = ['label' => 'Seller verified', 'type' => 'seller'];
    if (in_array(strtolower((string)($user['role'] ?? '')), ['admin', 'super_admin'], true)) $badges[] = ['label' => 'Beyond team', 'type' => 'team'];
    foreach ($academyBadges as $badge) {
        $badges[] = ['label' => (string)($badge['title'] ?? 'Learner certified'), 'type' => 'academy'];
    }
    return $badges;
}
