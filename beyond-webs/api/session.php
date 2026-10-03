<?php
declare(strict_types=1);

require_once __DIR__ . '/../../beyond-id/includes/session.php';
require_once __DIR__ . '/../../includes/ecosystem.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function webs_reply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function webs_profile_catalog(): array
{
    $catalog = json_decode((string)file_get_contents(__DIR__ . '/../machine-profiles.json'), true);
    return is_array($catalog['profiles'] ?? null) ? $catalog['profiles'] : [];
}

function webs_hourly_rates(): array
{
    $rates = json_decode((string)(getenv('BEYOND_WEBS_PROFILE_RATES_JSON') ?: '{}'), true);
    if (!is_array($rates)) return [];
    $valid = [];
    foreach ($rates as $profile => $rate) {
        if (!is_array($rate)) continue;
        $amount = filter_var($rate['amount'] ?? null, FILTER_VALIDATE_FLOAT);
        $currency = strtoupper(trim((string)($rate['currency'] ?? '')));
        if ($amount !== false && $amount > 0 && preg_match('/^[A-Z]{3}$/', $currency)) {
            $valid[(string)$profile] = ['amount' => number_format((float)$amount, 4, '.', ''), 'currency' => $currency];
        }
    }
    return $valid;
}

function webs_rate_label(?array $rate): ?string
{
    return $rate === null ? null : '$' . number_format((float)$rate['amount'], 2) . ' ' . $rate['currency'] . '/hour';
}

function webs_signed_json(string $url, array $payload): array
{
    if (!str_starts_with($url, 'https://')) throw new RuntimeException('Cloud provisioning endpoint must use HTTPS.');
    $secret = (string)getenv('BEYOND_WEBS_PROVISIONER_SECRET');
    if ($secret === '' || !function_exists('curl_init')) throw new RuntimeException('Cloud provisioning is not configured.');
    $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $timestamp = (string)time();
    $signature = hash_hmac('sha256', $timestamp . "\n" . $body, $secret);
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Cloud provisioning is unavailable.');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 270,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Beyond-Timestamp: ' . $timestamp,
            'X-Beyond-Signature: ' . $signature,
        ],
        CURLOPT_POSTFIELDS => $body,
    ]);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        error_log('Beyond Webs provisioner request failed status=' . $status . ' curl=' . $error);
        throw new RuntimeException('The VPS provider could not complete this session action.');
    }
    return $decoded;
}

function webs_fetch_request(PDO $pdo, int $userId): ?array
{
    $query = $pdo->prepare('SELECT user_id, request_id, flavour, plan, work_mode, status, requested_at, updated_at, provider_instance_name, provider_zone, provider_private_ip, session_runtime_seconds, hourly_rate_amount, hourly_rate_currency, usage_seconds, started_at, stopped_at FROM beyond_webs_requests WHERE user_id = ? LIMIT 1');
    $query->execute([$userId]);
    $request = $query->fetch(PDO::FETCH_ASSOC);
    return is_array($request) ? $request : null;
}

function webs_sync_runtime(PDO $pdo, array $request): array
{
    if (($request['status'] ?? '') !== 'running' || empty($request['started_at']) || (int)$request['session_runtime_seconds'] < 1) return $request;
    $started = strtotime((string)$request['started_at'] . ' UTC');
    if ($started === false) return $request;
    $now = time();
    if ($now < $started + (int)$request['session_runtime_seconds']) return $request;
    $used = max(0, min((int)$request['session_runtime_seconds'], $now - $started));
    $stoppedAt = gmdate('Y-m-d H:i:s', $started + $used);
    $pdo->prepare("UPDATE beyond_webs_requests SET status='stopped', usage_seconds=usage_seconds+?, stopped_at=?, provider_private_ip=NULL, updated_at=? WHERE user_id=? AND status='running'")->execute([$used, $stoppedAt, gmdate('Y-m-d H:i:s'), (int)$request['user_id']]);
    $request['status'] = 'stopped';
    $request['usage_seconds'] = (int)$request['usage_seconds'] + $used;
    $request['stopped_at'] = $stoppedAt;
    $request['provider_private_ip'] = null;
    return $request;
}

function webs_public_request(array $request, array $rates, bool $startEnabled): array
{
    $profile = (string)$request['plan'];
    $rate = !empty($request['hourly_rate_amount']) && !empty($request['hourly_rate_currency'])
        ? ['amount' => (string)$request['hourly_rate_amount'], 'currency' => (string)$request['hourly_rate_currency']]
        : ($rates[$profile] ?? null);
    $usage = (int)($request['usage_seconds'] ?? 0);
    if (($request['status'] ?? '') === 'running' && !empty($request['started_at'])) {
        $started = strtotime((string)$request['started_at'] . ' UTC');
        if ($started !== false) $usage += max(0, time() - $started);
    }
    $hours = $usage > 0 ? (int)ceil($usage / 3600) : 0;
    $request['hourly_rate'] = webs_rate_label($rate);
    $request['billable_hours_estimate'] = $hours;
    $request['usage_seconds_estimate'] = $usage;
    $request['session_access_ready'] = ($request['status'] ?? '') === 'running' && !empty($request['provider_private_ip']);
    $request['start_enabled'] = $startEnabled && $rate !== null;
    unset($request['user_id'], $request['provider_private_ip'], $request['provider_instance_name'], $request['provider_zone']);
    return $request;
}

if (empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] < 1) {
    webs_reply(401, ['error' => 'Sign in with Beyond ID to manage a VPS session.']);
}
$userId = (int)$_SESSION['user_id'];
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    webs_reply(405, ['error' => 'Method not allowed.']);
}

try {
    $pdo = beyond_db();
    if (!beyond_refresh_browser_identity($pdo) || (int)($_SESSION['user_id'] ?? 0) !== $userId) {
        webs_reply(401, ['error' => 'Your Beyond ID session has expired. Sign in again.']);
    }
    $rates = webs_hourly_rates();
    $startEnabled = getenv('BEYOND_WEBS_START_ENABLED') === 'true' && getenv('BEYOND_WEBS_BILLING_ENABLED') === 'true';
    $gatewaySecret = (string)getenv('BEYOND_WEBS_GATEWAY_TICKET_SECRET');
    $gatewayOrigin = rtrim((string)getenv('BEYOND_WEBS_GATEWAY_ORIGIN'), '/');

    if ($method === 'GET') {
        $request = webs_fetch_request($pdo, $userId);
        if ($request !== null) $request = webs_sync_runtime($pdo, $request);
        if ((string)($_GET['action'] ?? '') === 'access-ticket') {
            if ($request === null || $request['status'] !== 'running' || empty($request['provider_private_ip'])) {
                webs_reply(409, ['error' => 'There is no active VPS session to connect to.']);
            }
            if ($gatewaySecret === '' || $gatewayOrigin === '') webs_reply(503, ['error' => 'Browser access is not configured yet.']);
            $now = time();
            $sessionExpiry = strtotime((string)$request['started_at'] . ' UTC') + (int)$request['session_runtime_seconds'];
            $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
            $claims = rtrim(strtr(base64_encode(json_encode([
                'aud' => 'beyond-webs-gateway', 'sub' => $userId, 'rid' => $request['request_id'],
                'iat' => $now, 'exp' => min($now + 45, $sessionExpiry), 'session_exp' => $sessionExpiry,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
            $content = $header . '.' . $claims;
            $ticket = $content . '.' . rtrim(strtr(base64_encode(hash_hmac('sha256', $content, $gatewaySecret, true)), '+/', '-_'), '=');
            webs_reply(200, ['ticket' => $ticket, 'gateway_origin' => $gatewayOrigin]);
        }
        webs_reply(200, ['request' => $request === null ? null : webs_public_request($request, $rates, $startEnabled)]);
    }

    $contentType = strtolower(trim(explode(';', (string)($_SERVER['CONTENT_TYPE'] ?? ''), 2)[0]));
    if ($contentType !== 'application/json') webs_reply(415, ['error' => 'Send JSON to manage a session.']);
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || strlen($raw) > 4096) webs_reply(413, ['error' => 'The request is too large.']);
    $input = json_decode($raw, true);
    if (!is_array($input) || array_is_list($input)) webs_reply(400, ['error' => 'Invalid request.']);
    $csrf = $input['csrf'] ?? null;
    if (!is_string($csrf) || !is_string($_SESSION['beyond_webs_csrf'] ?? null) || !hash_equals($_SESSION['beyond_webs_csrf'], $csrf)) {
        webs_reply(403, ['error' => 'Refresh the page and try again.']);
    }

    $action = (string)($input['action'] ?? 'save');
    if ($action === 'save') {
        $flavour = $input['flavour'] ?? null;
        $plan = $input['plan'] ?? null;
        $workMode = $input['work_mode'] ?? null;
        $allowedProfiles = array_values(array_filter(array_map(static fn(array $profile): string => (string)($profile['id'] ?? ''), webs_profile_catalog())));
        if (!is_string($flavour) || !in_array($flavour, ['Home', 'Core', 'Creator', 'Academy', 'Cyber', 'Sentinel', 'Gaming'], true)
            || !is_string($plan) || !in_array($plan, $allowedProfiles, true)
            || !is_string($workMode) || !in_array($workMode, ['developer', 'creative', 'gaming'], true)) {
            webs_reply(422, ['error' => 'Choose a listed BIT OS flavour, machine profile, and work mode.']);
        }
        $existing = webs_fetch_request($pdo, $userId);
        if ($existing !== null && $existing['status'] !== 'requested') webs_reply(409, ['error' => 'This request has a VPS session. Stop it before changing its configuration.']);
        $requestId = $existing['request_id'] ?? bin2hex(random_bytes(16));
        $now = gmdate('Y-m-d H:i:s');
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'mysql'
            ? 'INSERT INTO beyond_webs_requests (user_id, request_id, flavour, plan, work_mode, status, requested_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE flavour = IF(status = \'requested\', VALUES(flavour), flavour), plan = IF(status = \'requested\', VALUES(plan), plan), work_mode = IF(status = \'requested\', VALUES(work_mode), work_mode), updated_at = IF(status = \'requested\', VALUES(updated_at), updated_at)'
            : 'INSERT INTO beyond_webs_requests (user_id, request_id, flavour, plan, work_mode, status, requested_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT(user_id) DO UPDATE SET flavour = CASE WHEN status = \'requested\' THEN excluded.flavour ELSE flavour END, plan = CASE WHEN status = \'requested\' THEN excluded.plan ELSE plan END, work_mode = CASE WHEN status = \'requested\' THEN excluded.work_mode ELSE work_mode END, updated_at = CASE WHEN status = \'requested\' THEN excluded.updated_at ELSE updated_at END';
        $pdo->prepare($sql)->execute([$userId, $requestId, $flavour, $plan, $workMode, 'requested', $now, $now]);
        $request = webs_fetch_request($pdo, $userId);
        webs_reply(200, ['request' => webs_public_request($request ?? [], $rates, $startEnabled)]);
    }

    $request = webs_fetch_request($pdo, $userId);
    if ($request === null) webs_reply(404, ['error' => 'Save a VPS request before starting a session.']);
    $now = gmdate('Y-m-d H:i:s');

    if ($action === 'start') {
        if (!$startEnabled) webs_reply(503, ['error' => 'VPS provisioning is disabled until the provider, rate, and billing settings are configured.']);
        $priorStatus = (string)$request['status'];
        if (!in_array($priorStatus, ['requested', 'stopped'], true)) webs_reply(409, ['error' => 'This VPS session cannot be started in its current state.']);
        $rate = $rates[(string)$request['plan']] ?? null;
        if ($priorStatus === 'stopped' && !empty($request['hourly_rate_amount']) && !empty($request['hourly_rate_currency'])) {
            $rate = ['amount' => (string)$request['hourly_rate_amount'], 'currency' => (string)$request['hourly_rate_currency']];
        }
        if ($rate === null) webs_reply(503, ['error' => 'A confirmed hourly rate is required for this machine profile.']);
        if (!is_string($input['accept_rate'] ?? null) || !hash_equals((string)webs_rate_label($rate), $input['accept_rate'])) {
            webs_reply(409, ['error' => 'Review and accept the current hourly rate before starting the session.']);
        }
        $claim = $pdo->prepare("UPDATE beyond_webs_requests SET status='provisioning', updated_at=? WHERE user_id=? AND status=?");
        $claim->execute([$now, $userId, $priorStatus]);
        if ($claim->rowCount() !== 1) webs_reply(409, ['error' => 'The VPS session changed. Refresh and try again.']);
        $provider = null;
        $base = rtrim((string)getenv('BEYOND_WEBS_PROVISIONER_URL'), '/');
        try {
            if ($base === '') throw new RuntimeException('Cloud provisioning is not configured.');
            $actionPath = $priorStatus === 'stopped' ? '/internal/resume' : '/internal/provision';
            $provider = webs_signed_json($base . $actionPath, [
                'user_id' => $userId, 'request_id' => $request['request_id'],
                'profile' => $request['plan'], 'flavour' => $request['flavour'], 'work_mode' => $request['work_mode'],
                'existing_instance_name' => $priorStatus === 'stopped' ? $request['provider_instance_name'] : null,
                'existing_zone' => $priorStatus === 'stopped' ? $request['provider_zone'] : null,
            ]);
            $privateIp = (string)($provider['private_ip'] ?? '');
            if (!filter_var($privateIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($privateIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('The provider did not return a private session address.');
            }
            $runtimeSeconds = max(1800, min(43200, (int)($provider['runtime_limit_seconds'] ?? 14400)));
            $sql = "UPDATE beyond_webs_requests SET status='running', provider_instance_name=?, provider_zone=?, provider_private_ip=?, session_runtime_seconds=?, hourly_rate_amount=?, hourly_rate_currency=?, started_at=?, stopped_at=NULL, updated_at=? WHERE user_id=? AND status='provisioning'";
            $updated = $pdo->prepare($sql);
            $updated->execute([
                (string)$provider['instance_name'], (string)$provider['zone'], $privateIp, $runtimeSeconds,
                $rate['amount'], $rate['currency'], $now, $now, $userId,
            ]);
            if ($updated->rowCount() !== 1) throw new RuntimeException('The session state could not be saved.');
        } catch (Throwable $exception) {
            if (is_array($provider) && !empty($provider['instance_name']) && !empty($provider['zone']) && $base !== '') {
                try {
                    $cleanupAction = $priorStatus === 'stopped' ? '/internal/stop' : '/internal/delete';
                    webs_signed_json($base . $cleanupAction, [
                        'user_id' => $userId, 'request_id' => $request['request_id'],
                        'instance_name' => $provider['instance_name'], 'zone' => $provider['zone'],
                    ]);
                } catch (Throwable $cleanupException) {
                    error_log('Beyond Webs orphan session cleanup failed for request ' . (string)$request['request_id']);
                }
            }
            $pdo->prepare('UPDATE beyond_webs_requests SET status=?, updated_at=? WHERE user_id=? AND status=\'provisioning\'')->execute([$priorStatus, gmdate('Y-m-d H:i:s'), $userId]);
            error_log('Beyond Webs start failed for request ' . (string)$request['request_id'] . ': ' . $exception->getMessage());
            webs_reply(503, ['error' => $exception instanceof RuntimeException ? $exception->getMessage() : 'The VPS session could not be started.']);
        }
        $request = webs_fetch_request($pdo, $userId);
        webs_reply(200, ['request' => webs_public_request($request ?? [], $rates, $startEnabled)]);
    }

    if ($action === 'stop') {
        if ((string)$request['status'] !== 'running') webs_reply(409, ['error' => 'There is no running VPS session to stop.']);
        $base = rtrim((string)getenv('BEYOND_WEBS_PROVISIONER_URL'), '/');
        if ($base === '') webs_reply(503, ['error' => 'Cloud provisioning is not configured.']);
        try {
            webs_signed_json($base . '/internal/stop', [
                'user_id' => $userId, 'request_id' => $request['request_id'],
                'instance_name' => $request['provider_instance_name'], 'zone' => $request['provider_zone'],
            ]);
            $started = strtotime((string)$request['started_at'] . ' UTC');
            $used = $started === false ? 0 : max(0, time() - $started);
            $pdo->prepare("UPDATE beyond_webs_requests SET status='stopped', usage_seconds=usage_seconds+?, stopped_at=?, provider_private_ip=NULL, updated_at=? WHERE user_id=? AND status='running'")->execute([$used, $now, $now, $userId]);
        } catch (Throwable $exception) {
            error_log('Beyond Webs stop failed for request ' . (string)$request['request_id'] . ': ' . $exception->getMessage());
            webs_reply(503, ['error' => 'The VPS provider could not stop this session.']);
        }
        $request = webs_fetch_request($pdo, $userId);
        webs_reply(200, ['request' => webs_public_request($request ?? [], $rates, $startEnabled)]);
    }

    webs_reply(422, ['error' => 'Unsupported session action.']);
} catch (Throwable $exception) {
    error_log('Beyond Webs request unavailable: ' . $exception->getMessage());
    webs_reply(503, ['error' => 'Session requests are temporarily unavailable. Please try again later.']);
}
