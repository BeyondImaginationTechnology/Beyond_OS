<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/social-auth.php';
require_once __DIR__ . '/../../config/roles.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: no-referrer');

$flow = is_array($_SESSION['oauth_flow'] ?? null) ? $_SESSION['oauth_flow'] : [];
$provider = strtolower(trim((string)($_GET['provider'] ?? ($flow['provider'] ?? ''))));
unset($_SESSION['oauth_flow']);
try {
    if (!in_array($provider, ['google', 'github', 'apple', 'x'], true) || ($flow['provider'] ?? '') !== $provider) throw new BeyondSocialUserException('Social sign-in session is invalid.');
    if (time() - (int)($flow['created_at'] ?? 0) > 600) throw new BeyondSocialUserException('Social sign-in expired. Please try again.');
    $response = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $state = (string)($response['state'] ?? '');
    if ($state === '' || !hash_equals((string)$flow['state'], $state)) throw new BeyondSocialUserException('Social sign-in security check failed.');
    if (!empty($response['error'])) throw new BeyondSocialUserException('Social sign-in was cancelled or denied.');
    $code = (string)($response['code'] ?? '');
    if ($code === '') throw new BeyondSocialUserException('The provider did not return an authorization code.');
    $tokens = beyond_social_exchange_code($provider, $code, (string)($flow['verifier'] ?? ''));
    if ($provider === 'apple') $tokens['_expected_nonce'] = hash('sha256', $state);
    $accessToken = (string)($tokens['access_token'] ?? '');
    if ($accessToken === '') throw new BeyondSocialUserException('The provider did not return an access token.');
    $profile = beyond_social_profile($provider, $accessToken, $tokens);
    if ($profile['subject'] === '') throw new BeyondSocialUserException('The provider account is missing an identifier.');
    if ($provider !== 'x' && $provider !== 'instagram' && (!$profile['email_verified'] || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL))) {
        throw new BeyondSocialUserException('A verified email address is required. Make sure your social account shares its email with Beyond ID.');
    }

    $identity = $pdo->prepare('SELECT user_id FROM social_identities WHERE provider=? AND provider_user_id=? LIMIT 1');
    $identity->execute([$provider, $profile['subject']]);
    $userId = (int)($identity->fetchColumn() ?: 0);
    if ($provider === 'x' && !$userId && (!$profile['email_verified'] || !filter_var($profile['email'], FILTER_VALIDATE_EMAIL))) {
        $_SESSION['pending_x_identity'] = [
            'subject' => $profile['subject'],
            'username' => $profile['username'] ?? '',
            'name' => $profile['name'],
            'created_at' => time(),
            'return_to' => is_string($_SESSION['beyond_return_to'] ?? null) ? $_SESSION['beyond_return_to'] : '',
            'mobile_scheme' => $flow['mobile_scheme'] ?? '',
            'mobile_code_challenge' => $flow['mobile_code_challenge'] ?? '',
        ];
        header('Location: x-complete.php');
        exit;
    }
    if ($provider === 'instagram' && !$userId) {
        $_SESSION['pending_instagram_identity'] = [
            'subject' => $profile['subject'],
            'username' => $profile['username'] ?? '',
            'name' => $profile['name'],
            'account_type' => $profile['account_type'] ?? '',
            'created_at' => time(),
            'return_to' => is_string($_SESSION['beyond_return_to'] ?? null) ? $_SESSION['beyond_return_to'] : '',
            'mobile_scheme' => $flow['mobile_scheme'] ?? '',
            'mobile_code_challenge' => $flow['mobile_code_challenge'] ?? '',
        ];
        header('Location: instagram-complete.php');
        exit;
    }

    $pdo->beginTransaction();
    if (!$userId) {
        $find = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
        $find->execute([$profile['email']]);
        $existing = $find->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $userId = (int)$existing['id'];
        } else {
            $first = $profile['first_name'] ?: trim(strtok($profile['name'], ' ') ?: 'Beyond');
            $last = $profile['last_name'];
            $name = $profile['name'] ?: trim($first . ' ' . $last);
            $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $role = beyond_signup_role($profile['email']);
            $insert = $pdo->prepare("INSERT INTO users (first_name,last_name,name,email,password,password_hash,email_verified,email_verified_at,verification_token,role,status) VALUES (?,?,?,?,?,?,1,?,NULL,?,'active')");
            $insert->execute([$first, $last, $name, $profile['email'], $randomPassword, $randomPassword, date('Y-m-d H:i:s'), $role]);
            $userId = (int)$pdo->lastInsertId();
            try { $pdo->prepare("UPDATE users SET terms_accepted_at=?,terms_version='beyond-id-0.1' WHERE id=?")->execute([date('Y-m-d H:i:s'), $userId]); } catch (Throwable $exception) {}
            try { $pdo->prepare('INSERT INTO profiles (user_id) VALUES (?)')->execute([$userId]); } catch (Throwable $exception) {}
            try {
                $sql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "INSERT OR IGNORE INTO beyond_wallets (user_id,balance,currency,status) VALUES (?,0,'BITS','active')" : "INSERT IGNORE INTO beyond_wallets (user_id,balance,currency,status) VALUES (?,0,'BITS','active')";
                $pdo->prepare($sql)->execute([$userId]);
            } catch (Throwable $exception) {}
            create_notification($pdo, $userId, 'Welcome to Beyond ID', 'Your account is ready for BIT OS and Beyond apps.', '/beyond-id/dashboard/profile.php', 'welcome');
        }
        $link = $pdo->prepare('INSERT INTO social_identities (user_id,provider,provider_user_id,email,display_name,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
        $now = date('Y-m-d H:i:s');
        $link->execute([$userId, $provider, $profile['subject'], $profile['email'], $profile['name'], $now, $now]);
    } elseif ($provider === 'instagram') {
        // The identity is already linked, so a profile-label refresh must not
        // prevent authentication if an older database schema is missing one
        // of the optional metadata columns.
        try {
            $update = $pdo->prepare('UPDATE social_identities SET display_name=?,updated_at=? WHERE provider=? AND provider_user_id=?');
            $update->execute([$profile['name'], date('Y-m-d H:i:s'), $provider, $profile['subject']]);
        } catch (Throwable $metadataException) {
            error_log('OAuth Instagram metadata refresh skipped class=' . get_class($metadataException) . ': ' . $metadataException->getMessage());
        }
    } elseif ($provider === 'x') {
        $update = $pdo->prepare('UPDATE social_identities SET display_name=?,updated_at=? WHERE provider=? AND provider_user_id=?');
        $update->execute([$profile['name'], date('Y-m-d H:i:s'), $provider, $profile['subject']]);
    } else {
        $update = $pdo->prepare('UPDATE social_identities SET email=?,display_name=?,updated_at=? WHERE provider=? AND provider_user_id=?');
        $update->execute([$profile['email'], $profile['name'], date('Y-m-d H:i:s'), $provider, $profile['subject']]);
    }
    $userStatement = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $userStatement->execute([$userId]);
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);
    if (!$user || ($user['status'] ?? 'active') !== 'active') throw new BeyondSocialUserException('This Beyond ID is not active.');
    if (in_array($provider, ['instagram', 'x'], true) && empty($user['email_verified']) && empty($user['email_verified_at'])) {
        throw new BeyondSocialUserException('Verify your Beyond ID email before signing in with ' . ($provider === 'x' ? 'X' : 'Instagram') . '.');
    }
    $pdo->commit();
    beyond_social_login_session($pdo, $user, $provider, beyond_social_destination($flow));
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('OAuth callback failed provider=' . $provider . ' class=' . get_class($exception) . ': ' . $exception->getMessage());
    $_SESSION['oauth_error'] = $exception instanceof BeyondSocialUserException
        ? $exception->getMessage()
        : 'Social sign-in could not be completed. Please try again.';
    header('Location: login.php');
    exit;
}
