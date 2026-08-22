<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = bos_page_start('Beyond Sell', 'Beyond Sell', 'Publish creator listings and manage Beyond Market orders.');
$listings = [];
try {
    $listings = beyond_db()->query("SELECT id,title,item_type,listing_type,price_cash,price_bits,currency,status FROM listings WHERE status='active' ORDER BY created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) {
    error_log('Beyond Sell listings unavailable: ' . $exception->getMessage());
}
?>
<main class="bos-main"><section class="bos-hero"><span class="bos-kicker">Beyond Sell · Stripe ready</span><h1>Create. Sell. Deliver.</h1><p>Publish digital creator work and accept secure card payments through Beyond Market and Stripe Checkout.</p><div class="bos-actions"><a class="bos-btn" href="create.php">List an item</a><a class="bos-btn secondary" href="product-pricing.php">Set product pricing</a><a class="bos-btn secondary" href="orders.php">My orders</a></div></section><section class="bos-section"><h2>Live listings</h2><?php if($listings):?><div class="bos-grid"><?php foreach($listings as $listing): ?><a class="bos-card" href="listing.php?id=<?=(int)$listing['id']?>"><span class="bos-card-icon"><?=e($listing['item_type']==='digital'?'↓':($listing['item_type']==='physical'?'□':'✦'))?></span><div><strong><?=e((string)$listing['title'])?></strong><p><?=e(ucfirst((string)$listing['listing_type']))?> · <?=e(ucfirst((string)$listing['item_type']))?></p><p><?php if((int)$listing['price_bits']>0):?><?=number_format((int)$listing['price_bits'])?> bit$<?php endif;?><?php if((float)$listing['price_cash']>0):?><?=((int)$listing['price_bits']>0?' or ':'')?>$<?=number_format((float)$listing['price_cash'],2)?> <?=e((string)$listing['currency'])?><?php elseif((int)$listing['price_bits']<=0):?>Free<?php endif;?></p></div><span class="bos-card-status">View</span></a><?php endforeach;?></div><?php else:?><div class="sell-empty"><h3>No live listings yet.</h3><p>Publish the first real creator listing—demo products are never shown as purchasable inventory.</p><a class="bos-btn" href="create.php">Create a listing</a></div><?php endif;?></section></main>
<style>.sell-empty{padding:28px;border:1px dashed var(--bos-line);border-radius:20px;background:rgba(255,255,255,.025)}.sell-empty p{color:var(--bos-muted)}</style>
<?php bos_page_end(); ?>
