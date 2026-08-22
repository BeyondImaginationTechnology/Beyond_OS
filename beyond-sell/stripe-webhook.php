<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/marketplace-stripe.php';
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok'=>false]);
    exit;
}

$payload = (string)file_get_contents('php://input');
$signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$secret = (string)beyond_config('stripe.webhook_secret', '');
if (!marketplace_stripe_signature_valid($payload, $signature, $secret)) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Invalid signature']);
    exit;
}
$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'Invalid event']);
    exit;
}

$pdo = beyond_db();
$eventId = (string)$event['id'];
$eventType = (string)$event['type'];
try {
    $insert = $pdo->prepare('INSERT INTO stripe_events (stripe_event_id,event_type,payload_json) VALUES (?,?,?)');
    $insert->execute([$eventId, $eventType, $payload]);
} catch (PDOException $exception) {
    if ((string)$exception->getCode() !== '23000') throw $exception;
    $existing = $pdo->prepare('SELECT processed FROM stripe_events WHERE stripe_event_id=? LIMIT 1');
    $existing->execute([$eventId]);
    if ((int)$existing->fetchColumn() === 1) {
        echo json_encode(['ok'=>true, 'duplicate'=>true]);
        exit;
    }
}

try {
    $session = is_array($event['data']['object'] ?? null) ? $event['data']['object'] : [];
    if (in_array($eventType, ['checkout.session.completed','checkout.session.async_payment_succeeded'], true)) {
        marketplace_fulfill_checkout($pdo, $session);
    } elseif (in_array($eventType, ['checkout.session.async_payment_failed','checkout.session.expired'], true)) {
        $orderId = (int)($session['metadata']['order_id'] ?? 0);
        if ($orderId > 0) marketplace_fail_order($pdo, $orderId);
    }
    $pdo->prepare('UPDATE stripe_events SET processed=1,processing_error=NULL,processed_at=CURRENT_TIMESTAMP WHERE stripe_event_id=?')->execute([$eventId]);
    echo json_encode(['ok'=>true]);
} catch (Throwable $exception) {
    error_log('Beyond Market Stripe webhook failed: ' . $exception->getMessage());
    $pdo->prepare('UPDATE stripe_events SET processing_error=? WHERE stripe_event_id=?')->execute([mb_substr($exception->getMessage(), 0, 2000), $eventId]);
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
