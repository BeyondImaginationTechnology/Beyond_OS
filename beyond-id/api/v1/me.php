<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$claims = beyond_v1_bearer($pdo, 'profile:read');
$statement = $pdo->prepare('SELECT u.id,u.name,u.first_name,u.last_name,u.email,u.status,u.preferred_locale,u.created_at,u.updated_at,p.display_name,p.avatar FROM users u LEFT JOIN profiles p ON p.user_id=u.id WHERE u.id=? LIMIT 1');
$statement->execute([(int)$claims['user_id']]);
$user = $statement->fetch(PDO::FETCH_ASSOC);
if (!$user || ($user['status'] ?? '') !== 'active') {
    beyond_v1_error(403, 'account_unavailable', 'This Beyond ID is not active.');
}
$data = [
    'id' => (string)$user['id'],
    'status' => 'active',
    'display_name' => (string)($user['display_name'] ?: $user['name'] ?: trim((string)$user['first_name'] . ' ' . (string)$user['last_name'])),
    'first_name' => (string)($user['first_name'] ?? ''),
    'last_name' => (string)($user['last_name'] ?? ''),
    'locale' => (string)($user['preferred_locale'] ?: 'en'),
    'avatar_url' => $user['avatar'] !== null ? (string)$user['avatar'] : null,
    'created_at' => (new DateTimeImmutable((string)$user['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
    'updated_at' => $user['updated_at'] ? (new DateTimeImmutable((string)$user['updated_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z') : null,
];
if (in_array('email:read', $claims['scopes'], true)) $data['email'] = (string)$user['email'];
beyond_v1_json(200, ['ok' => true, 'data' => $data]);
