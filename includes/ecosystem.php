<?php
declare(strict_types=1);

$beyondSessionBootstrap = __DIR__ . '/../beyond-id/includes/session.php';
if (is_file($beyondSessionBootstrap)) {
    require_once $beyondSessionBootstrap;
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$beyondFunctionsBootstrap = __DIR__ . '/../beyond-id/includes/functions.php';
if (is_file($beyondFunctionsBootstrap)) {
    require_once $beyondFunctionsBootstrap;
}
if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function beyond_base_path(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    // Resolve the installation root from the filesystem first. This keeps every
    // app folder (including future ones) anchored to the shared site assets.
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(__DIR__ . '/..');
    if (is_string($documentRoot) && is_string($projectRoot)) {
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
        $documentRootLower = strtolower($documentRoot);
        $projectRootLower = strtolower($projectRoot);
        if ($projectRootLower === $documentRootLower || str_starts_with($projectRootLower, $documentRootLower . '/')) {
            $relativeRoot = trim(substr($projectRoot, strlen($documentRoot)), '/');
            return $relativeRoot === '' ? '' : '/' . $relativeRoot;
        }
    }

    // Fallback for hosts that do not expose a usable DOCUMENT_ROOT.
    $known = ['/app-store/','/academy/','/coding-school/','/beyond-id/','/beyond-math/','/beyond-french/','/dailybreath/','/beyond-health/','/beyond-tv/','/beyond-media/','/beyond-games/','/beyond-jobs/','/beyond-radio/','/beyond-casino/','/beyond-skate/','/beyond-catering/','/beyond-baby-names/','/beyond-tattoo/','/beyond-space/','/beyond-ancient/','/beyond-careers/','/beyond-sell/','/beyond-market/','/beyond-finance/','/beyond-investing/','/dashboard/','/admin/','/api-hub/'];
    foreach ($known as $marker) {
        $position = strpos($script, $marker);
        if ($position !== false) return substr($script, 0, $position);
    }
    return rtrim(dirname($script), '/.');
}

function beyond_url(string $path = ''): string { return rtrim(beyond_base_path(), '/') . '/' . ltrim($path, '/'); }
function beyond_return_url(): string { $uri = $_SERVER['REQUEST_URI'] ?? beyond_url(); return str_starts_with($uri, '/') ? $uri : beyond_url(); }

function beyond_app_icon(string $appName): string {
    $fallback = 'assets/icons/app-store/beyond-imagination.jpg';
    $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $appName), '-'));
    $aliases = [
        'dailybreath' => 'daily-breath',
        'daily-breath' => 'daily-breath',
        'health' => 'beyond-health',
        'tv' => 'beyond-tv',
        'french' => 'beyond-french',
        'ancient' => 'beyond-ancient',
        'space' => 'beyond-space',
        'baby-names' => 'beyond-baby-names',
        'tattoo' => 'beyond-tattoo',
        'beyond-imagination-technology' => 'beyond-os',
    ];
    $slug = $aliases[$key] ?? $key;
    $supported = ['beyond-os','beyond-id','daily-breath','beyond-health','beyond-tv','beyond-french','beyond-ancient','beyond-space','beyond-baby-names','beyond-tattoo'];
    if (!in_array($slug, $supported, true)) {
        return is_file(__DIR__ . '/../' . $fallback) ? beyond_url($fallback) : '';
    }
    $versioned = [
        'daily-breath' => 'daily-breath-192.jpg?v=20260730-1',
        'beyond-baby-names' => 'beyond-baby-names-v2-192.webp?v=20260717-3',
        'beyond-tattoo' => 'beyond-tattoo-v2-192.webp?v=20260717-3',
    ];
    $file = $versioned[$slug] ?? ($slug . '-192.webp');
    $diskFile = preg_replace('/\?.*$/', '', $file);
    if (!is_file(__DIR__ . '/../assets/icons/' . $diskFile)) {
        return is_file(__DIR__ . '/../' . $fallback) ? beyond_url($fallback) : '';
    }
    return beyond_url('assets/icons/' . $file);
}
function beyond_splash_assets_markup(): string {
    return '<link rel="stylesheet" href="' . e(beyond_url('assets/css/beyond-splash.css?v=20260828-1')) . '">'
        . '<script src="' . e(beyond_url('assets/js/beyond-splash.js?v=20260828-1')) . '" defer></script>';
}
function beyond_inject_splash_assets(string $html): string {
    if (stripos($html, '</head>') === false || str_contains($html, 'beyond-splash.js')) {
        return $html;
    }
    return preg_replace('/<\/head>/i', beyond_splash_assets_markup() . '</head>', $html, 1) ?? $html;
}
function require_beyond_id(): void { if (empty($_SESSION['user_id'])) { $_SESSION['beyond_return_to'] = beyond_return_url(); header('Location: ' . beyond_url('beyond-id/auth/login.php?required=1')); exit; } }
function beyond_db(): PDO {
    $databaseBootstrap = __DIR__ . '/../beyond-id/includes/db.php';
    if (!is_file($databaseBootstrap)) {
        throw new RuntimeException('Beyond ID database bootstrap is not installed.');
    }
    require $databaseBootstrap;
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException('Beyond ID database bootstrap did not provide a PDO connection.');
    }
    return $pdo;
}

function beyond_wallet(): array {
    $wallet = ['balance'=>0,'currency'=>'BITS','status'=>'pending'];
    if (empty($_SESSION['user_id'])) return $wallet;
    try {
        $pdo = beyond_db();
        $insert = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? "INSERT OR IGNORE INTO beyond_wallets(user_id,balance,currency,status) VALUES(?,0,'BITS','active')"
            : "INSERT IGNORE INTO beyond_wallets(user_id,balance,currency,status) VALUES(?,0,'BITS','active')";
        $pdo->prepare($insert)->execute([(int)$_SESSION['user_id']]);
        $stmt = $pdo->prepare('SELECT balance,currency,status FROM beyond_wallets WHERE user_id=? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC) ?: $wallet;
    } catch (Throwable $exception) {
        error_log('Beyond Wallet unavailable: ' . $exception->getMessage());
    }
    return $wallet;
}

/** Credit a completion reward to the shared Beyond Wallet exactly once. */
function beyond_award_reward(int $userId,string $appSlug,string $activityType,string $activityId,int $amount,string $description): array {
    $result=['ok'=>false,'awarded'=>false,'amount'=>$amount,'balance'=>null,'message'=>''];
    if($userId<1||$amount<1||trim($activityId)==='')return $result;
    $appSlug=strtolower(trim((string)preg_replace('/[^a-z0-9-]+/i','-',$appSlug),'-'))?:'beyond-os';
    $activityType=strtolower(trim((string)preg_replace('/[^a-z0-9-]+/i','-',$activityType),'-'))?:'completion';
    $idempotency=sprintf('reward:v1:u%d:%s:%s:%s',$userId,substr($appSlug,0,28),substr($activityType,0,20),substr(hash('sha256',$activityId),0,24));
    try{
        $pdo=beyond_db();$driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnore=static fn(string $sql): string=>$driver==='sqlite'?preg_replace('/^INSERT\s+INTO/i','INSERT OR IGNORE INTO',$sql,1):preg_replace('/^INSERT\s+INTO/i','INSERT IGNORE INTO',$sql,1);
        $pdo->beginTransaction();
        $pdo->prepare($insertIgnore("INSERT INTO beyond_wallets(user_id,balance,currency,status) VALUES(?,0,'BITS','active')"))->execute([$userId]);
        $walletSql='SELECT id,balance,status FROM beyond_wallets WHERE user_id=? LIMIT 1'.($driver==='sqlite'?'':' FOR UPDATE');
        $walletStmt=$pdo->prepare($walletSql);$walletStmt->execute([$userId]);$wallet=$walletStmt->fetch(PDO::FETCH_ASSOC);
        if(!$wallet||($wallet['status']??'active')!=='active')throw new RuntimeException('Wallet is unavailable or not active.');
        $transaction=$pdo->prepare($insertIgnore("INSERT INTO beyond_wallet_transactions(wallet_id,amount,type,app_slug,description,idempotency_key) VALUES (?,?,'credit',?,?,?)"));
        $transaction->execute([(int)$wallet['id'],$amount,$appSlug,substr($description,0,255),$idempotency]);
        if($transaction->rowCount()===1){$pdo->prepare('UPDATE beyond_wallets SET balance=balance+? WHERE id=?')->execute([$amount,(int)$wallet['id']]);$result['awarded']=true;$result['balance']=(float)$wallet['balance']+$amount;$result['message']='+'.$amount.' bit$ earned.';}
        else{$result['balance']=(float)$wallet['balance'];$result['message']='Reward already collected.';}
        $pdo->commit();$result['ok']=true;
        $GLOBALS['beyond_wallet_override']=['balance'=>$result['balance'],'currency'=>'BITS','status'=>'active'];
    }catch(Throwable $exception){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();error_log('Beyond reward unavailable: '.$exception->getMessage());$result['message']='Learning progress saved; wallet reward will be available to retry.';}
    return $result;
}

function beyond_notification_count(): int {
    if (empty($_SESSION['user_id'])) return 0;
    try { return unread_notification_count(beyond_db(), (int)$_SESSION['user_id']); } catch (Throwable $exception) { return 0; }
}

function beyond_track_app(string $appName): void {
    if (empty($_SESSION['user_id'])) return;
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $appName), '-'));
    try {
        $pdo = beyond_db();
        $now = date('Y-m-d H:i:s');
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at) VALUES(?,?,?,?) ON CONFLICT(user_id,app_slug) DO UPDATE SET last_used_at=excluded.last_used_at,revoked_at=NULL';
        } else {
            $sql = 'INSERT INTO connected_apps(user_id,app_slug,permissions_json,last_used_at) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE last_used_at=VALUES(last_used_at),revoked_at=NULL';
        }
        $pdo->prepare($sql)->execute([(int)$_SESSION['user_id'], $slug, json_encode(['profile:read','wallet:read']), $now]);
    } catch (Throwable $exception) {
    }
}

function beyond_nav_bootstrap(string $appName, ?array $wallet = null): array {
    $wallet ??= beyond_wallet();
    if (PHP_SAPI !== 'cli' && empty($GLOBALS['beyond_nav_started'])) {
        $GLOBALS['beyond_nav_started'] = true;
        ob_start(static function (string $html) use ($appName, $wallet): string {
            $html = beyond_inject_splash_assets($html);
            if (stripos($html, '<body') === false || str_contains($html, 'id="beyond-os-shell"')) return $html;
            $wallet = $GLOBALS['beyond_wallet_override'] ?? $wallet;
            $icon = beyond_app_icon($appName) ?: beyond_app_icon('Beyond OS');
            if ($icon && stripos($html, 'rel="icon"') === false) {
                $iconType = preg_match('/\.jpe?g(?:\?|$)/i', $icon) ? 'image/jpeg' : 'image/webp';
                $tag = '<link rel="icon" type="' . $iconType . '" href="' . e($icon) . '">'
                    . '<link rel="apple-touch-icon" href="' . e($icon) . '">';
                $html = preg_replace('/<\/head>/i', $tag . '</head>', $html, 1) ?? $html;
            }
            if (!str_contains($html, 'beyond-theme-default.js')) {
                $themeAssets = '<script src="' . e(beyond_url('assets/js/beyond-theme-default.js?v=20260828-2')) . '"></script>'
                    . '<script src="' . e(beyond_url('assets/js/beyond-locales.js?v=20260727-1')) . '" defer></script>'
                    . '<link rel="stylesheet" href="' . e(beyond_url('assets/css/beyond-dark-default.css?v=20260828-2')) . '">';
                $html = preg_replace('/<\/head>/i', $themeAssets . '</head>', $html, 1) ?? $html;
            }
            return preg_replace('/(<body[^>]*>)/i', '$1' . beyond_shell_markup($appName, $wallet), $html, 1) ?? $html;
        });
    }
    return $wallet;
}

function beyond_app_bootstrap(string $appName): array {
    require_beyond_id();
    beyond_track_app($appName);
    $wallet = beyond_wallet();
    header('X-Beyond-OS-Version: 2.4');
    header('X-Beyond-App: ' . preg_replace('/[^A-Za-z0-9 -]/', '', $appName));
    return beyond_nav_bootstrap($appName, $wallet);
}

function beyond_shell_markup(string $appName, array $wallet): string {
    $signedIn = !empty($_SESSION['user_id']);
    $app = e($appName);
    $home = e(beyond_url());
    // Version the navbar asset so browsers receive logo updates immediately.
    $homeIcon = '<span class="bos-logo-mark" aria-hidden="true"><img src="' . e(beyond_url('assets/images/bos-logo-mark.svg?v=20260828-1')) . '" alt=""></span>';
    $currentIconPath = beyond_app_icon($appName);
    $currentIcon = $currentIconPath ? '<img class="bos-current-icon" src="' . e($currentIconPath) . '" alt="">' : '';
    $appIdentity = strcasecmp(trim($appName), 'Beyond OS') === 0
        ? ''
        : '<span class="bos-app-label">/</span>' . $currentIcon . '<strong class="bos-app">' . $app . '</strong>';

    if ($signedIn) {
        $emailRaw = (string)($_SESSION['email'] ?? 'Member');
        $nameRaw = trim((string)($_SESSION['name'] ?? '')) ?: strtok($emailRaw, '@');
        $email = e($emailRaw);
        $displayName = e($nameRaw ?: 'Member');
        $avatar = e(strtoupper(substr($nameRaw ?: $emailRaw ?: 'B', 0, 1)));
        $avatarUrl = '';
        try {
            $avatarStmt = beyond_db()->prepare('SELECT avatar FROM profiles WHERE user_id = ? LIMIT 1');
            $avatarStmt->execute([(int)$_SESSION['user_id']]);
            $avatarCandidate = trim((string)($avatarStmt->fetchColumn() ?: ''));
            if ($avatarCandidate !== '' && preg_match('#^(?:https?://|/)#i', $avatarCandidate)) {
                $avatarUrl = e($avatarCandidate);
            }
        } catch (Throwable $exception) {
            $avatarUrl = '';
        }
        $avatarMarkup = $avatarUrl !== ''
            ? '<span class="bos-avatar"><img src="' . $avatarUrl . '" alt=""></span>'
            : '<span class="bos-avatar bos-avatar-default"><img src="' . e(beyond_url('assets/images/default-astronaut-avatar.webp?v=20260727-1')) . '" alt=""></span>';
        $balance = number_format((float)($wallet['balance'] ?? 0), 0);
        $unread = beyond_notification_count();
        $accountActions = '<span class="bos-account-cluster"><a class="bos-icon-action bos-notifications" href="' . e(beyond_url('beyond-id/dashboard/notifications.php')) . '" aria-label="Notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>' . ($unread ? '<span class="bos-badge">' . $unread . '</span>' : '') . '</a>'
            . '<a class="bos-action bos-bits" href="' . e(beyond_url('beyond-id/dashboard/wallet.php')) . '"><i aria-hidden="true">b</i><span>' . $balance . ' bit$</span></a>'
            . '<details class="bos-account"><summary aria-label="Open Beyond ID account menu">' . $avatarMarkup . '<span class="bos-account-name">' . $displayName . '</span><svg class="bos-chevron" viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg></summary><div class="bos-account-menu"><div class="bos-account-identity">' . $avatarMarkup . '<span><strong>' . $displayName . '</strong><small>' . $email . '</small></span></div><a href="' . e(beyond_url('beyond-id/dashboard/')) . '">Beyond ID dashboard <span>→</span></a><a href="' . e(beyond_url('beyond-id/dashboard/wallet.php')) . '">Wallet <span>' . $balance . ' bit$</span></a><a class="bos-signout" href="' . e(beyond_url('beyond-id/auth/logout.php')) . '">Sign out</a></div></details></span>';
    } else {
        $accountActions = '<a class="bos-action bos-create" href="' . e(beyond_url('beyond-id/auth/login.php')) . '">Beyond ID</a>';
    }

    $navTools = '<span class="bos-tool-cluster"><label class="bos-locale" title="Choose language"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/></svg><span class="bos-sr-only">Language</span><select id="localePicker" aria-label="Choose language"><option value="en">English</option><option value="fr">Français</option><option value="ht">Kreyòl</option><option value="es">Español</option></select></label>'
        . '<button class="theme-toggle bos-theme-toggle" type="button" aria-label="Switch theme" title="Switch theme">☀</button></span>';
    $appStoreAction = '<a class="bos-action bos-app-store" href="' . e(beyond_url('app-store/')) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg><span class="bos-app-store-label bos-app-store-label-full">App Store</span><span class="bos-app-store-label bos-app-store-label-mobile">Apps</span><b aria-hidden="true">→</b></a>';

    return '<style>
@import url("https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&display=swap");
#beyond-os-shell{position:relative;top:auto;z-index:100;min-height:64px;padding:max(9px,env(safe-area-inset-top)) 16px 9px;background:rgba(7,8,16,.9);color:#fff;border-bottom:1px solid rgba(255,255,255,.12);backdrop-filter:blur(24px) saturate(1.3);font:600 13px/1.3 "Space Grotesk",Inter,system-ui,sans-serif;box-shadow:0 10px 35px rgba(0,0,0,.18)}
#beyond-os-shell *{box-sizing:border-box}#beyond-os-shell a{color:inherit;text-decoration:none}
#beyond-os-shell .bos-row{width:100%;max-width:1320px;min-width:0;margin:auto;display:flex;align-items:center;gap:12px}
#beyond-os-shell .bos-home{color:#a5b4fc;font-weight:900;letter-spacing:.04em;display:flex;align-items:center;gap:8px;flex:0 0 auto;white-space:nowrap}
#beyond-os-shell .bos-home-label{display:inline-flex;font-size:15px;line-height:1;font-weight:700;letter-spacing:-.045em;background:linear-gradient(100deg,#fff 0 52%,#f05ab8 76%,#9b82ff);background-clip:text;-webkit-background-clip:text;color:transparent}
#beyond-os-shell .bos-home img,#beyond-os-shell .bos-current-icon{width:30px;height:30px;border-radius:9px;object-fit:cover;border:1px solid rgba(255,255,255,.18)}
#beyond-os-shell .bos-logo-mark{position:relative;display:grid;place-items:center;width:38px;height:38px;flex:0 0 38px;min-width:38px;overflow:hidden;isolation:isolate;border:1px solid rgba(181,137,255,.44);border-radius:12px;background:radial-gradient(circle at 35% 30%,#31245d,#0b0b1d 68%);box-shadow:0 7px 22px rgba(112,74,255,.34),inset 0 0 14px rgba(118,88,255,.15)}
#beyond-os-shell .bos-logo-mark img{display:block;width:34px;height:34px;border:0;border-radius:0;object-fit:contain}
#beyond-os-shell .bos-app{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#beyond-os-shell .bos-actions{min-width:0;margin-left:auto;display:flex;align-items:center;gap:9px}
#beyond-os-shell .bos-action{min-height:40px;display:flex;align-items:center;gap:7px;padding:8px 12px;border:1px solid rgba(255,255,255,.13);border-radius:999px;background:rgba(255,255,255,.055);color:#fff;font:700 12px/1 "Space Grotesk",Inter,system-ui,sans-serif;letter-spacing:-.015em;transition:transform .2s ease,border-color .2s ease,background .2s ease}
#beyond-os-shell .bos-action:hover,#beyond-os-shell .bos-action:focus-visible{transform:translateY(-1px);border-color:rgba(190,170,255,.48);background:rgba(255,255,255,.1)}
#beyond-os-shell .bos-app-store{position:relative;isolation:isolate;min-height:42px;padding:0 16px;background:linear-gradient(105deg,#526dff 0%,#8658f6 48%,#e950aa 100%);border-color:rgba(255,255,255,.2);font-weight:700;box-shadow:0 10px 28px rgba(94,72,255,.32),inset 0 1px rgba(255,255,255,.25);overflow:hidden}#beyond-os-shell .bos-app-store:before{content:"";position:absolute;z-index:-1;inset:-80% 45% -80% -20%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.24),transparent);transform:skewX(-18deg)}#beyond-os-shell .bos-app-store:hover:before{animation:bos-app-shine .8s ease forwards}@keyframes bos-app-shine{to{transform:translateX(220%) skewX(-18deg)}}#beyond-os-shell .bos-app-store svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.7}#beyond-os-shell .bos-app-store b{font-size:14px}#beyond-os-shell .bos-app-store-label-mobile{display:none}
#beyond-os-shell .bos-create{background:linear-gradient(100deg,#586cff,#ef4897);border-color:rgba(255,255,255,.15)}#beyond-os-shell .bos-bits{color:#ffe17a;background:rgba(255,198,54,.07);border-color:rgba(255,216,122,.18)}#beyond-os-shell .bos-bits i{display:grid;width:20px;height:20px;place-items:center;border-radius:50%;background:linear-gradient(145deg,#ffe68a,#c98913);color:#352100;font:800 11px/1 Georgia,serif;box-shadow:0 0 14px rgba(255,197,58,.22)}
html[data-theme="sunset"] #beyond-os-shell{background:rgba(57,20,47,.95);border-color:rgba(255,204,176,.2)}html[data-theme="sunset"] #beyond-os-shell .bos-action,html[data-theme="sunset"] #beyond-os-shell .bos-locale,html[data-theme="sunset"] #beyond-os-shell .bos-theme-toggle{background:rgba(112,43,76,.46);border-color:rgba(255,208,180,.25)}html[data-theme="sunset"] #beyond-os-shell .bos-app-store{background:linear-gradient(110deg,#ff8a62,#a83e81);box-shadow:0 8px 24px rgba(255,108,92,.25)}
html[data-theme="fall"] #beyond-os-shell{background:rgba(47,25,16,.95);border-color:rgba(242,184,75,.2)}html[data-theme="fall"] #beyond-os-shell .bos-action,html[data-theme="fall"] #beyond-os-shell .bos-locale,html[data-theme="fall"] #beyond-os-shell .bos-theme-toggle{background:rgba(102,52,28,.46);border-color:rgba(241,174,84,.25)}html[data-theme="fall"] #beyond-os-shell .bos-app-store{background:linear-gradient(110deg,#d8562c,#e88327 57%,#f1b344);box-shadow:0 8px 24px rgba(201,83,30,.28)}
#beyond-os-shell .bos-tool-cluster,#beyond-os-shell .bos-account-cluster{display:flex;align-items:center;gap:4px;padding:3px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(255,255,255,.035);box-shadow:inset 0 1px rgba(255,255,255,.04)}
#beyond-os-shell .bos-icon-action{position:relative;display:grid;width:38px;height:38px;place-items:center;border-radius:50%;color:#e9ebf5;transition:background .2s ease,color .2s ease}#beyond-os-shell .bos-icon-action:hover,#beyond-os-shell .bos-icon-action:focus-visible{background:rgba(255,255,255,.1);color:#fff}#beyond-os-shell .bos-icon-action svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
#beyond-os-shell .bos-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;overflow:hidden;background:linear-gradient(135deg,#5b8cff,#e9449f);font-weight:900;flex:0 0 36px;border:1px solid rgba(210,197,255,.65);box-shadow:0 0 0 3px rgba(123,91,255,.12),0 7px 18px rgba(0,0,0,.24)}#beyond-os-shell .bos-avatar img{display:block;width:100%;height:100%;object-fit:cover;border:0;border-radius:50%}.bos-badge{position:absolute;right:-1px;top:-2px;display:inline-grid;place-items:center;min-width:17px;height:17px;padding:0 4px;border:2px solid #0b0c15;border-radius:999px;background:#ff4676;color:#fff;font-size:9px;font-weight:800}
#beyond-os-shell .bos-locale,#beyond-os-shell .bos-theme-toggle{position:relative;z-index:2;width:36px;height:36px;flex:0 0 36px;display:grid;place-items:center;padding:0;border:0;border-radius:50%;background:transparent;color:#e9ebf5;cursor:pointer;touch-action:manipulation;pointer-events:auto;font:600 15px/1 system-ui,sans-serif}#beyond-os-shell .bos-locale:hover,#beyond-os-shell .bos-theme-toggle:hover{background:rgba(255,255,255,.09);color:#fff}#beyond-os-shell .bos-locale>svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round}
#beyond-os-shell .bos-locale select{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}#beyond-os-shell .bos-sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
#beyond-os-shell .bos-account{position:relative}#beyond-os-shell .bos-account summary{display:flex;align-items:center;gap:8px;min-height:40px;padding:2px 8px 2px 2px;border-radius:999px;cursor:pointer;list-style:none}#beyond-os-shell .bos-account summary::-webkit-details-marker{display:none}#beyond-os-shell .bos-account summary:hover{background:rgba(255,255,255,.07)}#beyond-os-shell .bos-account-name{max-width:94px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;font-weight:700}#beyond-os-shell .bos-chevron{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.8;transition:transform .2s ease}#beyond-os-shell .bos-account[open] .bos-chevron{transform:rotate(180deg)}
#beyond-os-shell .bos-account-menu{position:absolute;right:0;top:48px;z-index:1000;width:250px;padding:9px;border:1px solid rgba(255,255,255,.14);border-radius:20px;background:rgba(12,13,25,.97);backdrop-filter:blur(24px);box-shadow:0 24px 70px rgba(0,0,0,.52)}#beyond-os-shell .bos-account-identity{display:flex;align-items:center;gap:11px;padding:9px 9px 13px;margin-bottom:5px;border-bottom:1px solid rgba(255,255,255,.09)}#beyond-os-shell .bos-account-identity span:last-child{min-width:0}#beyond-os-shell .bos-account-identity strong,#beyond-os-shell .bos-account-identity small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}#beyond-os-shell .bos-account-identity small{margin-top:3px;color:#969eb4;font-size:10px}#beyond-os-shell .bos-account-menu>a{display:flex;align-items:center;justify-content:space-between;padding:10px 11px;border-radius:11px;font-size:11px}#beyond-os-shell .bos-account-menu>a:hover{background:rgba(255,255,255,.07)}#beyond-os-shell .bos-account-menu .bos-signout{margin-top:5px;color:#ff9bb3;border-top:1px solid rgba(255,255,255,.08);border-radius:0 0 11px 11px}
@media(max-width:920px){#beyond-os-shell .bos-account-name,#beyond-os-shell .bos-app-store b{display:none}}
@media(max-width:760px){#beyond-os-shell{width:100%;max-width:100vw;padding-left:7px;padding-right:7px}#beyond-os-shell .bos-row{gap:5px}#beyond-os-shell .bos-home-label,#beyond-os-shell .bos-app-label,#beyond-os-shell .bos-app,#beyond-os-shell .bos-app-store-label{display:none}#beyond-os-shell .bos-current-icon{display:block;flex:0 0 30px}#beyond-os-shell .bos-action{min-height:38px;padding:6px 8px}#beyond-os-shell .bos-actions{gap:5px}#beyond-os-shell .bos-app-store{width:40px;justify-content:center;padding:0}#beyond-os-shell .bos-account summary{padding-right:2px}}
@media(max-width:520px){#beyond-os-shell .bos-bits{display:none}#beyond-os-shell .bos-tool-cluster,#beyond-os-shell .bos-account-cluster{gap:2px;padding:2px}}
@media(max-width:430px){#beyond-os-shell .bos-row{gap:4px}#beyond-os-shell .bos-current-icon{display:none}#beyond-os-shell .bos-home img{width:28px;height:28px}#beyond-os-shell .bos-locale,#beyond-os-shell .bos-theme-toggle{display:grid;width:34px;height:34px;flex-basis:34px}#beyond-os-shell .bos-app-store{width:auto;min-width:58px;min-height:36px;padding:6px 9px;justify-content:center}#beyond-os-shell .bos-app-store-label-full{display:none}#beyond-os-shell .bos-app-store-label-mobile{display:inline;font-size:11px}#beyond-os-shell .bos-avatar{width:32px;height:32px;flex-basis:32px}}
</style><nav id="beyond-os-shell" aria-label="Beyond OS navigation"><div class="bos-row"><a class="bos-home" href="' . $home . '" aria-label="Beyond OS" title="Beyond OS">' . $homeIcon . '<span class="bos-home-label">BEYOND OS</span></a>' . $appIdentity . '<div class="bos-actions">' . $appStoreAction . $navTools . $accountActions . '</div></div></nav>';
}

function render_beyond_bar(string $appName, array $wallet = []): void { echo beyond_shell_markup($appName, $wallet ?: beyond_wallet()); }
