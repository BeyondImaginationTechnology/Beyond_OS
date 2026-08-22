<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
require_once __DIR__ . '/../includes/marketplace-stripe.php';

$method = (string)($_GET['method'] ?? 'stripe');
if ($method !== 'free') require_beyond_id();
$id = max(0, (int)($_GET['id'] ?? 0));
$listing = null;
$asset = null;
try {
    $pdo = beyond_db();
    $statement = $pdo->prepare("SELECT id,seller_id,title,short_description,item_type,price_cash,price_bits,currency,quantity,status FROM listings WHERE id=? AND status='active' LIMIT 1");
    $statement->execute([$id]);
    $listing = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($listing) {
        $assetStatement = $pdo->prepare('SELECT file_name,file_path FROM digital_assets WHERE listing_id=? ORDER BY id LIMIT 1');
        $assetStatement->execute([$id]);
        $asset = $assetStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $exception) {
    $listing = null;
}
$isFree = $listing && (float)($listing['price_cash'] ?? 0) <= 0 && (int)($listing['price_bits'] ?? 0) <= 0;
$checkoutError = (string)($_SESSION['market_checkout_error'] ?? '');
unset($_SESSION['market_checkout_error']);
$stripeReady = marketplace_stripe_ready();
$wallet = bos_page_start('Beyond Market', 'Checkout', 'Secure marketplace checkout.');
?>
<main class="bos-main checkout-main">
<?php if (!$listing): ?>
  <section class="bos-hero checkout-hero"><span class="bos-kicker">Beyond Market</span><h1>Item unavailable.</h1><p>This listing cannot be checked out right now.</p><a class="bos-btn" href="<?=e(beyond_url('beyond-market/'))?>">Return to market</a></section>
<?php elseif ($method === 'free' && $isFree): ?>
  <section class="bos-hero checkout-hero free"><span class="bos-kicker">Free creator download</span><h1><?=e((string)$listing['title'])?></h1><p>No payment is required for this community listing.</p><?php if($asset):?><div class="bos-actions"><a class="bos-btn" href="<?=e((string)$asset['file_path'])?>" target="_blank" rel="noopener">Download <?=e((string)$asset['file_name'])?></a><a class="bos-btn secondary" href="<?=e(beyond_url('beyond-market/'))?>">Keep browsing</a></div><?php else:?><div class="bos-notice">The seller published the listing and is still attaching its download. Check back shortly.</div><?php endif;?></section>
<?php elseif ($method === 'stripe' && (float)$listing['price_cash'] > 0): ?>
  <section class="checkout-card"><div class="checkout-copy"><span class="bos-kicker">Secure Stripe Checkout</span><h1>Review your order.</h1><p>You’ll finish payment on Stripe’s secure checkout page.</p><?php if (!empty($_GET['cancelled'])):?><div class="checkout-message">Checkout was cancelled. You have not been charged.</div><?php endif;?><?php if ($checkoutError !== ''):?><div class="checkout-message error"><?=e($checkoutError)?></div><?php endif;?></div><div class="order-summary"><span><?=e(ucfirst((string)$listing['item_type']))?> listing</span><h2><?=e((string)$listing['title'])?></h2><p><?=e((string)($listing['short_description'] ?: 'Original Beyond Market creator listing.'))?></p><div class="order-total"><span>Total</span><strong>$<?=number_format((float)$listing['price_cash'], 2)?> <?=e((string)$listing['currency'])?></strong></div><?php if ((string)$listing['item_type']==='physical'):?><small>Shipping address collected securely by Stripe · Canada and United States</small><?php else:?><small>Digital access appears in My Orders after verified payment.</small><?php endif;?><?php if($stripeReady):?><form method="post" action="create-checkout.php"><input type="hidden" name="_csrf" value="<?=e(beyond_csrf_token())?>"><input type="hidden" name="listing_id" value="<?=$id?>"><button class="bos-btn checkout-button" type="submit">Continue to Stripe</button></form><?php else:?><div class="checkout-message error">Checkout setup is incomplete. Add the Stripe secret and webhook credentials to the protected live configuration.</div><?php endif;?><a class="back-link" href="listing.php?id=<?=$id?>">← Return to listing</a></div></section>
<?php else: ?>
  <section class="bos-hero checkout-hero"><span class="bos-kicker">Beyond Market</span><h1>Payment method unavailable.</h1><p>This listing cannot use the selected payment method yet.</p><a class="bos-btn secondary" href="listing.php?id=<?=$id?>">Return to listing</a></section>
<?php endif; ?>
</main>
<style>.checkout-main{width:min(1040px,calc(100% - 28px));padding-bottom:70px}.checkout-hero{background:radial-gradient(circle at 85% 15%,rgba(232,183,90,.24),transparent 28%),linear-gradient(135deg,#0e151c,#1b2932 58%,#25271f)}.checkout-hero.free{background:radial-gradient(circle at 85% 15%,rgba(65,224,169,.28),transparent 28%),linear-gradient(135deg,#101c34,#17374a 58%,#194436)}.checkout-card{display:grid;grid-template-columns:1fr minmax(340px,.8fr);gap:18px;margin-top:20px}.checkout-copy,.order-summary{padding:clamp(25px,5vw,48px);border:1px solid var(--bos-line);border-radius:26px;background:rgba(15,22,29,.92)}.checkout-copy h1{font-size:clamp(3rem,7vw,5.5rem);line-height:.95;letter-spacing:-.06em}.checkout-copy p,.order-summary p,.order-summary small{color:#aeb8c1;line-height:1.6}.order-summary>span{color:#e8b75a;font-size:.72rem;font-weight:950;text-transform:uppercase}.order-summary h2{font-size:2rem}.order-total{display:flex;justify-content:space-between;gap:20px;margin:25px 0;padding:20px 0;border-block:1px solid var(--bos-line)}.order-total strong{font-size:1.35rem}.order-summary small{display:block}.checkout-button{width:100%;justify-content:center;margin-top:24px;cursor:pointer}.back-link{display:block;margin-top:16px;color:#aeb8c1;text-align:center;text-decoration:none}.checkout-message{margin-top:20px;padding:13px;border:1px solid rgba(232,183,90,.35);border-radius:12px;background:rgba(232,183,90,.09)}.checkout-message.error{border-color:rgba(255,106,130,.35);background:rgba(255,70,100,.08);color:#ffc0cd}@media(max-width:760px){.checkout-card{grid-template-columns:1fr}.checkout-copy,.order-summary{padding:24px 18px}}</style>
<?php bos_page_end(); ?>
