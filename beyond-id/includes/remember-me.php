<?php
declare(strict_types=1);

const BEYOND_REMEMBER_COOKIE = 'beyond_id_remember';
const BEYOND_REMEMBER_YEARS = 0;
const BEYOND_REMEMBER_DAYS = 30;

function beyondRememberCookieOptions(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function beyondRememberIssue(PDO $pdo, int $userId): void
{
    if ($userId < 1) {
        throw new InvalidArgumentException('A valid user ID is required.');
    }

    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $agentHash = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $expires = new DateTimeImmutable('+' . BEYOND_REMEMBER_DAYS . ' days', new DateTimeZone('UTC'));

    $pdo->prepare(
        'INSERT INTO auth_remember_tokens
         (user_id, selector, validator_hash, user_agent_hash, expires_at)
         VALUES (:user_id, :selector, :validator_hash, :agent_hash, :expires_at)'
    )->execute([
        'user_id' => $userId,
        'selector' => $selector,
        'validator_hash' => $validatorHash,
        'agent_hash' => $agentHash,
        'expires_at' => $expires->format('Y-m-d H:i:s'),
    ]);

    setcookie(
        BEYOND_REMEMBER_COOKIE,
        $selector . ':' . $validator,
        beyondRememberCookieOptions($expires->getTimestamp())
    );
}

function beyondRememberRestore(PDO $pdo): ?int
{
    $cookie = (string) ($_COOKIE[BEYOND_REMEMBER_COOKIE] ?? '');
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $parts)) {
        return null;
    }

    [, $selector, $validator] = $parts;
    $query = $pdo->prepare(
        'SELECT id, user_id, validator_hash, user_agent_hash
           FROM auth_remember_tokens
          WHERE selector = :selector
            AND revoked_at IS NULL
            AND expires_at >= CURRENT_TIMESTAMP
          LIMIT 1'
    );
    $query->execute(['selector' => $selector]);
    $token = $query->fetch(PDO::FETCH_ASSOC);

    $agentHash = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $valid = $token
        && hash_equals((string) $token['validator_hash'], hash('sha256', $validator))
        && hash_equals((string) $token['user_agent_hash'], $agentHash);

    if (!$valid) {
        beyondRememberForget($pdo);
        return null;
    }

    $pdo->prepare(
        'UPDATE auth_remember_tokens
            SET revoked_at = CURRENT_TIMESTAMP,
                last_used_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
          WHERE id = :id'
    )->execute(['id' => $token['id']]);

    beyondRememberIssue($pdo, (int) $token['user_id']);
    return (int) $token['user_id'];
}

function beyondRememberForget(PDO $pdo): void
{
    $cookie = (string) ($_COOKIE[BEYOND_REMEMBER_COOKIE] ?? '');
    if (preg_match('/^([a-f0-9]{24}):/', $cookie, $parts)) {
        $pdo->prepare(
            'UPDATE auth_remember_tokens
                SET revoked_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
              WHERE selector = :selector'
        )->execute(['selector' => $parts[1]]);
    }

    setcookie(BEYOND_REMEMBER_COOKIE, '', beyondRememberCookieOptions(time() - 3600));
    unset($_COOKIE[BEYOND_REMEMBER_COOKIE]);
}

function beyondRememberRevokeAll(PDO $pdo, int $userId): void
{
    $pdo->prepare(
        'UPDATE auth_remember_tokens
            SET revoked_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
          WHERE user_id = :user_id AND revoked_at IS NULL'
    )->execute(['user_id' => $userId]);
}
