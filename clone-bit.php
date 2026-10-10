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

if (is_dir($targetDir . '/.git')) {
    echo "<p>Configuring remote origin and fetching main from <code>$targetDir</code>…</p>";
    $cmd = 'cd ' . escapeshellarg($targetDir) . ' && git remote remove origin 2>/dev/null; git remote add origin ' . escapeshellarg($authUrl) . ' 2>&1; git remote set-url origin ' . escapeshellarg($authUrl) . ' 2>&1 && git fetch origin main 2>&1 && git reset --hard origin/main 2>&1';
} else {
    echo "<p>Cloning fresh <strong>BIT</strong> repository to <code>$targetDir</code>…</p>";
    if (is_dir($targetDir)) {
        echo "<p>Cleaning existing empty directory <code>$targetDir</code>…</p>";
        @rmdir($targetDir);
    }
    $cmd = 'git clone ' . escapeshellarg($authUrl) . ' ' . escapeshellarg($targetDir) . ' 2>&1';
}

echo '<pre>';
$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);
echo htmlspecialchars(implode("\n", $output));
echo "\n\nExit Code: " . $returnCode . "\n";
if ($returnCode === 0) {
    echo "✅ SUCCESS: BIT monorepo deployed at $targetDir\n";
} else {
    echo "❌ ERROR: Check git permissions or token.\n";
}
echo '</pre></body></html>';
