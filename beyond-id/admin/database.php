<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

$driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    if ($driver === 'sqlite' && ($_POST['action'] ?? '') === 'checkpoint') {
        $pdo->exec('PRAGMA wal_checkpoint(PASSIVE)');
        $message = 'SQLite write-ahead log checkpoint completed.';
    }
}

if ($driver === 'sqlite') {
    $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    $tableNames = array_map(static fn(array $row): string => (string)$row['name'], $statement->fetchAll());
} else {
    $statement = $pdo->query('SHOW TABLES');
    $tableNames = array_map(static fn(array $row): string => (string)array_values($row)[0], $statement->fetchAll());
}

$tables = [];
foreach ($tableNames as $name) {
    $quoted = $driver === 'sqlite' ? '"' . str_replace('"', '""', $name) . '"' : '`' . str_replace('`', '``', $name) . '`';
    try { $count = (int)$pdo->query('SELECT COUNT(*) FROM ' . $quoted)->fetchColumn(); }
    catch (Throwable $exception) { $count = -1; }
    $tables[] = ['name'=>$name, 'rows'=>$count];
}

$phpMyAdminUrl = trim((string)getenv('BEYOND_PHPMYADMIN_URL'));
if ($phpMyAdminUrl !== '' && !preg_match('#^https://#i', $phpMyAdminUrl)) $phpMyAdminUrl = '';
$title = 'Database';
require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content">
  <h1>Database Explorer</h1>
  <p>Connected through PDO using <strong><?= e(strtoupper($driver)) ?></strong>. This page exposes schema names and row counts only.</p>
  <?php if ($message): ?><div class="alert"><?= e($message) ?></div><?php endif; ?>
  <div class="card">
    <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
      <strong><?= count($tables) ?> application tables</strong>
      <div style="display:flex;gap:8px">
        <?php if ($driver === 'sqlite'): ?>
          <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="checkpoint"><button class="btn" type="submit">Checkpoint SQLite WAL</button></form>
        <?php elseif ($driver === 'mysql' && $phpMyAdminUrl !== ''): ?>
          <a class="btn" href="<?= e($phpMyAdminUrl) ?>" target="_blank" rel="noopener noreferrer">Open phpMyAdmin</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card"><table><thead><tr><th>Table</th><th>Rows</th><th>Option</th></tr></thead><tbody>
    <?php foreach ($tables as $table): ?><tr><td><code><?= e($table['name']) ?></code></td><td><?= $table['rows'] >= 0 ? number_format($table['rows']) : 'Unavailable' ?></td><td><?php if (beyond_sql_console_enabled()): ?><a href="sql.php?table=<?= rawurlencode($table['name']) ?>">Open SQL console</a><?php else: ?>Restricted<?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php if ($driver === 'mysql' && $phpMyAdminUrl === ''): ?><p class="muted">Set <code>BEYOND_PHPMYADMIN_URL</code> to an HTTPS URL to display the phpMyAdmin option. Credentials are never embedded in the link.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
