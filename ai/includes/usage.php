<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/visitor-analytics.php';

const JAGUAR_MONTHLY_REQUEST_LIMIT = 5;
const JAGUAR_MONTHLY_BIT_MICRO_LIMIT = 100000; // 0.10 BIT$, at 1 BIT$ = 1 Modal credit.
const JAGUAR_MODAL_L4_BIT_MICRO_PER_SECOND = 222; // Modal's listed $0.000222 / L4 GPU-second.

/** Load a persistent HMAC key from private storage; never fall back to a predictable key. */
function jaguar_usage_guest_key(): string
{
    $directory = beyond_analytics_private_root() . DIRECTORY_SEPARATOR . 'jaguar-usage';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Jaguar guest usage storage is unavailable.');
    }
    @chmod($directory, 0700);
    $path = $directory . DIRECTORY_SEPARATOR . 'guest-ip.key';
    $lock = @fopen($path . '.lock', 'c');
    if (!is_resource($lock) || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) fclose($lock);
        throw new RuntimeException('Jaguar guest usage key is unavailable.');
    }
    @chmod($path . '.lock', 0600);
    try {
        if (is_link($path)) throw new RuntimeException('Jaguar guest usage key path is invalid.');
        if (!is_file($path)) {
            $generated = bin2hex(random_bytes(32));
            if (file_put_contents($path, $generated, LOCK_EX) !== 64) {
                @unlink($path);
                throw new RuntimeException('Jaguar guest usage key could not be created.');
            }
            @chmod($path, 0600);
        }
        $key = trim((string)file_get_contents($path));
        $permissions = @fileperms($path);
        if (preg_match('/^[a-f0-9]{64}$/', $key) !== 1 || !is_int($permissions) || ($permissions & 0077) !== 0) {
            throw new RuntimeException('Jaguar guest usage key is invalid or not private.');
        }
        return $key;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** @return list<string> */
function jaguar_usage_trusted_proxy_cidrs(): array
{
    $environment = getenv('BEYOND_TRUSTED_PROXY_CIDRS');
    $configured = is_string($environment) && trim($environment) !== ''
        ? explode(',', $environment)
        : beyond_optional_config('security.trusted_proxy_cidrs', []);
    if (is_string($configured)) $configured = explode(',', $configured);
    if (!is_array($configured)) return [];
    return array_values(array_filter(array_map(static fn($cidr): string => trim((string)$cidr), $configured)));
}

function jaguar_usage_ip_matches_cidr(string $ip, string $cidr): bool
{
    $parts = explode('/', trim($cidr), 2);
    $network = @inet_pton(trim($parts[0]));
    $address = @inet_pton($ip);
    if ($network === false || $address === false || strlen($network) !== strlen($address)) return false;
    $maxBits = strlen($network) * 8;
    if (isset($parts[1]) && (!ctype_digit($parts[1]) || (int)$parts[1] > $maxBits)) return false;
    $bits = isset($parts[1]) ? (int)$parts[1] : $maxBits;
    if ($bits === 0) return false; // Never accept an internet-wide trust range.
    $wholeBytes = intdiv($bits, 8);
    if ($wholeBytes > 0 && substr($network, 0, $wholeBytes) !== substr($address, 0, $wholeBytes)) return false;
    $remainingBits = $bits % 8;
    if ($remainingBits === 0) return true;
    $mask = (0xff << (8 - $remainingBits)) & 0xff;
    return (ord($network[$wholeBytes]) & $mask) === (ord($address[$wholeBytes]) & $mask);
}

/** Trust the edge-provided client IP only when the direct peer matches an explicit allowlist. */
function jaguar_usage_client_ip(): string
{
    $peer = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($peer, FILTER_VALIDATE_IP) === false) {
        throw new RuntimeException('Jaguar guest IP is unavailable.');
    }
    foreach (jaguar_usage_trusted_proxy_cidrs() as $cidr) {
        if (!jaguar_usage_ip_matches_cidr($peer, $cidr)) continue;
        $forwarded = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if (filter_var($forwarded, FILTER_VALIDATE_IP) !== false) {
            $packed = @inet_pton($forwarded);
            if ($packed !== false) return inet_ntop($packed) ?: $forwarded;
        }
        break;
    }
    $packed = @inet_pton($peer);
    return $packed === false ? $peer : (inet_ntop($packed) ?: $peer);
}

function jaguar_usage_identity(bool $signedIn): string
{
    if ($signedIn) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId < 1) throw new RuntimeException('Signed-in Jaguar identity is unavailable.');
        return 'user:' . $userId;
    }

    // Never store the raw address; this dedicated key has no predictable fallback.
    return 'guest:' . hash_hmac('sha256', 'jaguar-monthly-guest|' . jaguar_usage_client_ip(), jaguar_usage_guest_key());
}

function jaguar_usage_period(): string
{
    return gmdate('Y-m');
}

/** @return array{requests:int,input_tokens:int,output_tokens:int,bit_micro:int,period:string} */
function jaguar_usage_read(PDO $pdo, string $identity, ?string $period = null): array
{
    $period ??= jaguar_usage_period();
    // Individual month buckets are only needed until the monthly reset.
    $pdo->prepare('DELETE FROM jaguar_monthly_usage WHERE period < ?')->execute([$period]);
    $statement = $pdo->prepare('SELECT request_count,reserved_request_count,input_tokens,output_tokens,modal_bit_micro_estimate FROM jaguar_monthly_usage WHERE identity_key=? AND period=? LIMIT 1');
    $statement->execute([$identity, $period]);
    $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    return [
        'requests' => (int)($row['request_count'] ?? 0),
        'reserved_requests' => (int)($row['reserved_request_count'] ?? 0),
        'input_tokens' => (int)($row['input_tokens'] ?? 0),
        'output_tokens' => (int)($row['output_tokens'] ?? 0),
        'bit_micro' => (int)($row['modal_bit_micro_estimate'] ?? 0),
        'period' => $period,
    ];
}

/** Reserve one of five monthly model requests atomically for an account or guest IP. */
function jaguar_usage_reserve(PDO $pdo, string $identity): array
{
    $period = jaguar_usage_period();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $now = time();
    $pdo->beginTransaction();
    try {
        $insert = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO jaguar_monthly_usage(identity_key,period,updated_at) VALUES(?,?,?)'
            : 'INSERT IGNORE INTO jaguar_monthly_usage(identity_key,period,updated_at) VALUES(?,?,?)';
        $pdo->prepare($insert)->execute([$identity, $period, $now]);
        $select = 'SELECT request_count,reserved_request_count,modal_bit_micro_estimate,updated_at FROM jaguar_monthly_usage WHERE identity_key=? AND period=?' . ($driver === 'mysql' ? ' FOR UPDATE' : '');
        $statement = $pdo->prepare($select);
        $statement->execute([$identity, $period]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: ['request_count' => 0, 'reserved_request_count' => 0, 'modal_bit_micro_estimate' => 0, 'updated_at' => $now];
        $used = (int)$row['request_count'];
        $reserved = (int)$row['reserved_request_count'];
        $bitMicro = (int)$row['modal_bit_micro_estimate'];
        // The runtime call deadline is 105 seconds. Expire reservations left
        // behind by a crashed PHP worker after a generous five-minute window.
        if ($reserved > 0 && $now - (int)$row['updated_at'] > 300) {
            $pdo->prepare('UPDATE jaguar_monthly_usage SET reserved_request_count=0,updated_at=? WHERE identity_key=? AND period=?')
                ->execute([$now, $identity, $period]);
            $reserved = 0;
        }
        if ($used + $reserved >= JAGUAR_MONTHLY_REQUEST_LIMIT || $bitMicro >= JAGUAR_MONTHLY_BIT_MICRO_LIMIT) {
            $pdo->commit();
            return array_merge(['allowed' => false], jaguar_usage_read($pdo, $identity, $period));
        }
        $pdo->prepare('UPDATE jaguar_monthly_usage SET reserved_request_count=reserved_request_count+1,updated_at=? WHERE identity_key=? AND period=?')
            ->execute([$now, $identity, $period]);
        $pdo->commit();
        return array_merge(['allowed' => true], jaguar_usage_read($pdo, $identity, $period));
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

/** Release an uncompleted model request so a transient runtime failure does not use monthly allowance. */
function jaguar_usage_release(PDO $pdo, string $identity, string $period): void
{
    $statement = $pdo->prepare('UPDATE jaguar_monthly_usage SET reserved_request_count=CASE WHEN reserved_request_count>0 THEN reserved_request_count-1 ELSE 0 END,updated_at=? WHERE identity_key=? AND period=?');
    $statement->execute([time(), $identity, $period]);
}

/** Count a completed runtime call even if its usage metadata was malformed. */
function jaguar_usage_consume_unmetered(PDO $pdo, string $identity, string $period): void
{
    $statement = $pdo->prepare('UPDATE jaguar_monthly_usage SET request_count=request_count+1,reserved_request_count=CASE WHEN reserved_request_count>0 THEN reserved_request_count-1 ELSE 0 END,updated_at=? WHERE identity_key=? AND period=?');
    $statement->execute([time(), $identity, $period]);
}

/** Save exact tokenizer counts and a GPU-only Modal BIT$ estimate for the request. */
function jaguar_usage_settle(PDO $pdo, string $identity, string $period, int $inputTokens, int $outputTokens, float $gpuSeconds): array
{
    $bitMicro = max(0, (int)ceil(max(0.0, $gpuSeconds) * JAGUAR_MODAL_L4_BIT_MICRO_PER_SECOND));
    $statement = $pdo->prepare('UPDATE jaguar_monthly_usage SET request_count=request_count+1,reserved_request_count=CASE WHEN reserved_request_count>0 THEN reserved_request_count-1 ELSE 0 END,input_tokens=input_tokens+?,output_tokens=output_tokens+?,modal_bit_micro_estimate=modal_bit_micro_estimate+?,updated_at=? WHERE identity_key=? AND period=?');
    $statement->execute([max(0, $inputTokens), max(0, $outputTokens), $bitMicro, time(), $identity, $period]);
    return jaguar_usage_read($pdo, $identity, $period);
}

/** Read the signed-in user's current closed-loop BIT$ wallet balance. */
function jaguar_wallet_bit_balance(PDO $pdo, int $userId): float
{
    $statement = $pdo->prepare("SELECT balance FROM beyond_wallets WHERE user_id=? AND currency='BITS' AND status='active' LIMIT 1");
    $statement->execute([$userId]);
    $balance = $statement->fetchColumn();
    return is_numeric($balance) ? max(0.0, (float)$balance) : 0.0;
}

/** @return array{requests:int,request_limit:int,bit_dollars_left:float,period:string,wallet_bit_balance?:float} */
function jaguar_usage_public(array $usage, ?float $walletBalance = null): array
{
    $public = [
        'requests' => $usage['requests'],
        'request_limit' => JAGUAR_MONTHLY_REQUEST_LIMIT,
        'bit_dollars_left' => max(0.0, (JAGUAR_MONTHLY_BIT_MICRO_LIMIT - $usage['bit_micro']) / 1000000),
        'period' => $usage['period'],
    ];
    if ($walletBalance !== null) $public['wallet_bit_balance'] = $walletBalance;
    return $public;
}
