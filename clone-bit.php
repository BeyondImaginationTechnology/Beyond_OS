<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$accountRoot = '/home/sites/42b/a/a9823859bb/beyondimagination.co.technology';
$targetDir = $accountRoot . '/BIT';

$token = trim((string)($_GET['token'] ?? ''));

echo '<!doctype html><html><head><title>Deploy BIT Monorepo</title><style>body{background:#0b0813;color:#f1e8ff;font-family:monospace;padding:24px;}pre{background:#130e20;padding:18px;border-radius:12px;border:1px solid #3d2459;overflow:auto;line-height:1.5;}input,button{padding:10px 14px;border-radius:8px;border:1px solid #7d35d0;background:#1b122b;color:#fff;font-family:inherit;}</style></head><body>';
echo '<h1>✦ BIT Monorepo Server Deployment ✦</h1>';

if ($token === '' && !is_dir($targetDir . '/.git')) {
    echo '<form method="GET" style="margin:20px 0">';
    echo '<label style="display:block;margin-bottom:8px">Enter GitHub Personal Access Token (PAT):</label>';
    echo '<input type="password" name="token" placeholder="ghp_..." style="width:380px" required> ';
    echo '<button type="submit">Clone BIT Repository to /BIT</button>';
    echo '</form></body></html>';
    exit;
}

$user = 'BeyondImaginationTechnology';
$authUrl = $token !== '' ? "https://{$user}:{$token}@github.com/{$user}/BIT.git" : "https://github.com/{$user}/BIT.git";

if (is_dir($targetDir . '/.git')) {
    echo "<p>Repository already exists at <code>$targetDir</code>. Configuring origin and updating from main…</p>";
    if ($token !== '') {
        $cmd = 'cd ' . escapeshellarg($targetDir) . ' && git remote set-url origin ' . escapeshellarg($authUrl) . ' 2>&1 && git fetch origin main 2>&1 && git reset --hard origin/main 2>&1';
    } else {
        $cmd = 'cd ' . escapeshellarg($targetDir) . ' && git fetch origin main 2>&1 && git reset --hard origin/main 2>&1';
    }
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
