<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = bos_page_start('Beyond Sell', 'Product Pricing', 'Set seller profit across the Beyond Market physical product catalog.');
$catalogFile = __DIR__ . '/../beyond-market/data/product-catalog.json';
$catalog = json_decode((string)file_get_contents($catalogFile), true);
if (!is_array($catalog)) $catalog = [];
$groups = [];
foreach ($catalog as $product) $groups[(string)$product['category']][] = $product;
$platformMarkup = 5.00;
?>
<main class="bos-main pricing-main">
  <section class="bos-hero pricing-hero">
    <span class="bos-kicker">Beyond Sell · Product pricing</span>
    <h1>Your profit.<br>Your call.</h1>
    <p>Every physical product includes a fixed $5.00 Beyond platform markup. Adjust your seller-profit percentage independently for each product; the fixed Beyond amount never changes.</p>
    <div class="pricing-summary">
      <span><b><?=count($catalog)?></b> product types</span>
      <span><b><?=count($groups)?></b> categories</span>
      <span><b>$<?=number_format($platformMarkup,2)?></b> fixed Beyond markup</span>
    </div>
    <div class="bos-actions"><button class="bos-btn" type="button" id="savePricing">Save profit settings</button><button class="bos-btn secondary" type="button" id="resetPricing">Reset defaults</button><a class="bos-btn secondary" href="<?=e(beyond_url('beyond-market/#shop'))?>">View Marketplace</a></div>
    <p class="save-state" id="pricingState" role="status">Changes are stored on this device until seller-account pricing sync is connected.</p>
  </section>

  <section class="pricing-note"><strong>Price formula</strong><span>Production reference + $5.00 Beyond markup + your seller profit = customer price.</span><small>Production and availability references are manually maintained. Confirm current fulfillment costs before publishing.</small></section>

  <?php foreach ($groups as $category => $products): ?>
  <section class="pricing-group" id="<?=e(strtolower((string)preg_replace('/[^a-z0-9]+/i','-',$category)))?>">
    <header><div><span class="bos-kicker"><?=e($category)?></span><h2><?=count($products)?> products</h2></div><button type="button" class="apply-category" data-category="<?=e($category)?>">Apply first % to category</button></header>
    <div class="pricing-table-wrap">
      <table>
        <thead><tr><th>Product</th><th>Production reference</th><th>Beyond</th><th>Your profit %</th><th>Your profit</th><th>Customer price</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product):
          $baseMin = max(0, (float)$product['retail_min'] - (float)$product['margin_min']);
          $baseMax = max(0, (float)$product['retail_max'] - (float)$product['margin_max']);
          $key = strtolower((string)preg_replace('/[^a-z0-9]+/i','-', $category . '-' . (string)$product['name']));
        ?>
          <tr data-pricing-row data-key="<?=e($key)?>" data-category="<?=e($category)?>" data-base-min="<?=number_format($baseMin,2,'.','')?>" data-base-max="<?=number_format($baseMax,2,'.','')?>" data-margin-min="<?=number_format((float)$product['margin_min'],2,'.','')?>" data-margin-max="<?=number_format((float)$product['margin_max'],2,'.','')?>" data-default-percent="<?=e((string)$product['default_percent'])?>">
            <th scope="row"><?=e((string)$product['name'])?></th>
            <td data-base></td>
            <td><span class="fixed-markup">+$<?=number_format($platformMarkup,2)?></span></td>
            <td><label><span class="sr-only">Seller profit percent for <?=e((string)$product['name'])?></span><input class="profit-input" type="number" min="0" max="200" step="1" value="<?=e((string)$product['default_percent'])?>" inputmode="decimal"><b>%</b></label></td>
            <td data-profit></td>
            <td><strong data-final></strong></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endforeach; ?>
</main>
<style>
.pricing-main{width:min(1380px,calc(100% - 24px))}.pricing-hero{background:radial-gradient(circle at 84% 14%,color-mix(in srgb,var(--accent) 24%,transparent),transparent 28%),linear-gradient(135deg,var(--surface-deep),var(--surface-strong))}.pricing-summary{display:flex;gap:9px;flex-wrap:wrap;margin-top:24px}.pricing-summary span{padding:9px 12px;border:1px solid var(--line);border-radius:999px;background:color-mix(in srgb,var(--panel) 78%,transparent);color:var(--muted);font-size:.78rem}.pricing-summary b{color:var(--text)}.pricing-hero button{font:inherit;cursor:pointer}.save-state{margin:14px 0 0!important;font-size:.78rem}.pricing-note{display:grid;grid-template-columns:auto 1fr;gap:4px 18px;margin:18px 0;padding:18px 20px;border:1px solid var(--line);border-radius:18px;background:var(--panel)}.pricing-note strong{color:var(--accent-soft)}.pricing-note span{color:var(--text)}.pricing-note small{grid-column:2;color:var(--muted)}.pricing-group{margin-top:20px;padding:24px;border:1px solid var(--line);border-radius:24px;background:var(--panel)}.pricing-group>header{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:16px}.pricing-group h2{margin:4px 0 0;font-size:2rem}.apply-category{padding:9px 12px;border:1px solid var(--line);border-radius:11px;background:var(--surface-strong);color:var(--text);font:inherit;font-weight:800;cursor:pointer}.pricing-table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:16px}table{width:100%;border-collapse:collapse;min-width:920px}th,td{padding:14px 13px;border-bottom:1px solid var(--line);text-align:left}thead th{background:var(--surface-deep);color:var(--muted);font-size:.7rem;letter-spacing:.08em;text-transform:uppercase}tbody th{font-size:.84rem}tbody td{color:var(--muted);font-size:.8rem}tbody tr:last-child th,tbody tr:last-child td{border-bottom:0}.fixed-markup{display:inline-flex;padding:5px 8px;border-radius:999px;background:color-mix(in srgb,var(--primary) 15%,transparent);color:var(--accent-soft);font-weight:900}.profit-input{width:76px;padding:9px;border:1px solid var(--line);border-radius:9px;background:var(--surface-deep);color:var(--text);font:inherit;font-weight:900}.profit-input+b{margin-left:5px;color:var(--muted)}[data-final]{color:var(--accent-soft);font-size:.9rem}.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
@media(max-width:650px){.pricing-group{padding:15px}.pricing-group>header{align-items:start;flex-direction:column}.pricing-note{grid-template-columns:1fr}.pricing-note small{grid-column:1}}
</style>
<script>
(()=>{const PLATFORM=5,STORAGE='beyond-seller-product-pricing-v1',rows=[...document.querySelectorAll('[data-pricing-row]')],state=document.getElementById('pricingState');let saved={};try{saved=JSON.parse(localStorage.getItem(STORAGE)||'{}')||{}}catch(error){saved={}}
const money=value=>'$'+Number(value).toFixed(2),range=(min,max)=>Math.abs(min-max)<.005?money(min):money(min)+' – '+money(max);
function render(row){const input=row.querySelector('.profit-input'),percent=Math.max(0,Math.min(200,Number(input.value)||0)),baseMin=Number(row.dataset.baseMin),baseMax=Number(row.dataset.baseMax),defaultPercent=Number(row.dataset.defaultPercent),defaultMarginMin=Number(row.dataset.marginMin),defaultMarginMax=Number(row.dataset.marginMax),useReference=Math.abs(percent-defaultPercent)<.001,profitMin=useReference?defaultMarginMin:baseMin*percent/100,profitMax=useReference?defaultMarginMax:baseMax*percent/100;row.querySelector('[data-base]').textContent=range(baseMin,baseMax);row.querySelector('[data-profit]').textContent=range(profitMin,profitMax);row.querySelector('[data-final]').textContent=range(baseMin+PLATFORM+profitMin,baseMax+PLATFORM+profitMax)}
rows.forEach(row=>{const input=row.querySelector('.profit-input');if(Object.prototype.hasOwnProperty.call(saved,row.dataset.key))input.value=String(saved[row.dataset.key]);input.addEventListener('input',()=>{render(row);state.textContent='Unsaved pricing changes.'});render(row)});
document.querySelectorAll('.apply-category').forEach(button=>button.addEventListener('click',()=>{const category=button.dataset.category,categoryRows=rows.filter(row=>row.dataset.category===category);if(!categoryRows.length)return;const percent=categoryRows[0].querySelector('.profit-input').value;categoryRows.forEach(row=>{row.querySelector('.profit-input').value=percent;render(row)});state.textContent='Applied '+percent+'% across '+category+'. Save to keep it.'}));
document.getElementById('savePricing').addEventListener('click',()=>{const payload={};rows.forEach(row=>payload[row.dataset.key]=Number(row.querySelector('.profit-input').value)||0);try{localStorage.setItem(STORAGE,JSON.stringify(payload));state.textContent='Seller profit settings saved on this device.'}catch(error){state.textContent='Your browser blocked local saving.'}});
document.getElementById('resetPricing').addEventListener('click',()=>{rows.forEach(row=>{row.querySelector('.profit-input').value=row.dataset.defaultPercent;render(row)});try{localStorage.removeItem(STORAGE)}catch(error){}state.textContent='Default profit settings restored.'});
})();
</script>
<?php bos_page_end(); ?>
