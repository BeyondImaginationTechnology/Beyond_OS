<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';

if (!beyond_sql_console_enabled()) {
    http_response_code(403);
    exit('SQL Console is restricted to super administrators and must be explicitly enabled for this deployment.');
}

$title = 'SQL Console';
$query = '';
$email = trim((string)($_POST['email'] ?? ''));
$error = '';
$result = null;
$driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

$requestedTable = trim((string)($_GET['table'] ?? ''));
if ($requestedTable !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $requestedTable)) {
    $quotedTable = $driver === 'sqlite' ? '"'.$requestedTable.'"' : '`'.$requestedTable.'`';
    $query = 'SELECT * FROM '.$quotedTable.' LIMIT 100;';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    $query = trim((string)($_POST['query'] ?? ''));
    $normalized = rtrim($query, " \t\n\r\0\x0B;");
    $verb = strtoupper((string)strtok(ltrim($normalized), " \t\r\n"));
    $readOnly = in_array($verb, ['SELECT','SHOW','DESCRIBE','DESC','EXPLAIN','PRAGMA'], true);
    $blocked = preg_match('/\b(ATTACH|DETACH|DROP|TRUNCATE|ALTER|LOAD_FILE|OUTFILE|INFILE|WRITABLE_SCHEMA)\b/i', $normalized);

    if ($normalized === '') $error = 'Enter a SQL command.';
    elseif (str_contains($normalized, ';')) $error = 'Only one SQL statement can run at a time.';
    elseif ($blocked) $error = 'Schema, file, and database attachment commands are blocked in the web console.';
    elseif (!$readOnly && (empty($_POST['confirm_write']) || (string)($_POST['write_confirmation'] ?? '') !== 'RUN WRITE')) $error = 'Confirm the write operation and enter RUN WRITE before running it.';
    elseif (str_contains($normalized, ':email') && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Enter the target user email address.';
    else {
        try {
            if (!$readOnly) $pdo->beginTransaction();
            $stmt = $pdo->prepare($normalized);
            $params = str_contains($normalized, ':email') ? ['email'=>$email] : [];
            $stmt->execute($params);
            $result = $readOnly ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [['message'=>'Command executed successfully','affected_rows'=>$stmt->rowCount()]];
            if (!$readOnly) $pdo->commit();
            log_activity($pdo, (int)$_SESSION['user_id'], 'sql_console_'.$verb);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Admin SQL console error: '.$exception->getMessage());
            $error = 'The database rejected the command. Check its syntax and constraints.';
        }
    }
}

$presets = [
    'Recent users' => 'SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC LIMIT 50;',
    'Find user by email' => 'SELECT id, name, email, role, status, created_at FROM users WHERE email = :email LIMIT 1;',
    'List administrators' => "SELECT id, name, email, role, status FROM users WHERE role IN ('admin','super_admin') ORDER BY id;",
    'Disable user' => "UPDATE users SET status = 'inactive' WHERE email = :email;",
    'Delete user' => 'DELETE FROM users WHERE email = :email;',
];

require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content"><h1>Protected SQL Console</h1><p class="muted">Super-admin-only, deployment-gated, single-statement console. Writes run in a transaction and require explicit confirmation.</p>
<div class="card"><h2>Command presets</h2><div style="display:flex;gap:8px;flex-wrap:wrap"><?php foreach($presets as $label=>$sql): ?><button class="btn sql-preset" type="button" data-sql="<?= e($sql) ?>"><?= e($label) ?></button><?php endforeach; ?></div></div>
<div class="card"><?php if($error): ?><div class="badge danger"><?= e($error) ?></div><?php endif; ?><form method="post" id="sql-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Target email for <code>:email</code><input type="email" name="email" value="<?= e($email) ?>" placeholder="member@example.com"></label><label>SQL command<textarea id="sql-query" name="query" rows="10" spellcheck="false" placeholder="SELECT id, email, role FROM users LIMIT 50;"><?= e($query) ?></textarea></label><label><input type="checkbox" name="confirm_write" value="1"> I understand and confirm this write operation.</label><label>Write confirmation<input type="text" name="write_confirmation" autocomplete="off" placeholder="Enter RUN WRITE for a write command"></label><p><button class="btn" type="submit">Run command</button> <a class="btn" href="database.php">Database explorer</a></p></form></div>
<?php if(is_array($result)): ?><div class="card"><h2>Results</h2><div style="overflow:auto"><table><?php if($result): ?><thead><tr><?php foreach(array_keys($result[0]) as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach($result as $row): ?><tr><?php foreach($row as $value): ?><td><?= e(is_scalar($value)||$value===null?(string)$value:json_encode($value)) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody><?php else: ?><tr><td>No results.</td></tr><?php endif; ?></table></div></div><?php endif; ?></section>
<script>document.querySelectorAll('.sql-preset').forEach(function(button){button.addEventListener('click',function(){document.getElementById('sql-query').value=button.dataset.sql||'';document.getElementById('sql-query').focus();});});</script>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
