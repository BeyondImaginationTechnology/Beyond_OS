<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mobile-auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

$scheme = strtolower(trim((string)($_GET['scheme'] ?? '')));
$client = beyond_api_client_for_scheme($scheme);
$userId = (int)($_SESSION['user_id'] ?? 0);
$challenge = trim((string)($_GET['code_challenge'] ?? ''));
$callback = static function (string $scheme, string $key, string $value): never {
    header('Location: ' . $scheme . '://auth?' . http_build_query([$key => $value], '', '&', PHP_QUERY_RFC3986));
    exit;
};

if ($client === []) {
    http_response_code(400);
    exit('This app is not registered with Beyond ID.');
}
if ($userId <= 0) {
    $callback($scheme, 'error', 'Beyond ID sign in was not completed.');
}
if (!preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge)) {
    $callback($scheme, 'error', 'This app must be updated to use secure sign-in.');
}

$audience = (string)$client['audience'];
$appSlug = (string)($client['app_slug'] ?? '');
$appName = (string)($client['name'] ?? 'Beyond app');
$requestedScopes = beyond_mobile_scopes($audience);
$scopeDescriptions = [
    'profile:read' => ['Profile details', 'Read your display name and basic profile information.'],
    'email:read' => ['Email address', 'Read the email address on your Beyond ID.'],
    'wallet:read' => ['Beyond Wallet balance', 'Read your current Beyond Wallet rewards balance.'],
    'watchlist:write' => ['Watchlist', 'Save and update your watchlist in this app.'],
    'progress:read' => ['Learning progress', 'Read your saved learning progress.'],
    'progress:write' => ['Learning progress', 'Save learning progress from this app to your Beyond ID.'],
    'trivia:play' => ['Trivia activity', 'Submit trivia results and keep them connected to your account.'],
    'streaks:write' => ['Daily streaks', 'Save your activity streak for this app.'],
    'account:delete' => ['Account deletion request', 'Submit a request to delete your Beyond ID and associated account data.'],
];
$permissions = [];
foreach ($requestedScopes as $scope) {
    $permissions[] = $scopeDescriptions[$scope] ?? [ucwords(str_replace([':', '_'], [' ', ' '], $scope)), 'Allow this app to use this Beyond ID feature.'];
}

$pending = is_array($_SESSION['mobile_consent_pending'] ?? null) ? $_SESSION['mobile_consent_pending'] : [];
$decision = strtolower(trim((string)($_POST['decision'] ?? '')));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validPending = (int)($pending['user_id'] ?? 0) === $userId
        && hash_equals((string)($pending['audience'] ?? ''), $audience)
        && hash_equals((string)($pending['scheme'] ?? ''), $scheme)
        && time() - (int)($pending['created_at'] ?? 0) <= 600;
    if (!verify_csrf_token($_POST['csrf'] ?? null) || !$validPending) {
        unset($_SESSION['mobile_consent_pending']);
        http_response_code(400);
        exit('This approval request expired. Return to the app and try signing in again.');
    }
    unset($_SESSION['mobile_consent_pending']);
    if ($decision !== 'approve') {
        $callback($scheme, 'error', 'Sign-in was cancelled.');
    }
} else {
    $grantedScopes = [];
    try {
        $statement = $pdo->prepare('SELECT permissions_json,revoked_at FROM connected_apps WHERE user_id=? AND app_slug=? LIMIT 1');
        $statement->execute([$userId, $appSlug]);
        $grant = $statement->fetch(PDO::FETCH_ASSOC);
        if (is_array($grant) && empty($grant['revoked_at'])) {
            $decoded = json_decode((string)($grant['permissions_json'] ?? '[]'), true);
            if (is_array($decoded)) $grantedScopes = array_values(array_filter($decoded, 'is_string'));
        }
    } catch (Throwable $exception) {
        error_log('Mobile consent lookup failed: ' . $exception->getMessage());
    }
    if (array_diff($requestedScopes, $grantedScopes) === []) {
        $decision = 'approve';
    } else {
        $_SESSION['mobile_consent_pending'] = [
            'user_id' => $userId,
            'audience' => $audience,
            'scheme' => $scheme,
            'created_at' => time(),
        ];
    }
}

if ($decision === 'approve') {
    try {
        $code = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        $existingQuery = $pdo->prepare('SELECT permissions_json,revoked_at FROM connected_apps WHERE user_id=? AND app_slug=? LIMIT 1');
        $existingQuery->execute([$userId, $appSlug]);
        $existingGrant = $existingQuery->fetch(PDO::FETCH_ASSOC);
        $existingScopes = is_array($existingGrant) && empty($existingGrant['revoked_at'])
            ? json_decode((string)($existingGrant['permissions_json'] ?? '[]'), true)
            : [];
        if (!is_array($existingScopes)) $existingScopes = [];
        $permissionsJson = json_encode(array_values(array_unique(array_merge(
            array_values(array_filter($existingScopes, 'is_string')),
            $requestedScopes
        ))), JSON_THROW_ON_ERROR);
        $pdo->prepare('INSERT INTO mobile_authorization_codes(code_hash,user_id,audience,code_challenge,expires_at,created_at) VALUES (?,?,?,?,?,?)')->execute([
            hash('sha256', $code), $userId, $audience, $challenge, date('Y-m-d H:i:s', time() + 60), $now,
        ]);
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $connectionSql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON CONFLICT(user_id,app_slug) DO UPDATE SET permissions_json=excluded.permissions_json,last_used_at=excluded.last_used_at,revoked_at=NULL';
        } else {
            $connectionSql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at,revoked_at) VALUES(?,?,?,?,NULL) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json),last_used_at=VALUES(last_used_at),revoked_at=NULL';
        }
        $pdo->prepare($connectionSql)->execute([$userId, $appSlug, $permissionsJson, $now]);
        $pdo->commit();
        $callback($scheme, 'code', $code);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Mobile authorization completion failed: ' . $exception->getMessage());
        $callback($scheme, 'error', 'Could not complete sign-in. Please try again.');
    }
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0b0b16">
<title>Connect <?= e($appName) ?> | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;padding:20px;display:grid;place-items:center;background:radial-gradient(circle at 12% 8%,#292161,transparent 38%),#090912;color:#fff;font-family:Inter,system-ui,sans-serif}.card{width:min(100%,560px);padding:clamp(22px,6vw,38px);border:1px solid #383849;border-radius:26px;background:#151522;box-shadow:0 22px 70px #0006}.brand{color:#c4b5fd;font-size:12px;font-weight:900;letter-spacing:.12em}.app{display:flex;align-items:center;gap:14px;margin:22px 0;padding:15px;border-radius:17px;background:#202033}.mark{width:48px;height:48px;display:grid;place-items:center;border-radius:15px;background:linear-gradient(135deg,#6d66ff,#e044a7);font-weight:950;font-size:20px}.app strong,.app small{display:block}.app small{margin-top:3px;color:#aaaabd;line-height:1.4}h1{font-size:clamp(27px,7vw,36px);letter-spacing:-.035em;margin:0}.intro,.fine{color:#b8b8ca;line-height:1.6}.permissions{display:grid;gap:10px;margin:20px 0;padding:0;list-style:none}.permissions li{padding:14px 15px;border:1px solid #343447;border-radius:14px}.permissions strong,.permissions span{display:block}.permissions span{margin-top:4px;color:#b8b8ca;font-size:14px;line-height:1.5}.actions{display:grid;grid-template-columns:1fr 1fr;gap:11px;margin-top:22px}button{min-height:50px;padding:12px 16px;border-radius:13px;font:inherit;font-weight:850;cursor:pointer}.approve{border:0;background:linear-gradient(90deg,#5b8cff,#a044f2,#e9449f);color:#fff}.cancel{border:1px solid #44445a;background:#1b1b2a;color:#fff}button:focus-visible{outline:3px solid #c4b5fd;outline-offset:3px}.fine{font-size:12px}.fine a{color:#d5c8ff}@media(max-width:420px){.actions{grid-template-columns:1fr}.card{border-radius:21px}}
</style>
</head>
<body>
<main class="card">
<div class="brand">BEYOND ID · APP PERMISSIONS</div>
<div class="app"><span class="mark" aria-hidden="true"><?= e(substr($appName, 0, 1)) ?></span><div><strong><?= e($appName) ?></strong><small>Requesting access to your Beyond ID</small></div></div>
<h1>Allow this app to connect?</h1>
<p class="intro">Review what <?= e($appName) ?> can access. You can disconnect it later from Beyond ID → Connected apps.</p>
<ul class="permissions" aria-label="Requested permissions"><?php foreach ($permissions as [$label, $description]): ?><li><strong><?= e($label) ?></strong><span><?= e($description) ?></span></li><?php endforeach; ?></ul>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="actions"><button class="approve" type="submit" name="decision" value="approve">Allow and continue</button><button class="cancel" type="submit" name="decision" value="deny">Cancel</button></div></form>
<p class="fine">By continuing, you authorize this app to use these permissions. Review access any time in <a href="../dashboard/connected-apps.php">Connected apps</a>.</p>
</main>
</body>
</html>
