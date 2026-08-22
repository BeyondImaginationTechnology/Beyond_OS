<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
require_beyond_id();

$isWallpaper = ($_GET['type'] ?? $_POST['product_type'] ?? '') === 'wallpaper';
$error = '';
if (empty($_SESSION['beyond_sell_csrf'])) {
    $_SESSION['beyond_sell_csrf'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $title = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 180);
    $description = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 4000);
    $access = (string) ($_POST['access'] ?? 'free');
    $priceBits = $access === 'premium' ? max(1, min(100000, (int) ($_POST['price_bits'] ?? 0))) : 0;
    $priceCash = $access === 'stripe' ? round((float) ($_POST['price_cash'] ?? 0), 2) : 0.0;
    $deliveryUrl = trim((string) ($_POST['delivery_url'] ?? ''));

    if (!hash_equals((string) $_SESSION['beyond_sell_csrf'], $token)) {
        $error = 'Your session expired. Reload the page and try again.';
    } elseif ($title === '' || mb_strlen($title) < 3) {
        $error = 'Add a descriptive title with at least three characters.';
    } elseif ($description === '') {
        $error = 'Tell buyers what is included in the wallpaper download.';
    } elseif (!in_array($access, ['free', 'premium', 'stripe'], true)) {
        $error = 'Choose free, Stripe, or bit$ access.';
    } elseif ($access === 'stripe' && ($priceCash < .50 || $priceCash > 999999.99)) {
        $error = 'Set a Stripe price between $0.50 and $999,999.99.';
    } elseif ($deliveryUrl !== '' && (!filter_var($deliveryUrl, FILTER_VALIDATE_URL) || strtolower((string) parse_url($deliveryUrl, PHP_URL_SCHEME)) !== 'https')) {
        $error = 'Enter a valid HTTPS delivery link or leave it blank for now.';
    } elseif (empty($_POST['rights'])) {
        $error = 'Confirm that you own the work or have permission to distribute it.';
    } else {
        try {
            $pdo = beyond_db();
            $slugBase = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'wallpaper';
            $slug = substr($slugBase, 0, 175) . '-' . bin2hex(random_bytes(3));
            $short = mb_substr($description, 0, 300);
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO listings (seller_id,title,slug,short_description,description,listing_type,item_type,condition_type,price_cash,price_bits,currency,quantity,status,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $statement->execute([(int) $_SESSION['user_id'], $title, $slug, $short, $description, 'buy_now', 'digital', 'not_applicable', $priceCash, $priceBits, 'USD', 999, 'active', date('Y-m-d H:i:s')]);
            $listingId = (int) $pdo->lastInsertId();
            if ($deliveryUrl !== '') {
                $fileName = basename((string) (parse_url($deliveryUrl, PHP_URL_PATH) ?: 'wallpaper-download')) ?: 'wallpaper-download';
                $asset = $pdo->prepare('INSERT INTO digital_assets (listing_id,file_name,file_path,mime_type,download_limit,download_expiry_days) VALUES (?,?,?,?,?,?)');
                $asset->execute([$listingId, mb_substr($fileName, 0, 255), $deliveryUrl, 'application/octet-stream', 5, 30]);
            }
            $pdo->commit();
            $_SESSION['beyond_sell_csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . beyond_url('beyond-sell/listing.php?id=' . $listingId . '&created=1'));
            exit;
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            error_log('Beyond Sell listing creation failed: ' . $exception->getMessage());
            $error = 'The listing could not be published right now. Please try again.';
        }
    }
}
$wallet = bos_page_start('Beyond Sell', 'Create a Listing', 'Publish a free or premium digital product.');
?>
<main class="bos-main create-main">
  <section class="bos-hero create-hero"><span class="bos-kicker">Beyond Market · Quick listing</span><h1><?= $isWallpaper ? 'Sell a wallpaper.' : 'Create a listing.' ?></h1><p>Publish a free download, charge securely with Stripe, or set a premium bit$ price.</p></section>
  <section class="create-layout">
    <form class="listing-form" method="post" novalidate>
      <input type="hidden" name="csrf" value="<?=e((string) $_SESSION['beyond_sell_csrf'])?>">
      <input type="hidden" name="product_type" value="<?=$isWallpaper?'wallpaper':'digital'?>">
      <?php if ($error !== ''): ?><div class="form-error" role="alert"><?=e($error)?></div><?php endif; ?>
      <div class="form-heading"><span>1</span><div><h2>Describe your <?= $isWallpaper ? 'wallpaper' : 'product' ?></h2><p>Clear names and useful details help buyers discover your work.</p></div></div>
      <label>Listing title<input name="title" required maxlength="180" value="<?=e((string)($_POST['title']??''))?>" placeholder="Midnight Montréal wallpaper pack"></label>
      <label>Description<textarea name="description" required maxlength="4000" placeholder="Describe the included sizes, orientations, file types and permitted use."><?=e((string)($_POST['description']??''))?></textarea></label>
      <div class="form-heading"><span>2</span><div><h2>Choose access</h2><p>Offer a free release or choose how buyers pay.</p></div></div>
      <div class="access-grid">
        <label class="access-card"><input type="radio" name="access" value="free" <?=($_POST['access']??'free')==='free'?'checked':''?>><span>FREE</span><strong>Free download</strong><small>Great for samples, community drops and audience growth.</small></label>
        <label class="access-card"><input type="radio" name="access" value="stripe" <?=($_POST['access']??'')==='stripe'?'checked':''?>><span>STRIPE</span><strong>Card payment</strong><small>Sell in USD through secure hosted Stripe Checkout.</small></label>
        <label class="access-card"><input type="radio" name="access" value="premium" <?=($_POST['access']??'')==='premium'?'checked':''?>><span>bit$</span><strong>Premium download</strong><small>Set a clear bit$ price for original packs and collections.</small></label>
      </div>
      <label id="cashField">Price in USD<input type="number" name="price_cash" min="0.50" max="999999.99" step="0.01" value="<?=e((string)($_POST['price_cash']??'9.99'))?>" inputmode="decimal"></label>
      <label id="bitsField">Premium price in bit$<input type="number" name="price_bits" min="1" max="100000" step="1" value="<?=e((string)($_POST['price_bits']??'50'))?>"></label>
      <div class="form-heading"><span>3</span><div><h2>Add delivery</h2><p>You can add a secure HTTPS link now or attach delivery later.</p></div></div>
      <label>Download URL <small>Optional</small><input type="url" name="delivery_url" value="<?=e((string)($_POST['delivery_url']??''))?>" placeholder="https://your-storage.example/wallpaper-pack.zip"></label>
      <label class="rights-check"><input type="checkbox" name="rights" value="1" required <?=!empty($_POST['rights'])?'checked':''?>> I own this work or have permission to sell and distribute it.</label>
      <button class="bos-btn publish-button" type="submit">Publish to Beyond Market</button>
    </form>
    <aside class="listing-guide"><span class="guide-icon">▥</span><span class="bos-kicker">Wallpaper checklist</span><h2>Make it easy to buy.</h2><ul><li>Include phone and desktop sizes.</li><li>Use JPG, PNG or a clearly labelled ZIP.</li><li>Show whether personal or commercial use is allowed.</li><li>Price a small pack around 25–100 bit$.</li><li>Never upload artwork you do not own.</li></ul><a href="<?=e(beyond_url('beyond-market/#shop'))?>">See the marketplace →</a></aside>
  </section>
</main>
<style>
.create-main{width:min(1120px,calc(100% - 28px));padding-bottom:60px}.create-hero{background:radial-gradient(circle at 86% 14%,rgba(102,141,255,.3),transparent 28%),radial-gradient(circle at 12% 90%,rgba(238,78,156,.22),transparent 32%),linear-gradient(135deg,#11173c,#332057 55%,#481f46)}.create-layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:18px;margin-top:18px}.listing-form,.listing-guide{padding:clamp(22px,4vw,38px);border:1px solid var(--bos-line);border-radius:26px;background:rgba(16,17,43,.84)}.listing-form{display:grid;gap:17px}.listing-form label{display:grid;gap:7px;color:#c8cce0;font-weight:850}.listing-form label>small{color:#878faa;font-weight:600}.listing-form input,.listing-form textarea{width:100%;padding:13px 14px;border:1px solid var(--bos-line);border-radius:12px;background:#090c24;color:white;font:inherit}.listing-form textarea{min-height:130px;resize:vertical}.form-heading{display:flex;gap:13px;align-items:flex-start;margin-top:10px;padding-top:20px;border-top:1px solid var(--bos-line)}.form-heading:first-of-type{margin-top:0;padding-top:0;border-top:0}.form-heading>span{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#6751d9,#e6509c);font-weight:1000}.form-heading h2,.form-heading p{margin:0}.form-heading p{margin-top:3px;color:#9299b6;font-size:.82rem}.access-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.access-card{position:relative;padding:16px;border:1px solid var(--bos-line);border-radius:15px;background:#090c24;cursor:pointer}.access-card:has(input:checked){border-color:#ef69ad;background:rgba(216,73,152,.1)}.access-card input{position:absolute;right:12px;top:12px;width:auto}.access-card span,.access-card strong,.access-card small{display:block}.access-card span{color:#f17db5;font-size:.72rem;font-weight:1000}.access-card strong{margin:6px 0}.access-card small{color:#9299b5;line-height:1.4}.rights-check{grid-template-columns:auto 1fr!important;align-items:start}.rights-check input{width:auto;margin-top:3px}.publish-button{width:100%;border:0;cursor:pointer}.form-error{padding:13px;border:1px solid rgba(255,102,130,.4);border-radius:12px;background:rgba(255,64,106,.1);color:#ffabc0}.listing-guide{position:sticky;top:90px;align-self:start}.guide-icon{display:grid;place-items:center;width:70px;height:70px;margin-bottom:20px;border-radius:20px;background:linear-gradient(135deg,#5e64dd,#d84e9b);font-size:2.5rem}.listing-guide h2{font-size:2.2rem}.listing-guide li{margin:12px 0;color:#b0b6cf;line-height:1.45}.listing-guide a{color:#77e6da;text-decoration:none;font-weight:950}
.access-grid{grid-template-columns:repeat(3,1fr)}
@media(max-width:800px){.create-layout{grid-template-columns:1fr}.listing-guide{position:static}}@media(max-width:560px){.create-main{width:min(100% - 18px,1120px)}.create-hero{padding:28px 18px}.access-grid{grid-template-columns:1fr}.listing-form,.listing-guide{padding:20px 15px}}
</style>
<script>(()=>{const radios=[...document.querySelectorAll('input[name="access"]')],bits=document.getElementById('bitsField'),cash=document.getElementById('cashField'),bitsInput=bits.querySelector('input'),cashInput=cash.querySelector('input');function sync(){const selected=radios.find(r=>r.checked)?.value;bits.hidden=selected!=='premium';cash.hidden=selected!=='stripe';bitsInput.required=selected==='premium';cashInput.required=selected==='stripe'}radios.forEach(r=>r.addEventListener('change',sync));sync()})();</script>
<?php bos_page_end(); ?>
