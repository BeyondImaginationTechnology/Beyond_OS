<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$root = dirname(__DIR__, 2);
$apps = [
    ['Beyond OS','/','index.php','Platform home'],
    ['Beyond ID','/beyond-id/dashboard/','beyond-id/dashboard/index.php','Identity and account'],
    ['Beyond Tattoo','/beyond-tattoo/','beyond-tattoo/index.php','Tattoo marketplace'],
    ['Beyond TV','/beyond-tv/','beyond-tv/index.php','Live and on-demand TV'],
    ['DailyBreath','/dailybreath/','dailybreath/index.php','Faith and wellness'],
    ['Beyond Math','/beyond-math/','beyond-math/index.php','Math learning'],
    ['Beyond Space','/beyond-space/','beyond-space/index.php','Space learning'],
    ['Beyond Ancient Academy','/beyond-ancient/academy.php','beyond-ancient/academy.php','World history learning'],
    ['Beyond Skate','/beyond-skate/','beyond-skate/index.php','Skate platform'],
    ['Beyond Sell','/beyond-sell/','beyond-sell/index.php','Selling tools'],
    ['Beyond Radio','/beyond-radio/','beyond-radio/index.php','Audio and radio'],
    ['Daily Studio','/server/admin/daily-studio/','server/admin/daily-studio/index.php','Publishing tool — uses your current admin session'],
];
foreach ($apps as &$app) $app[] = is_file($root . '/' . $app[2]);
unset($app);

$title = 'Apps';
require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content"><div style="display:flex;align-items:end;justify-content:space-between;gap:16px"><div><h1>Beyond Apps</h1><p class="muted">Launch installed applications and administration surfaces.</p></div><input id="app-filter" type="search" placeholder="Find an app" aria-label="Find an app"></div>
<div class="grid" id="app-grid"><?php foreach($apps as [$name,$url,$file,$description,$installed]): ?><article class="tile app-launch-tile" data-app-name="<?= e(strtolower($name.' '.$description)) ?>"><h3><?= e($name) ?></h3><p><?= e($description) ?></p><span class="badge <?= $installed?'ok':'warn' ?>"><?= $installed?'Installed':'Unavailable in this build' ?></span><p><?php if($installed): ?><a class="btn" href="<?= e($url) ?>">Launch</a> <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">Open new tab ↗</a><?php endif; ?></p></article><?php endforeach; ?></div></section>
<script>document.getElementById('app-filter').addEventListener('input',function(){const q=this.value.trim().toLowerCase();document.querySelectorAll('.app-launch-tile').forEach(function(tile){tile.hidden=q!==''&&!tile.dataset.appName.includes(q);});});</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
