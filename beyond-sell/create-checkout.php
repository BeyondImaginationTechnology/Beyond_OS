<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/marketplace-stripe.php';
require_beyond_id();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . beyond_url('beyond-market/'));
    exit;
}
beyond_require_csrf();

$listingId = max(0, (int)($_POST['listing_id'] ?? 0));
$buyerId = (int)$_SESSION['user_id'];
$pdo = beyond_db();
$orderId = 0;

try {
    if (!marketplace_stripe_ready()) {
        throw new RuntimeException('Stripe checkout is not configured yet.');
    }
    $statement = $pdo->prepare("SELECT id,seller_id,title,short_description,item_type,listing_type,price_cash,currency,quantity,status FROM listings WHERE id=? AND status='active' LIMIT 1");
    $statement->execute([$listingId]);
    $listing = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$listing || (float)$listing['price_cash'] < .50 || (int)$listing['quantity'] < 1) {
        throw new RuntimeException('This listing is not available for Stripe checkout.');
    }
    if ((int)$listing['seller_id'] === $buyerId) {
        throw new RuntimeException('You cannot purchase your own listing.');
    }
    if (!in_array((string)$listing['listing_type'], ['buy_now','buy_now_auction','digital','service'], true)) {
        throw new RuntimeException('Auction-only listings cannot use immediate checkout.');
    }
    if (!in_array(strtolower((string)$listing['currency']), ['usd','cad'], true)) {
        throw new RuntimeException('This listing uses a currency that is not enabled for checkout.');
    }

    $order = marketplace_create_order($pdo, $listing, $buyerId);
    $orderId = (int)$order['id'];
    $parameters = [
        'mode' => 'payment',
        'success_url' => marketplace_absolute_url('beyond-sell/checkout-success.php?session_id={CHECKOUT_SESSION_ID}'),
        'cancel_url' => marketplace_absolute_url('beyond-sell/checkout.php?id=' . $listingId . '&method=stripe&cancelled=1'),
        'client_reference_id' => (string)$orderId,
        'customer_email' => filter_var((string)($_SESSION['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? (string)$_SESSION['email'] : null,
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower((string)$order['currency']),
                'unit_amount' => (int)round((float)$order['amount'] * 100),
                'product_data' => [
                    'name' => (string)$listing['title'],
                    'description' => mb_substr((string)($listing['short_description'] ?: 'Beyond Market creator listing'), 0, 500),
                    'metadata' => ['listing_id'=>(string)$listingId],
                ],
            ],
        ]],
        'metadata' => [
            'order_id' => (string)$orderId,
            'order_number' => (string)$order['number'],
            'buyer_id' => (string)$buyerId,
            'listing_id' => (string)$listingId,
        ],
        'payment_intent_data' => ['metadata'=>['order_id'=>(string)$orderId, 'order_number'=>(string)$order['number']]],
    ];
    if ($parameters['customer_email'] === null) unset($parameters['customer_email']);
    if ((string)$listing['item_type'] === 'physical') {
        $parameters['shipping_address_collection'] = ['allowed_countries'=>['CA','US']];
    }
    $session = marketplace_stripe_request('POST', 'checkout/sessions', $parameters, ['Idempotency-Key: beyond-market-' . $order['number']]);
    $sessionId = (string)($session['id'] ?? '');
    $checkoutUrl = (string)($session['url'] ?? '');
    if ($sessionId === '' || !preg_match('#^https://checkout\.stripe\.com/#', $checkoutUrl)) {
        throw new RuntimeException('Stripe did not return a valid Checkout URL.');
    }
    marketplace_attach_checkout($pdo, $orderId, $sessionId);
    header('Location: ' . $checkoutUrl, true, 303);
    exit;
} catch (Throwable $exception) {
    if ($orderId > 0) {
        try { marketplace_fail_order($pdo, $orderId); } catch (Throwable $ignored) {}
    }
    error_log('Beyond Market checkout creation failed: ' . $exception->getMessage());
    $_SESSION['market_checkout_error'] = $exception->getMessage();
    header('Location: ' . beyond_url('beyond-sell/checkout.php?id=' . $listingId . '&method=stripe'), true, 303);
    exit;
}
