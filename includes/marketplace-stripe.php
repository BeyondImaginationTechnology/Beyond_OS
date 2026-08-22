<?php
declare(strict_types=1);

require_once __DIR__ . '/ecosystem.php';
require_once __DIR__ . '/../config/bootstrap.php';

function marketplace_stripe_ready(): bool
{
    try {
        $secret = (string)beyond_config('stripe.secret_key', '');
        $webhook = (string)beyond_config('stripe.webhook_secret', '');
        return strlen($secret) > 20 && strlen($webhook) > 20 && str_starts_with($secret, 'sk_') && str_starts_with($webhook, 'whsec_');
    } catch (Throwable $exception) {
        return false;
    }
}

function marketplace_absolute_url(string $path = ''): string
{
    $configured = rtrim((string)beyond_config('app.url', ''), '/');
    if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
        return $configured . beyond_url($path);
    }
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $host)) {
        throw new RuntimeException('Set app.url in the protected live configuration before enabling checkout.');
    }
    return (beyond_is_https() ? 'https://' : 'http://') . $host . beyond_url($path);
}

function marketplace_stripe_request(string $method, string $endpoint, array $parameters = [], array $requestHeaders = []): array
{
    $secret = (string)beyond_config('stripe.secret_key', '');
    if (!str_starts_with($secret, 'sk_')) {
        throw new RuntimeException('Stripe is not configured. Add stripe.secret_key to the protected live configuration.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The PHP cURL extension is required for Stripe Checkout.');
    }

    $url = 'https://api.stripe.com/v1/' . ltrim($endpoint, '/');
    $curl = curl_init();
    if ($curl === false) throw new RuntimeException('Could not initialize the Stripe connection.');
    $headers = ['Authorization: Bearer ' . $secret, 'Accept: application/json', ...$requestHeaders];
    $method = strtoupper($method);
    if ($method === 'GET' && $parameters) $url .= '?' . http_build_query($parameters);
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);
    if ($method !== 'GET') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [...$headers, 'Content-Type: application/x-www-form-urlencoded']);
    }
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if (!is_string($body)) throw new RuntimeException('Stripe could not be reached. ' . $error);
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) throw new RuntimeException('Stripe returned an unreadable response.');
    if ($status < 200 || $status >= 300) {
        $message = (string)($decoded['error']['message'] ?? 'Stripe rejected the checkout request.');
        throw new RuntimeException($message);
    }
    return $decoded;
}

function marketplace_stripe_signature_valid(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    if ($payload === '' || $header === '' || !str_starts_with($secret, 'whsec_')) return false;
    $timestamp = 0;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't') $timestamp = (int)$value;
        if ($key === 'v1' && $value !== '') $signatures[] = $value;
    }
    if ($timestamp <= 0 || abs(time() - $timestamp) > $tolerance) return false;
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) return true;
    }
    return false;
}

function marketplace_order_number(): string
{
    return 'BM-' . gmdate('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function marketplace_create_order(PDO $pdo, array $listing, int $buyerId): array
{
    $amount = round((float)$listing['price_cash'], 2);
    $currency = strtoupper((string)$listing['currency']);
    $orderNumber = marketplace_order_number();
    $fulfillment = (string)$listing['item_type'] === 'digital' ? 'pending' : 'processing';
    $pdo->beginTransaction();
    try {
        $order = $pdo->prepare("INSERT INTO orders (order_number,buyer_id,payment_method,currency,subtotal_cash,total_cash,status) VALUES (?,?,?,?,?,?,'pending_payment')");
        $order->execute([$orderNumber, $buyerId, 'stripe', $currency, $amount, $amount]);
        $orderId = (int)$pdo->lastInsertId();
        $item = $pdo->prepare('INSERT INTO order_items (order_id,listing_id,seller_id,title_snapshot,item_type,quantity,unit_price_cash,line_total_cash,fulfillment_status) VALUES (?,?,?,?,?,1,?,?,?)');
        $item->execute([$orderId, (int)$listing['id'], (int)$listing['seller_id'], (string)$listing['title'], (string)$listing['item_type'], $amount, $amount, $fulfillment]);
        $payment = $pdo->prepare('INSERT INTO payments (order_id,user_id,provider,amount_cash,currency,status) VALUES (?,?,?,?,?,?)');
        $payment->execute([$orderId, $buyerId, 'stripe', $amount, $currency, 'created']);
        $pdo->commit();
        return ['id'=>$orderId, 'number'=>$orderNumber, 'amount'=>$amount, 'currency'=>$currency];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

function marketplace_attach_checkout(PDO $pdo, int $orderId, string $sessionId): void
{
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE orders SET stripe_checkout_session_id=? WHERE id=? AND status=\'pending_payment\'')->execute([$sessionId, $orderId]);
        $pdo->prepare("UPDATE payments SET provider_reference=?,status='pending' WHERE order_id=? AND provider='stripe'")->execute([$sessionId, $orderId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

function marketplace_fail_order(PDO $pdo, int $orderId): void
{
    $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND status='pending_payment'")->execute([$orderId]);
    $pdo->prepare("UPDATE payments SET status='failed' WHERE order_id=? AND provider='stripe' AND status IN ('created','pending')")->execute([$orderId]);
}

function marketplace_fulfill_checkout(PDO $pdo, array $session): bool
{
    $sessionId = (string)($session['id'] ?? '');
    $orderId = (int)($session['metadata']['order_id'] ?? 0);
    if ($sessionId === '' || $orderId <= 0) throw new RuntimeException('Checkout session is missing Beyond Market order metadata.');

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('SELECT id,buyer_id,currency,total_cash,status,stripe_checkout_session_id FROM orders WHERE id=? FOR UPDATE');
        $statement->execute([$orderId]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$order || !hash_equals((string)$order['stripe_checkout_session_id'], $sessionId)) {
            throw new RuntimeException('Checkout session does not match the local order.');
        }
        if (in_array((string)$order['status'], ['paid','processing','ready_to_ship','shipped','delivered','completed'], true)) {
            $pdo->commit();
            return true;
        }
        $expectedAmount = (int)round((float)$order['total_cash'] * 100);
        $actualAmount = (int)($session['amount_total'] ?? -1);
        $actualCurrency = strtoupper((string)($session['currency'] ?? ''));
        if ($expectedAmount !== $actualAmount || $actualCurrency !== strtoupper((string)$order['currency'])) {
            throw new RuntimeException('Stripe amount or currency does not match the local order.');
        }
        if ((string)($session['payment_status'] ?? '') === 'unpaid') {
            $pdo->commit();
            return false;
        }
        $paymentIntent = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null;
        $pdo->prepare("UPDATE orders SET status='paid',stripe_payment_intent_id=?,placed_at=COALESCE(placed_at,CURRENT_TIMESTAMP) WHERE id=?")->execute([$paymentIntent, $orderId]);
        $pdo->prepare("UPDATE payments SET provider_reference=COALESCE(?,provider_reference),status='succeeded',paid_at=COALESCE(paid_at,CURRENT_TIMESTAMP) WHERE order_id=? AND provider='stripe'")->execute([$paymentIntent ?: $sessionId, $orderId]);
        $pdo->prepare("UPDATE order_items SET fulfillment_status=CASE WHEN item_type='digital' THEN 'download_ready' ELSE 'processing' END WHERE order_id=?")->execute([$orderId]);
        $items = $pdo->prepare('SELECT listing_id,quantity FROM order_items WHERE order_id=?');
        $items->execute([$orderId]);
        foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $pdo->prepare("UPDATE listings SET status=CASE WHEN quantity<=? THEN 'sold' ELSE status END,quantity=GREATEST(quantity-?,0) WHERE id=?")->execute([(int)$item['quantity'], (int)$item['quantity'], (int)$item['listing_id']]);
        }
        if (function_exists('create_notification')) {
            create_notification($pdo, (int)$order['buyer_id'], 'Payment received', 'Your Beyond Market order is confirmed.', beyond_url('beyond-sell/orders.php'), 'commerce');
        }
        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
