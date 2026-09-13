<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$checks = [];
try { $pdo->query('SELECT 1')->fetchColumn(); $checks['database']=['ok','Connected through '.strtoupper((string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME))]; }
catch(Throwable $exception) { $checks['database']=['danger','Connection failed']; }
$checks['authentication'] = [!empty($_SESSION['user_id'])?'ok':'danger', !empty($_SESSION['user_id'])?'Admin session active':'Session unavailable'];
$varPath = (string)(getenv('BEYOND_VAR_PATH') ?: dirname(__DIR__, 3).'/var');
$checks['storage'] = [is_dir($varPath)&&is_writable($varPath)?'ok':'warn', is_dir($varPath)&&is_writable($varPath)?'Private storage writable':'Check BEYOND_VAR_PATH permissions'];
$smtpConfigured = false;
try {
    $configFile = rtrim($varPath, '/\\').'/config/live.php';
    if (is_file($configFile)) {
        $cfg = require $configFile;
        $smtp = is_array($cfg['smtp'] ?? null) ? $cfg['smtp'] : [];
        $smtpConfigured = !empty($smtp['host'])
            && !empty($smtp['user'])
            && !empty($smtp['pass'])
            && filter_var($smtp['from'] ?? '', FILTER_VALIDATE_EMAIL);
    }
} catch(Throwable $exception) {}
$checks['email'] = [$smtpConfigured?'ok':'warn', $smtpConfigured?'SMTP configuration detected':'Configure SMTP'];
$extensions = ['pdo'=>extension_loaded('pdo'),'pdo_sqlite'=>extension_loaded('pdo_sqlite'),'pdo_mysql'=>extension_loaded('pdo_mysql'),'openssl'=>extension_loaded('openssl'),'sodium'=>extension_loaded('sodium'),'mbstring'=>extension_loaded('mbstring'),'fileinfo'=>extension_loaded('fileinfo')];

$title = 'System';
require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content"><h1>System Health</h1><p class="muted">Select a component for checks and troubleshooting steps. Secrets and filesystem paths are intentionally hidden.</p>
<div class="grid">
  <button class="tile system-tile" type="button" data-guide="database"><h3>Database</h3><span class="badge <?= e($checks['database'][0]) ?>"><?= e($checks['database'][1]) ?></span><p>View troubleshooting</p></button>
  <button class="tile system-tile" type="button" data-guide="authentication"><h3>Authentication</h3><span class="badge <?= e($checks['authentication'][0]) ?>"><?= e($checks['authentication'][1]) ?></span><p>View troubleshooting</p></button>
  <button class="tile system-tile" type="button" data-guide="smtp"><h3>Email</h3><span class="badge <?= e($checks['email'][0]) ?>"><?= e($checks['email'][1]) ?></span><p>Configure and test SMTP</p></button>
  <button class="tile system-tile" type="button" data-guide="storage"><h3>Storage</h3><span class="badge <?= e($checks['storage'][0]) ?>"><?= e($checks['storage'][1]) ?></span><p>View troubleshooting</p></button>
</div>
<div class="card"><h2>Required PHP extensions</h2><div style="display:flex;gap:8px;flex-wrap:wrap"><?php foreach($extensions as $extension=>$loaded): ?><span class="badge <?= $loaded?'ok':'danger' ?>"><?= e($extension) ?>: <?= $loaded?'loaded':'missing' ?></span><?php endforeach; ?></div></div>
</section>
<dialog id="system-guide" style="width:min(720px,calc(100% - 32px));border:0;border-radius:18px;padding:0"><div class="card" style="margin:0"><button type="button" id="close-guide" style="float:right" aria-label="Close">×</button><div id="guide-content"></div></div></dialog>
<template id="guide-smtp"><h2>Configure and troubleshoot SMTP</h2><ol><li>Edit the protected <code>var/config/live.php</code> configuration, never a public PHP file.</li><li>Set SMTP host, port, encryption mode, username, password, and verified From address.</li><li>Use port 465 with implicit TLS or 587 with STARTTLS as required by the provider.</li><li>Confirm OpenSSL is loaded and the server clock and CA certificates are current.</li><li>Verify the From domain has SPF and DKIM records; add DMARC after delivery works.</li><li>Run the existing mail test page and inspect the private PHP error log. Never display credentials or full transport exceptions publicly.</li></ol><h3>Common symptoms</h3><ul><li><strong>Authentication failed:</strong> verify the username, app password, and provider SMTP access.</li><li><strong>Connection timed out:</strong> ask the host whether outbound SMTP ports are blocked.</li><li><strong>Certificate error:</strong> correct the hostname and server CA bundle; do not disable TLS verification.</li><li><strong>Sent but not received:</strong> inspect spam placement, SPF, DKIM, DMARC, and provider suppression lists.</li></ul></template>
<template id="guide-database"><h2>Database troubleshooting</h2><ol><li>Open Database Explorer and confirm the PDO driver and tables.</li><li>For SQLite, confirm private storage is writable and run a passive WAL checkpoint.</li><li>For MySQL, confirm host, database, username, and privileges in protected configuration.</li><li>Review the private PHP log for <code>DB-CONNECT</code> or migration errors.</li><li>Back up before migrations or repairs.</li></ol></template>
<template id="guide-authentication"><h2>Authentication troubleshooting</h2><ol><li>Confirm HTTPS and a valid admin or super-admin role.</li><li>Clear only the site session cookie, then sign in again.</li><li>Verify session storage permissions and cookie domain/path configuration.</li><li>Check the activity log for failed login or inactive-account entries.</li></ol></template>
<template id="guide-storage"><h2>Private storage troubleshooting</h2><ol><li>Set <code>BEYOND_VAR_PATH</code> to a directory outside the public web root.</li><li>Grant the PHP process read/write access without world-writable permissions.</li><li>Confirm disk quota and free space.</li><li>Keep uploads, SQLite databases, logs, and live configuration in private storage.</li></ol></template>
<script>(function(){const dialog=document.getElementById('system-guide'),content=document.getElementById('guide-content');document.querySelectorAll('[data-guide]').forEach(function(button){button.addEventListener('click',function(){const template=document.getElementById('guide-'+button.dataset.guide);content.replaceChildren(template.content.cloneNode(true));dialog.showModal();});});document.getElementById('close-guide').addEventListener('click',function(){dialog.close();});dialog.addEventListener('click',function(event){if(event.target===dialog)dialog.close();});})();</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
