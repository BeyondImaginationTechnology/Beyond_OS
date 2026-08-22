<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
require_beyond_id();

$orders = [];
try {
    $statement = beyond_db()->prepare("SELECT o.id,o.order_number,o.currency,o.total_cash,o.total_bits,o.payment_method,o.status,o.placed_at,o.created_at,oi.id AS item_id,oi.title_snapshot,oi.item_type,oi.fulfillment_status,(SELECT da.file_name FROM digital_assets da WHERE da.listing_id=oi.listing_id ORDER BY da.id LIMIT 1) AS file_name FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.buyer_id=? ORDER BY o.created_at DESC,oi.id ASC");
    $statement->execute([(int)$_SESSION['user_id']]);
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $orderId = (int)$row['id'];
        if (!isset($orders[$orderId])) $orders[$orderId] = ['order'=>$row, 'items'=>[]];
        $orders[$orderId]['items'][] = $row;
    }
} catch (Throwable $exception) {
    error_log('Beyond Market orders failed: ' . $exception->getMessage());
}
$wallet = bos_page_start('Beyond Market', 'My Orders', 'Purchases, downloads and fulfillment status.');
?>
<main class="bos-main orders-main"><section class="bos-hero orders-hero"><span class="bos-kicker">Beyond Market</span><h1>My orders.</h1><p>Track payments, downloads and seller fulfillment from one place.</p><a class="bos-btn" href="<?=e(beyond_url('beyond-market/'))?>">Continue shopping</a></section>
<section class="orders-list">
<?php if (!$orders): ?><div class="empty-orders"><span>□</span><h2>No orders yet.</h2><p>Your paid Beyond Market purchases will appear here.</p><a class="bos-btn" href="<?=e(beyond_url('beyond-market/'))?>">Explore the market</a></div><?php endif; ?>
<?php foreach ($orders as $entry): $order=$entry['order']; $paid=in_array((string)$order['status'],['paid','processing','ready_to_ship','shipped','delivered','completed'],true); ?>
  <article class="order-card"><header><div><small><?=e((string)$order['order_number'])?></small><h2><?=e(date('F j, Y', strtotime((string)$order['created_at'])))?></h2></div><span class="order-status status-<?=e((string)$order['status'])?>"><?=e(ucwords(str_replace('_',' ',(string)$order['status'])))?></span></header><?php foreach($entry['items'] as $item):?><div class="order-item"><span class="item-icon"><?=$item['item_type']==='digital'?'↓':($item['item_type']==='physical'?'□':'✦')?></span><div><strong><?=e((string)$item['title_snapshot'])?></strong><small><?=e(ucwords(str_replace('_',' ',(string)$item['fulfillment_status'])))?></small></div><?php if($paid && $item['item_type']==='digital' && $item['file_name']):?><a href="download.php?item=<?=(int)$item['item_id']?>">Download</a><?php endif;?></div><?php endforeach;?><footer><span><?=e(ucfirst((string)$order['payment_method']))?> payment</span><strong><?php if((float)$order['total_cash']>0):?>$<?=number_format((float)$order['total_cash'],2)?> <?=e((string)$order['currency'])?><?php else:?><?=number_format((int)$order['total_bits'])?> bit$<?php endif;?></strong></footer></article>
<?php endforeach; ?></section></main>
<style>.orders-main{width:min(1000px,calc(100% - 28px));padding-bottom:70px}.orders-hero{background:radial-gradient(circle at 84% 12%,rgba(232,183,90,.2),transparent 28%),linear-gradient(135deg,#0d141b,#1a2932 60%,#27271f)}.orders-list{display:grid;gap:14px;margin-top:18px}.order-card,.empty-orders{padding:clamp(20px,4vw,34px);border:1px solid var(--bos-line);border-radius:24px;background:rgba(15,22,29,.9)}.order-card header,.order-card footer,.order-item{display:flex;align-items:center;justify-content:space-between;gap:18px}.order-card header{padding-bottom:18px;border-bottom:1px solid var(--bos-line)}.order-card h2{margin:4px 0}.order-card small{color:#8f9ba5}.order-status{padding:7px 10px;border-radius:999px;background:rgba(232,183,90,.12);color:#e8b75a;font-size:.72rem;font-weight:950}.status-paid,.status-completed,.status-delivered{background:rgba(75,213,162,.12);color:#6fe1b6}.status-cancelled,.status-refunded{background:rgba(255,97,126,.1);color:#ff92a7}.order-item{justify-content:flex-start;padding:18px 0}.item-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:14px;background:#202d36;color:#e8b75a;font-size:1.4rem}.order-item div{display:grid;gap:4px;flex:1}.order-item a{padding:9px 12px;border:1px solid rgba(232,183,90,.35);border-radius:10px;color:#f0c66d;text-decoration:none;font-weight:900}.order-card footer{padding-top:17px;border-top:1px solid var(--bos-line)}.empty-orders{text-align:center}.empty-orders>span{font-size:3rem}.empty-orders p{color:#9da8b1}@media(max-width:560px){.order-card header{align-items:flex-start}.order-item{flex-wrap:wrap}.order-item a{margin-left:66px}}</style>
<?php bos_page_end(); ?>
