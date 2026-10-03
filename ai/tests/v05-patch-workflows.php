<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/code-thinking.php';
require_once __DIR__ . '/../includes/usage.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function remove_fixture(string $path): void
{
    if (!is_dir($path)) return;
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        chmod($item->getPathname(), $item->isDir() ? 0777 : 0666);
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

$fixture = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jaguar-test-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
try {
    foreach ([['git', 'init'], ['git', 'config', 'user.email', 'jaguar-test@example.invalid'], ['git', 'config', 'user.name', 'Jaguar Test']] as $argv) {
        expect(jaguar_code_command($argv, $fixture, 10)['ok'], 'Could not initialize Git fixture.');
    }
    file_put_contents($fixture . DIRECTORY_SEPARATOR . 'sample.php', "<?php echo 'old';\n");
    mkdir($fixture . DIRECTORY_SEPARATOR . 'src');
    file_put_contents($fixture . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'large.php', "<?php\n" . implode('', array_map(static fn($line) => '// line-' . $line . ' ' . str_repeat('x', 80) . "\n", range(1, 350))));
    expect(jaguar_code_command(['git', 'add', 'sample.php', 'src/large.php'], $fixture, 10)['ok'], 'Could not stage fixture.');
    expect(jaguar_code_command(['git', 'commit', '-m', 'fixture'], $fixture, 10)['ok'], 'Could not commit fixture.');
    $revision = jaguar_code_revision($fixture);
    expect($revision !== null, 'Fixture has no revision.');
    file_put_contents($fixture . DIRECTORY_SEPARATOR . 'sample.php', "<?php echo 'uncommitted';\n");
    $project = ['path' => $fixture, 'checks' => [[PHP_BINARY, '-l', 'sample.php']]];
    $context = jaguar_code_read_context($project, ['sample.php'], $revision);
    expect(count($context) === 1 && str_contains($context[0], "echo 'old'"), 'Context did not use the cited commit.');
    expect(!str_contains($context[0], 'uncommitted'), 'Uncommitted content leaked into cited context.');
    expect(jaguar_code_committed_file($fixture, 'src/large.php', $revision) === null, 'Oversized Git blob was loaded into full-file context.');
    $largeExcerpt = jaguar_code_read_context($project, ['src/large.php:120-125'], $revision);
    expect(count($largeExcerpt) === 1 && str_contains($largeExcerpt[0], 'PROJECT FILE: src/large.php') && str_contains($largeExcerpt[0], 'line-120') && !str_contains($largeExcerpt[0], 'line-150'), 'Bounded committed line range was not supplied accurately.');
    $valid = "diff --git a/sample.php b/sample.php\n--- a/sample.php\n+++ b/sample.php\n@@ -1 +1 @@\n-<?php echo 'old';\n+<?php echo 'new';\n";
    expect(jaguar_code_patch_diff($valid, ['sample.php']) !== null, 'Valid source diff was rejected.');
    expect(jaguar_code_patch_diff(str_replace('sample.php', 'invented.js', $valid), ['sample.php']) === null, 'Invented file was accepted.');
    $review = jaguar_code_verify_patch($project, $revision, $valid, ['sample.php']);
    expect(($review['verified'] ?? false) === true, 'Applicable patch did not pass isolated checks: ' . json_encode($review));
    expect(jaguar_code_review_check(['php', 'sample.php'], $fixture)['ok'] === false, 'A project-code execution check was allowed.');
    expect(jaguar_code_review_check(['php', '-l', '../sample.php'], $fixture)['ok'] === false, 'A syntax check escaped the checkout.');
    expect(str_contains($review['diff'], "echo 'new'"), 'Review diff is missing the applied change.');
    expect(file_get_contents($fixture . DIRECTORY_SEPARATOR . 'sample.php') === "<?php echo 'uncommitted';\n", 'Patch touched the configured project.');
    $bad = str_replace("echo 'old'", "echo 'missing'", $valid);
    $rejected = jaguar_code_verify_patch($project, $revision, $bad, ['sample.php']);
    expect(isset($rejected['error']), 'Non-applicable diff was accepted.');
    $invalidPhp = str_replace("echo 'new';", 'echo ;', $valid);
    $checked = jaguar_code_verify_patch($project, $revision, $invalidPhp, ['sample.php']);
    expect(($checked['verified'] ?? true) === false, 'A failing project lint was reported as verified.');
    expect(file_get_contents($fixture . DIRECTORY_SEPARATOR . 'sample.php') === "<?php echo 'uncommitted';\n", 'A failed check touched the configured project.');
} finally {
    remove_fixture($fixture);
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE beyond_wallets (id INTEGER PRIMARY KEY,user_id INTEGER UNIQUE,balance NUMERIC,currency TEXT,status TEXT)");
$pdo->exec("CREATE TABLE beyond_wallet_transactions (id INTEGER PRIMARY KEY,wallet_id INTEGER,amount NUMERIC,type TEXT,app_slug TEXT,description TEXT,idempotency_key TEXT UNIQUE)");
$migration = file_get_contents(__DIR__ . '/../../beyond-id/database/migrations/20260929_01_jaguar_draw_holds_sqlite.sql');
expect(is_string($migration), 'SQLite hold migration is missing.');
$pdo->exec($migration);
$pdo->exec("INSERT INTO beyond_wallets(id,user_id,balance,currency,status) VALUES(1,7,10,'BITS','active')");
expect(jaguar_wallet_reserve($pdo, 7, 10, 'draw-one')['ok'], 'First Draw hold failed.');
expect(!jaguar_wallet_reserve($pdo, 7, 10, 'draw-two')['ok'], 'Second Draw exceeded available balance.');
expect(jaguar_wallet_capture($pdo, 7, 'draw-one', 'Image')['ok'], 'Successful image was not captured.');
$chargedReceipt = jaguar_wallet_draw_receipt($pdo, 7, 'draw-one');
expect(($chargedReceipt['status'] ?? '') === 'charged' && $chargedReceipt['amount_bit_dollars'] === 10.0, 'Successful Draw receipt is incorrect.');
expect(jaguar_wallet_draw_receipt($pdo, 8, 'draw-one') === null, 'Another account could read the Draw receipt.');
expect(!jaguar_wallet_release($pdo, 7, 'draw-one'), 'Charged image was refunded.');
expect(jaguar_wallet_bit_balance($pdo, 7) === 0.0, 'Captured balance is incorrect.');
$pdo->exec('UPDATE beyond_wallets SET balance=10 WHERE user_id=7');
expect(jaguar_wallet_reserve($pdo, 7, 10, 'draw-failed')['ok'], 'Failed-image fixture hold was not created.');
expect(jaguar_wallet_release($pdo, 7, 'draw-failed'), 'Failed image hold was not released.');
expect((jaguar_wallet_draw_receipt($pdo, 7, 'draw-failed')['status'] ?? '') === 'released', 'Failed Draw receipt does not show a release.');
expect(jaguar_wallet_bit_balance($pdo, 7) === 10.0, 'Release did not restore balance.');
expect(jaguar_wallet_release($pdo, 7, 'draw-failed'), 'Idempotent release failed.');
expect(jaguar_wallet_bit_balance($pdo, 7) === 10.0, 'Repeat release credited twice.');
expect(jaguar_wallet_reserve($pdo, 7, 10, 'draw-abandoned')['ok'], 'Abandoned-image fixture hold was not created.');
$pdo->exec("UPDATE jaguar_draw_holds SET created_at=0 WHERE idempotency_key='draw-abandoned'");
expect(jaguar_wallet_bit_balance($pdo, 7) === 10.0, 'Stale Draw hold was not recovered.');
expect((int)$pdo->query('SELECT COUNT(*) FROM beyond_wallet_transactions')->fetchColumn() === 1, 'Wrong number of completed debits.');

echo "Jaguar v0.5 patch and wallet checks passed.\n";
