<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/marketplace-stripe.php';
require_beyond_id();

$sessionId = trim((string)($_GET['session_id'] ?? ''));
$order = null;
$error = '';
if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
    $error = 'The checkout confirmation link is invalid.';
} else {
    try {
        $stripeSession = marketplace_stripe_request('GET', 'checkout/sessions/' . rawurlencode($sessionId));
        if ((int)($stripeSession['metadata']['buyer_id'] ?? 0) !== (int)$_SESSION['user_id']) {
            throw new RuntimeException('This checkout belongs to a different Beyond ID.');
        }
        marketplace_fulfill_checkout(beyond_db(), $stripeSession);
        $statement = beyond_db()->prepare('SELECT id,order_number,total_cash,currency,status,placed_at FROM orders WHERE stripe_checkout_session_id=? AND buyer_id=? LIMIT 1');
        $statement->execute([$sessionId, (int)$_SESSION['user_id']]);
        $order = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$order) throw new RuntimeException('The local order could not be found.');
    } catch (Throwable $exception) {
        error_log('Beyond Market checkout confirmation failed: ' . $exception->getMessage());
        $error = 'Your payment is being confirmed. Check My Orders in a moment for the final status.';
    }
}
$wallet = bos_page_start('Beyond Market', 'Order confirmed', 'Your Beyond Market purchase status.');
?>
<main class="bos-main checkout-main"><section class="bos-hero checkout-success">
<?php if ($order && in_array((string)$order['status'], ['paid','processing','ready_to_ship','shipped','delivered','completed'], true)): ?>
  <span class="success-mark">✓</span><span class="bos-kicker">Payment received</span><h1>Thank you for your order.</h1><p>Order <b><?=e((string)$order['order_number'])?></b> is confirmed for $<?=number_format((float)$order['total_cash'], 2)?> <?=e((string)$order['currency'])?>.</p>
<?php else: ?>
  <span class="success-mark pending">…</span><span class="bos-kicker">Confirmation pending</span><h1>We’re checking your payment.</h1><p><?=e($error ?: 'Stripe is still reporting this payment as pending. No action is needed.')?></p>
<?php endif; ?>
<div class="bos-actions"><a class="bos-btn" href="orders.php">View my orders</a><a class="bos-btn secondary" href="<?=e(beyond_url('beyond-market/'))?>">Keep shopping</a></div></section></main>
<style>.checkout-main{width:min(900px,calc(100% - 28px));padding-bottom:70px}.checkout-success{text-align:center;background:radial-gradient(circle at 50% 15%,rgba(76,214,166,.24),transparent 30%),linear-gradient(145deg,#0e1820,#173328)}.checkout-success .bos-actions{justify-content:center}.success-mark{display:grid;place-items:center;width:72px;height:72px;margin:0 auto 20px;border-radius:50%;background:#55d9a5;color:#07150f;font-size:2.2rem;font-weight:1000}.success-mark.pending{background:#e8b75a;color:#211706}</style>
<?php bos_page_end(); ?>
