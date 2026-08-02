<?php
require __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$uid = (int)$_SESSION['user_id'];
$message = '';
$error = '';
$isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
$insertIgnore = static fn(string $sql): string => $isSqlite ? str_replace('INSERT INTO', 'INSERT OR IGNORE INTO', $sql) : str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);

try {
    $pdo->prepare($insertIgnore('INSERT INTO profiles (user_id) VALUES (?)'))->execute([$uid]);
} catch (Throwable $exception) {
    $error = 'Run the profile details migration before editing your profile.';
}

$options = ['Learning', 'Wellness', 'Faith', 'Languages', 'Food & Business', 'Entertainment', 'Space', 'History', 'Creativity', 'Technology'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $displayName = trim($_POST['display_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $address1 = trim($_POST['address_line1'] ?? '');
        $address2 = trim($_POST['address_line2'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $goals = trim($_POST['goals'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $username = beyond_profile_slug((string)($_POST['username'] ?? ''));
        $publicProfile = !empty($_POST['public_profile_enabled']) ? 1 : 0;
        $creatorLinks = trim((string)($_POST['creator_links'] ?? ''));
        $interests = implode(',', array_slice(array_unique(array_filter(array_map('trim', $_POST['interests'] ?? []))), 0, 8));

        if ($displayName === '') {
            $error = 'Display name is required.';
        } elseif ($username !== '' && (strlen($username) < 3 || strlen($username) > 40)) {
            $error = 'Use a public username between 3 and 40 characters.';
        } else {
            try {
                if ($username !== '') {
                    $check = $pdo->prepare('SELECT user_id FROM profiles WHERE username=? AND user_id<>? LIMIT 1');
                    $check->execute([$username, $uid]);
                    if ($check->fetchColumn()) {
                        throw new RuntimeException('That public username is already taken.');
                    }
                }
                $coreComplete = $displayName !== '' && $interests !== '' && $goals !== '';
                $addressComplete = $address1 !== '' && $country !== '' && $province !== '' && $city !== '' && $postalCode !== '';
                $completedAt = $coreComplete ? date('Y-m-d H:i:s') : null;
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE profiles SET display_name=?,phone=?,birthdate=?,address_line1=?,address_line2=?,country=?,province=?,city=?,postal_code=?,interests=?,goals=?,bio=?,username=?,public_profile_enabled=?,creator_links=?,profile_completed_at=CASE WHEN ? IS NULL THEN profile_completed_at ELSE COALESCE(profile_completed_at,?) END WHERE user_id=?')
                    ->execute([$displayName, $phone, $birthdate ?: null, $address1, $address2, $country, $province, $city, $postalCode, $interests, $goals, $bio, $username ?: null, $publicProfile, $creatorLinks, $completedAt, $completedAt, $uid]);

                $pdo->prepare($insertIgnore("INSERT INTO beyond_wallets(user_id,balance,currency,status) VALUES (?,0,'BITS','active')"))->execute([$uid]);
                $walletSql = 'SELECT id FROM beyond_wallets WHERE user_id=?' . ($isSqlite ? '' : ' FOR UPDATE');
                $wallet = $pdo->prepare($walletSql);
                $wallet->execute([$uid]);
                $walletId = (int)$wallet->fetchColumn();
                $earned = 0;

                $award = static function (int $amount, string $description, string $key) use ($pdo, $insertIgnore, $walletId, &$earned): void {
                    $stmt = $pdo->prepare($insertIgnore("INSERT INTO beyond_wallet_transactions(wallet_id,amount,type,app_slug,description,idempotency_key) VALUES (?,?,'credit','beyond-id',?,?)"));
                    $stmt->execute([$walletId, $amount, $description, $key]);
                    if ($stmt->rowCount() === 1) {
                        $pdo->prepare('UPDATE beyond_wallets SET balance=balance+? WHERE id=?')->execute([$amount, $walletId]);
                        $earned += $amount;
                    }
                };

                if ($coreComplete) $award(100, 'Core profile completion reward', 'profile-complete-v2-user-' . $uid);
                if ($addressComplete) $award(50, 'Address profile bonus', 'profile-address-v1-user-' . $uid);
                if ($phone !== '') $award(10, 'Contact profile bonus', 'profile-phone-v1-user-' . $uid);
                if ($birthdate !== '') $award(10, 'Birthday profile bonus', 'profile-birthdate-v1-user-' . $uid);

                $pdo->commit();
                $message = $earned > 0 ? "Profile saved - {$earned} bonus bit$ earned." : 'Profile saved.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = $exception->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT u.first_name,u.last_name,u.email,p.* FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
$stmt->execute([$uid]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
$selected = array_filter(explode(',', (string)($profile['interests'] ?? '')));
$completionValues = [$profile['display_name'] ?? '', $profile['phone'] ?? '', $profile['birthdate'] ?? '', $profile['address_line1'] ?? '', $profile['country'] ?? '', $profile['province'] ?? '', $profile['city'] ?? '', $profile['postal_code'] ?? '', $profile['interests'] ?? '', $profile['goals'] ?? '', $profile['bio'] ?? '', $profile['username'] ?? ''];
$completion = (int)round(count(array_filter($completionValues, static fn($value): bool => trim((string)$value) !== '')) / count($completionValues) * 100);
$publicUrl = !empty($profile['username']) ? '/u/' . rawurlencode((string)$profile['username']) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile | Beyond ID</title>
<style>
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 8% 0,#ece8ff,transparent 32%),#f7f8fc;color:#252737;font-family:system-ui}.shell{max-width:1040px;margin:auto;padding:28px}.top{display:flex;justify-content:space-between;gap:14px;align-items:center;margin-bottom:30px}.top strong{color:#55586a}.top a{color:#6557c8;font-weight:700}.heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:22px}.heading h1{font-size:clamp(40px,7vw,68px);line-height:.95;letter-spacing:-.055em;margin:8px 0}.heading p{color:#686b7d}.score{min-width:160px;padding:16px;border:1px solid #dcd7ff;border-radius:18px;background:#f4f1ff;color:#5848b4}.score strong{display:block;font-size:28px}.card{padding:28px;border:1px solid #dddfea;border-radius:26px;background:#fff;box-shadow:0 20px 60px rgba(42,44,74,.09)}fieldset{border:0;padding:0;margin:0 0 28px}legend{font-size:21px;font-weight:800;margin-bottom:4px}.hint{margin:4px 0 14px;color:#737688;font-size:13px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.wide{grid-column:1/-1}label{display:block;font-size:13px;font-weight:800}input,textarea{width:100%;margin-top:7px;padding:14px;border:1px solid #d8dae5;border-radius:13px;background:#fbfbfd;color:#242636;font:inherit}input:focus,textarea:focus{outline:3px solid #dcd6ff;border-color:#7566df}textarea{min-height:100px;resize:vertical}.choices{display:grid;grid-template-columns:repeat(2,1fr);gap:9px}.choice{display:flex;align-items:center;gap:8px;padding:11px;border:1px solid #dfe1ea;border-radius:12px;background:#fbfbfd}.choice input{width:auto;margin:0}.toggle{display:flex;align-items:flex-start;gap:10px;margin-top:12px;padding:12px;border:1px solid #dfe1ea;border-radius:13px;background:#fbfbfd}.toggle input{width:auto;margin-top:2px}.public-box{margin-top:14px;padding:15px;border:1px solid #dcd7ff;border-radius:16px;background:#f6f3ff}.public-box a{color:#5848b4;font-weight:850}.reward{color:#8a6800}.button{padding:15px 22px;border:0;border-radius:13px;background:linear-gradient(90deg,#5b8cff,#a044f2,#e9449f);color:#fff;font-weight:900;cursor:pointer}.msg,.err{padding:13px;border-radius:12px;margin-bottom:16px}.msg{background:#e4f7ed;color:#17623e}.err{background:#fde8ec;color:#8f2438}@media(max-width:700px){.shell{padding:18px}.heading{display:block}.score{margin-top:15px}.form-grid,.choices{grid-template-columns:1fr}.wide{grid-column:auto}}
</style>
</head>
<body>
<main class="shell">
    <header class="top"><strong>BEYOND ID - PROFILE</strong><a href="index.php">Dashboard</a></header>
    <div class="heading"><div><span>YOUR INFORMATION</span><h1>Complete your profile.</h1><p>Keep your information current across the Beyond ecosystem.</p></div><div class="score"><strong><?= $completion ?>%</strong>complete</div></div>
    <section class="card">
        <?php if ($message): ?><div class="msg"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <fieldset><legend>Personal details</legend><p class="hint">Basic details used across your connected apps.</p><div class="form-grid">
                <label>Display name<input name="display_name" autocomplete="nickname" value="<?= e($profile['display_name'] ?? $profile['first_name'] ?? '') ?>" required></label>
                <label>Email<input value="<?= e($profile['email'] ?? '') ?>" disabled></label>
                <label>Phone <span class="reward">- +10 bit$</span><input type="tel" name="phone" autocomplete="tel" value="<?= e($profile['phone'] ?? '') ?>"></label>
                <label>Birthday <span class="reward">- +10 bit$</span><input type="date" name="birthdate" value="<?= e($profile['birthdate'] ?? '') ?>"></label>
            </div></fieldset>
            <fieldset><legend>Address <span class="reward">- +50 bit$</span></legend><p class="hint">Optional. Complete the full address section once to earn the bonus.</p><div class="form-grid">
                <label class="wide">Street address<input name="address_line1" autocomplete="address-line1" value="<?= e($profile['address_line1'] ?? '') ?>"></label>
                <label class="wide">Apartment, suite, or unit<input name="address_line2" autocomplete="address-line2" value="<?= e($profile['address_line2'] ?? '') ?>"></label>
                <label>City<input name="city" autocomplete="address-level2" value="<?= e($profile['city'] ?? '') ?>"></label>
                <label>Province or state<input name="province" autocomplete="address-level1" value="<?= e($profile['province'] ?? '') ?>"></label>
                <label>Postal or ZIP code<input name="postal_code" autocomplete="postal-code" value="<?= e($profile['postal_code'] ?? '') ?>"></label>
                <label>Country<input name="country" autocomplete="country-name" value="<?= e($profile['country'] ?? '') ?>"></label>
            </div></fieldset>
            <fieldset><legend>Interests and goals <span class="reward">- core reward 100 bit$</span></legend><p class="hint">Choose interests and tell us what you want to accomplish.</p>
                <div class="choices"><?php foreach ($options as $option): ?><label class="choice"><input type="checkbox" name="interests[]" value="<?= e($option) ?>" <?= in_array($option, $selected, true) ? 'checked' : '' ?>><?= e($option) ?></label><?php endforeach; ?></div>
                <div class="form-grid"><label class="wide">Goals<textarea name="goals" placeholder="Learn consistently, build healthier habits, grow my business..."><?= e($profile['goals'] ?? '') ?></textarea></label><label class="wide">About you<textarea name="bio"><?= e($profile['bio'] ?? '') ?></textarea></label></div>
            </fieldset>
            <fieldset><legend>Public Beyond ID</legend><p class="hint">Create a shareable profile for badges, certificates, creator links, and public app presence.</p><div class="form-grid">
                <label>Public username<input name="username" pattern="[a-z0-9-]{3,40}" placeholder="your-name" value="<?= e($profile['username'] ?? '') ?>"></label>
                <label class="wide">Creator links<textarea name="creator_links" placeholder="https://example.com&#10;https://instagram.com/yourname"><?= e($profile['creator_links'] ?? '') ?></textarea></label>
            </div>
            <label class="toggle"><input type="checkbox" name="public_profile_enabled" value="1" <?= !empty($profile['public_profile_enabled']) ? 'checked' : '' ?>><span><strong>Publish my Beyond ID profile</strong><br><small class="hint">Only your display name, bio, interests, badges, certificates, creator links, and public app links are shown.</small></span></label>
            <?php if ($publicUrl): ?><p class="public-box">Public profile: <a href="<?= e($publicUrl) ?>"><?= e($publicUrl) ?></a></p><?php endif; ?>
            </fieldset>
            <button class="button" type="submit">Save profile</button>
        </form>
    </section>
</main>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>
