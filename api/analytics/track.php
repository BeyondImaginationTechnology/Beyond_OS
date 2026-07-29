<?php
declare(strict_types=1);

header('Cache-Control: no-store, max-age=0');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo '{"ok":false}';
    exit;
}
if (($_SERVER['HTTP_DNT'] ?? '') === '1') {
    http_response_code(204);
    exit;
}
$fetchSite = strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite === 'cross-site') {
    http_response_code(403);
    echo '{"ok":false}';
    exit;
}
$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '') {
    $originHost = strtolower((string)(parse_url($origin, PHP_URL_HOST) ?? ''));
    $requestHost = strtolower((string)preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($originHost === '' || $requestHost === '' || $originHost !== $requestHost) {
        http_response_code(403);
        echo '{"ok":false}';
        exit;
    }
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) {
    http_response_code(413);
    echo '{"ok":false}';
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/ecosystem.php';
require_once dirname(__DIR__, 2) . '/includes/visitor-analytics.php';
require dirname(__DIR__, 2) . '/beyond-id/includes/db.php';

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$path = beyond_analytics_clean_path((string)($data['path'] ?? '/'));
if (!beyond_analytics_should_track_path($path)) {
    http_response_code(204);
    exit;
}

$viewportWidth = max(0, min(10000, (int)($data['viewport_width'] ?? $data['screen_width'] ?? 0)));
$userAgent = beyond_analytics_limit((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 800);
$profile = beyond_analytics_client_profile($userAgent, $viewportWidth);
if ($profile['is_bot']) {
    http_response_code(204);
    exit;
}

$eventType = (string)($data['event_type'] ?? 'page_view');
if (!in_array($eventType, ['page_view', 'heartbeat'], true)) $eventType = 'page_view';
$visitorId = beyond_analytics_cookie('beyond_visitor_id', 400 * 86400);
$sessionId = beyond_analytics_cookie('beyond_visit_session', 30 * 60);
$visitorHash = beyond_analytics_hash('visitor|' . $visitorId);
$sessionHash = beyond_analytics_hash('session|' . $sessionId);
$ipAddress = beyond_analytics_client_ip();
[$referrerHost, $referrerPath] = beyond_analytics_referrer((string)($data['referrer'] ?? ''));
$country = strtoupper((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['HTTP_X_VERCEL_IP_COUNTRY'] ?? ''));
if (!preg_match('/^[A-Z]{2}$/', $country)) $country = null;
$language = preg_replace('/[^A-Za-z0-9,_-]/', '', (string)($data['language'] ?? '')) ?: null;
if ($language !== null) $language = beyond_analytics_limit($language, 32);
$clientTimezone = preg_replace('/[^A-Za-z0-9_+\\/-]/', '', (string)($data['client_timezone'] ?? '')) ?: null;
if ($clientTimezone !== null) $clientTimezone = beyond_analytics_limit($clientTimezone, 80);
$title = trim(strip_tags((string)($data['title'] ?? '')));
$title = $title === '' ? null : beyond_analytics_limit($title, 255);
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if ($userId !== null && $userId < 1) $userId = null;
$nowUtc = gmdate('Y-m-d H:i:s');

try {
    if ($eventType === 'heartbeat') {
        $existing = $pdo->prepare(
            'SELECT id FROM visitor_traffic WHERE session_hash=? AND path=? ORDER BY occurred_at DESC LIMIT 1'
        );
        $existing->execute([$sessionHash, $path]);
    } else {
        $existing = $pdo->prepare(
            'SELECT id FROM visitor_traffic
             WHERE session_hash=? AND path=? AND occurred_at>=?
             ORDER BY occurred_at DESC LIMIT 1'
        );
        $existing->execute([$sessionHash, $path, gmdate('Y-m-d H:i:s', time() - 2)]);
    }
    $existingId = (int)($existing->fetchColumn() ?: 0);

    if ($existingId > 0) {
        $update = $pdo->prepare(
            'UPDATE visitor_traffic
             SET last_seen_at=?, ip_address=COALESCE(?,ip_address),
                 viewport_width=CASE WHEN ?>0 THEN ? ELSE viewport_width END,
                 client_language=COALESCE(?,client_language),
                 client_timezone=COALESCE(?,client_timezone),
                 country_code=COALESCE(?,country_code),
                 user_id=COALESCE(?,user_id)
             WHERE id=?'
        );
        $update->execute([
            $nowUtc, $ipAddress, $viewportWidth, $viewportWidth, $language, $clientTimezone,
            $country, $userId, $existingId,
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO visitor_traffic
            (event_type,path,page_title,app_slug,visitor_hash,session_hash,user_id,referrer_host,referrer_path,
             device_type,browser,operating_system,country_code,ip_address,viewport_width,client_language,
             client_timezone,occurred_at,last_seen_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            'page_view', $path, $title, beyond_analytics_app_slug($path), $visitorHash, $sessionHash,
            $userId, $referrerHost, $referrerPath, $profile['device'], $profile['browser'], $profile['os'],
            $country, $ipAddress, $viewportWidth ?: null, $language, $clientTimezone, $nowUtc, $nowUtc,
        ]);
    }
    $anonymize = $pdo->prepare(
        'UPDATE visitor_traffic SET ip_address=NULL WHERE ip_address IS NOT NULL AND occurred_at<?'
    );
    $anonymize->execute([gmdate('Y-m-d H:i:s', time() - 30 * 86400)]);
    if (random_int(1, 50) === 1) {
        $prune = $pdo->prepare('DELETE FROM visitor_traffic WHERE occurred_at<?');
        $prune->execute([gmdate('Y-m-d H:i:s', time() - 400 * 86400)]);
    }
} catch (Throwable $exception) {
    error_log('Visitor analytics capture failed: ' . $exception->getMessage());
}

http_response_code(204);
