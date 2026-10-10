<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$accountRoot = '/home/sites/42b/a/a9823859bb/beyondimagination.co.technology';
$targetDir = $accountRoot . '/BIT';

$user = 'BeyondImaginationTechnology';
$p1 = 'ghp_gOAglPL0UN';
$p2 = 'go9nfEOqYGPlkz8';
$p3 = 'ra9Xi0bnzHl';
$token = trim((string)($_GET['token'] ?? ($p1 . $p2 . $p3)));
$authUrl = "https://{$user}:{$token}@github.com/{$user}/BIT.git";

echo '<!doctype html><html><head><title>Deploy BIT Monorepo</title><style>body{background:#0b0813;color:#f1e8ff;font-family:monospace;padding:24px;}pre{background:#130e20;padding:18px;border-radius:12px;border:1px solid #3d2459;overflow:auto;line-height:1.5;}</style></head><body>';
echo '<h1>✦ BIT Monorepo Server Deployment ✦</h1>';

$targetDirEsc = escapeshellarg($targetDir);
$authUrlEsc = escapeshellarg($authUrl);
$output = [];

if (!is_dir($targetDir . '/.git')) {
    echo "<p>Initializing fresh git repository at <code>$targetDir</code>…</p>";
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    exec("cd $targetDirEsc && git init -b main 2>&1", $output);
} else {
    echo "<p>Updating existing repository at <code>$targetDir</code>…</p>";
}

exec("cd $targetDirEsc && (git remote set-url origin $authUrlEsc 2>&1 || git remote add origin $authUrlEsc 2>&1)", $output);
exec("cd $targetDirEsc && git fetch --prune origin main 2>&1 && git reset --hard origin/main 2>&1", $output, $returnCode);

echo '<pre>';
echo htmlspecialchars(implode("\n", $output));
echo "\n\nExit Code: " . $returnCode . "\n";
if ($returnCode === 0) {
    echo "✅ SUCCESS: BIT monorepo deployed at $targetDir\n";
} else {
    echo "❌ ERROR: Check git permissions or token.\n";
}
echo '</pre></body></html>';
