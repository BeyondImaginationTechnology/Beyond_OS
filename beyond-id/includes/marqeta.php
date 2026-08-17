<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

final class BeyondMarqetaException extends RuntimeException
{
    public function __construct(string $message, private readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}

function beyond_marqeta_config(): array
{
    $private = [];
    try {
        $candidate = beyond_config('payments.marqeta', []);
        if (is_array($candidate)) $private = $candidate;
    } catch (Throwable $exception) {
        // Environment-only installations do not require a private config file.
    }

    $value = static function (string $environmentName, string $privateKey, string $fallback = '') use ($private): string {
        $environmentValue = getenv($environmentName);
        if (is_string($environmentValue) && trim($environmentValue) !== '') return trim($environmentValue);
        $privateValue = $private[$privateKey] ?? $fallback;
        return is_scalar($privateValue) ? trim((string)$privateValue) : $fallback;
    };

    $environment = strtolower($value('BEYOND_MARQETA_ENVIRONMENT', 'environment', 'off'));
    if (!in_array($environment, ['off', 'sandbox', 'production'], true)) $environment = 'off';

    $config = [
        'environment' => $environment,
        'base_url' => $environment === 'production'
            ? 'https://api.marqeta.com/v3'
            : 'https://sandbox-api.marqeta.com/v3',
        'application_token' => $value('BEYOND_MARQETA_APPLICATION_TOKEN', 'application_token'),
        'admin_access_token' => $value('BEYOND_MARQETA_ADMIN_ACCESS_TOKEN', 'admin_access_token'),
        'card_product_token' => $value('BEYOND_MARQETA_CARD_PRODUCT_TOKEN', 'card_product_token'),
        'webhook_secret' => $value('BEYOND_MARQETA_WEBHOOK_SECRET', 'webhook_secret'),
        'webhook_username' => $value('BEYOND_MARQETA_WEBHOOK_USERNAME', 'webhook_username'),
        'webhook_password' => $value('BEYOND_MARQETA_WEBHOOK_PASSWORD', 'webhook_password'),
        'issuer' => strtolower($value('BEYOND_CARD_ISSUER', 'issuer', 'peoples_trust')),
        'production_enabled' => strtolower($value('BEYOND_MARQETA_PRODUCTION_ENABLED', 'production_enabled', 'false')) === 'true',
    ];
    $config['configured'] = $environment !== 'off'
        && ($environment !== 'production' || $config['production_enabled'])
        && $config['application_token'] !== ''
        && $config['admin_access_token'] !== '';
    $config['card_configured'] = $config['configured'] && $config['card_product_token'] !== '';
    $config['webhook_configured'] = $config['webhook_secret'] !== ''
        && $config['webhook_username'] !== ''
        && $config['webhook_password'] !== '';
    return $config;
}

function beyond_marqeta_request(string $method, string $path, ?array $payload = null): array
{
    $config = beyond_marqeta_config();
    if (!$config['configured']) throw new RuntimeException('Marqeta sandbox credentials are not configured.');
    if (!preg_match('#^/[A-Za-z0-9_./?=&%+-]*$#', $path)) throw new InvalidArgumentException('Invalid Marqeta API path.');

    $method = strtoupper($method);
    if (!in_array($method, ['GET', 'POST', 'PUT'], true)) throw new InvalidArgumentException('Unsupported Marqeta API method.');
    $url = rtrim((string)$config['base_url'], '/') . $path;
    $body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload !== null && !is_string($body)) throw new RuntimeException('Unable to encode Marqeta request.');
    $headers = ['Accept: application/json', 'Content-Type: application/json', 'User-Agent: Beyond-Wallet/2.3.4'];
    $status = 0;
    $raw = false;
    $transportError = '';

    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $config['application_token'] . ':' . $config['admin_access_token'],
        ]);
        if ($body !== null) curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $transportError = (string)curl_error($handle);
        curl_close($handle);
    } else {
        $headers[] = 'Authorization: Basic ' . base64_encode($config['application_token'] . ':' . $config['admin_access_token']);
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'timeout' => 15,
            'ignore_errors' => true,
        ]]);
        $raw = @file_get_contents($url, false, $context);
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $matches)) {
                $status = (int)$matches[1];
                break;
            }
        }
    }

    if (!is_string($raw)) throw new BeyondMarqetaException($transportError ?: 'Marqeta is unavailable.', $status);
    $decoded = $raw === '' ? [] : json_decode($raw, true);
    if (!is_array($decoded)) throw new BeyondMarqetaException('Marqeta returned invalid JSON.', $status);
    if ($status < 200 || $status >= 300) {
        $providerMessage = trim((string)($decoded['error_message'] ?? $decoded['message'] ?? ''));
        throw new BeyondMarqetaException($providerMessage !== '' ? $providerMessage : 'Marqeta returned HTTP ' . $status . '.', $status);
    }
    return $decoded;
}

function beyond_card_customer(PDO $pdo, int $userId): ?array
{
    $statement = $pdo->prepare('SELECT * FROM card_program_customers WHERE user_id=? LIMIT 1');
    $statement->execute([$userId]);
    $customer = $statement->fetch(PDO::FETCH_ASSOC);
    return is_array($customer) ? $customer : null;
}

function beyond_card_cards(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare('SELECT id,provider_card_token,state,fulfillment_status,last_four,expiration_time,currency,created_at FROM payment_cards WHERE user_id=? ORDER BY created_at DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function beyond_card_program_state(PDO $pdo, int $userId): array
{
    return [
        'config' => beyond_marqeta_config(),
        'customer' => beyond_card_customer($pdo, $userId),
        'cards' => beyond_card_cards($pdo, $userId),
    ];
}

function beyond_card_new_token(string $prefix): string
{
    return substr($prefix . bin2hex(random_bytes(16)), 0, 36);
}

function beyond_marqeta_card_product_token(): string
{
    $config = beyond_marqeta_config();
    if ($config['card_product_token'] !== '') return (string)$config['card_product_token'];
    $response = beyond_marqeta_request('GET', '/cardproducts?count=1&sort_by=-lastModifiedTime');
    $product = $response['data'][0] ?? null;
    $token = is_array($product) ? trim((string)($product['token'] ?? '')) : '';
    if ($token === '') throw new RuntimeException('No Marqeta sandbox card product is available.');
    return $token;
}

function beyond_card_update_customer(PDO $pdo, int $customerId, string $status): void
{
    $statement = $pdo->prepare('UPDATE card_program_customers SET provider_status=?,last_synced_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $statement->execute([strtoupper(substr($status, 0, 32)), $customerId]);
}

function beyond_card_enroll(PDO $pdo, int $userId, string $consentVersion = '2026-07-30'): array
{
    $config = beyond_marqeta_config();
    if (!$config['configured']) throw new RuntimeException('Marqeta sandbox credentials are not configured yet.');

    $customer = beyond_card_customer($pdo, $userId);
    if (!$customer) {
        $token = beyond_card_new_token('bosu_');
        $statement = $pdo->prepare(
            'INSERT INTO card_program_customers(user_id,issuer,processor,provider_user_token,provider_status,consent_version,consented_at) VALUES(?,?,?,?,?,?,CURRENT_TIMESTAMP)'
        );
        $statement->execute([$userId, $config['issuer'], 'marqeta', $token, 'PENDING', $consentVersion]);
        $customer = beyond_card_customer($pdo, $userId);
    }
    if (!$customer) throw new RuntimeException('Unable to initialize the card application.');

    try {
        $providerUser = beyond_marqeta_request('GET', '/users/' . rawurlencode((string)$customer['provider_user_token']));
    } catch (BeyondMarqetaException $exception) {
        if ($exception->httpStatus() !== 404) throw $exception;
        $statement = $pdo->prepare('SELECT first_name,last_name,name,email FROM users WHERE id=? LIMIT 1');
        $statement->execute([$userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($user)) throw new RuntimeException('Beyond ID account not found.');
        $displayParts = preg_split('/\s+/', trim((string)($user['name'] ?? '')), 2) ?: [];
        $firstName = trim((string)($user['first_name'] ?? '')) ?: trim((string)($displayParts[0] ?? 'Beyond'));
        $lastName = trim((string)($user['last_name'] ?? '')) ?: trim((string)($displayParts[1] ?? 'Member'));
        $providerUser = beyond_marqeta_request('POST', '/users', [
            'token' => (string)$customer['provider_user_token'],
            'first_name' => substr($firstName, 0, 40),
            'last_name' => substr($lastName, 0, 40),
            'email' => substr((string)$user['email'], 0, 255),
            'metadata' => ['beyond_user_id' => (string)$userId],
        ]);
    }

    beyond_card_update_customer($pdo, (int)$customer['id'], (string)($providerUser['status'] ?? 'PENDING'));
    return beyond_card_customer($pdo, $userId) ?? $customer;
}

function beyond_card_upsert_card(PDO $pdo, array $customer, array $card): void
{
    $cardToken = trim((string)($card['token'] ?? $card['card_token'] ?? ''));
    if ($cardToken === '') return;
    $lastFour = preg_match('/^\d{4}$/', (string)($card['last_four'] ?? '')) ? (string)$card['last_four'] : null;
    $values = [
        (int)$customer['user_id'],
        (int)$customer['id'],
        'marqeta',
        substr($cardToken, 0, 36),
        substr((string)($card['card_product_token'] ?? beyond_marqeta_config()['card_product_token']), 0, 36),
        substr(strtoupper((string)($card['state'] ?? 'UNACTIVATED')), 0, 32),
        isset($card['fulfillment_status']) ? substr(strtoupper((string)$card['fulfillment_status']), 0, 32) : null,
        $lastFour,
        isset($card['expiration_time']) ? substr((string)$card['expiration_time'], 0, 40) : null,
        substr(strtoupper((string)($card['currency'] ?? 'CAD')), 0, 3),
    ];
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $sql = 'INSERT INTO payment_cards(user_id,card_program_customer_id,processor,provider_card_token,card_product_token,state,fulfillment_status,last_four,expiration_time,currency)
            VALUES(?,?,?,?,?,?,?,?,?,?)
            ON CONFLICT(provider_card_token) DO UPDATE SET state=excluded.state,fulfillment_status=excluded.fulfillment_status,last_four=excluded.last_four,expiration_time=excluded.expiration_time,updated_at=CURRENT_TIMESTAMP';
    } else {
        $sql = 'INSERT INTO payment_cards(user_id,card_program_customer_id,processor,provider_card_token,card_product_token,state,fulfillment_status,last_four,expiration_time,currency)
            VALUES(?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE state=VALUES(state),fulfillment_status=VALUES(fulfillment_status),last_four=VALUES(last_four),expiration_time=VALUES(expiration_time),updated_at=CURRENT_TIMESTAMP';
    }
    $pdo->prepare($sql)->execute($values);
}

function beyond_card_issue(PDO $pdo, int $userId): array
{
    $state = beyond_card_program_state($pdo, $userId);
    $customer = $state['customer'];
    if (!$customer) throw new RuntimeException('Connect your cardholder profile first.');
    if (strtoupper((string)$customer['provider_status']) !== 'ACTIVE') {
        throw new RuntimeException('The Marqeta cardholder must be active before a card can be issued.');
    }
    if ($state['cards']) return $state['cards'][0];

    $config = $state['config'];
    if (!$config['configured']) throw new RuntimeException('Marqeta sandbox credentials are not configured yet.');
    $cardProductToken = beyond_marqeta_card_product_token();
    $card = beyond_marqeta_request('POST', '/cards', [
        'token' => beyond_card_new_token('bosc_'),
        'user_token' => (string)$customer['provider_user_token'],
        'card_product_token' => $cardProductToken,
        'metadata' => ['beyond_user_id' => (string)$userId],
    ]);
    beyond_card_upsert_card($pdo, $customer, $card);
    $cards = beyond_card_cards($pdo, $userId);
    if (!$cards) throw new RuntimeException('Marqeta created no card record.');
    return $cards[0];
}

function beyond_card_sync(PDO $pdo, int $userId): array
{
    $customer = beyond_card_customer($pdo, $userId);
    if (!$customer) throw new RuntimeException('Connect your cardholder profile first.');
    $providerUser = beyond_marqeta_request('GET', '/users/' . rawurlencode((string)$customer['provider_user_token']));
    beyond_card_update_customer($pdo, (int)$customer['id'], (string)($providerUser['status'] ?? $customer['provider_status']));
    $response = beyond_marqeta_request('GET', '/cards/user/' . rawurlencode((string)$customer['provider_user_token']) . '?count=100');
    foreach ((array)($response['data'] ?? []) as $card) {
        if (is_array($card)) beyond_card_upsert_card($pdo, $customer, $card);
    }
    return beyond_card_program_state($pdo, $userId);
}

function beyond_card_event_summary(array $event): array
{
    $allowed = [
        'token', 'type', 'state', 'status', 'reason', 'reason_code', 'channel',
        'user_token', 'card_token', 'card_product_token', 'fulfillment_status',
        'last_four', 'expiration_time', 'created_time', 'last_modified_time',
    ];
    $summary = [];
    foreach ($allowed as $key) {
        if (isset($event[$key]) && is_scalar($event[$key])) $summary[$key] = (string)$event[$key];
    }
    return $summary;
}

function beyond_card_record_event(PDO $pdo, string $collection, array $event): void
{
    $summary = beyond_card_event_summary($event);
    $eventKey = trim((string)($event['token'] ?? ''));
    if ($eventKey === '') $eventKey = hash('sha256', $collection . ':' . json_encode($summary, JSON_UNESCAPED_SLASHES));
    $eventType = substr($collection . '.' . (string)($event['type'] ?? $event['status'] ?? $event['state'] ?? 'event'), 0, 80);
    $payload = json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $sql = $driver === 'sqlite'
        ? 'INSERT OR IGNORE INTO card_provider_events(processor,event_key,event_type,payload_json,processed_at) VALUES(?,?,?,?,CURRENT_TIMESTAMP)'
        : 'INSERT IGNORE INTO card_provider_events(processor,event_key,event_type,payload_json,processed_at) VALUES(?,?,?,?,CURRENT_TIMESTAMP)';
    $pdo->prepare($sql)->execute(['marqeta', substr($eventKey, 0, 190), $eventType, $payload]);
}

function beyond_card_process_webhook(PDO $pdo, array $payload): void
{
    $pdo->beginTransaction();
    try {
        foreach ($payload as $collection => $events) {
            if (!is_array($events)) continue;
            foreach ($events as $event) {
                if (!is_array($event)) continue;
                beyond_card_record_event($pdo, (string)$collection, $event);

                if ($collection === 'usertransitions' && !empty($event['user_token'])) {
                    $statement = $pdo->prepare('UPDATE card_program_customers SET provider_status=?,last_synced_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE provider_user_token=?');
                    $statement->execute([
                        substr(strtoupper((string)($event['status'] ?? 'PENDING')), 0, 32),
                        (string)$event['user_token'],
                    ]);
                }

                if ($collection === 'cards' && !empty($event['user_token']) && !empty($event['card_token'])) {
                    $statement = $pdo->prepare('SELECT * FROM card_program_customers WHERE provider_user_token=? LIMIT 1');
                    $statement->execute([(string)$event['user_token']]);
                    $customer = $statement->fetch(PDO::FETCH_ASSOC);
                    if (is_array($customer)) beyond_card_upsert_card($pdo, $customer, $event);
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
