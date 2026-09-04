<?php
require __DIR__ . '/../includes/admin-check.php';
require_once __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/db.php';
$title = 'Admin Review';
require __DIR__ . '/../includes/admin-header.php';
require __DIR__ . '/../includes/admin-sidebar.php';
?>
<section class="content">
  <div class="page-heading">
    <div>
      <p class="eyebrow">Quality control</p>
      <h1>Admin Review</h1>
      <p class="muted">Review users, content, database health, and Daily Breath publishing tools.</p>
    </div>
  </div>
  <div class="grid">
    <div class="card">
      <h2>Publishing tools</h2>
      <p class="muted">Create daily artwork, save drafts, add narration, and prepare social posts.</p>
      <p><a class="btn" href="verse-generator.php">Open Verse Generator</a></p>
      <p><a class="btn btn-secondary" href="french-generator.php">Open Fran&ccedil;ais du Jour Generator</a></p>
    </div>
    <div class="card">
      <h2>Database and access</h2>
      <div class="tools-list">
        <a class="tool-link" href="users.php"><span>Review users and roles</span><span aria-hidden="true">&rarr;</span></a>
        <a class="tool-link" href="database.php"><span>Database Explorer</span><span aria-hidden="true">&rarr;</span></a>
        <?php if (beyond_sql_console_enabled()): ?><a class="tool-link" href="sql.php"><span>Protected SQL Console</span><span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        <a class="tool-link" href="logs.php"><span>Audit Logs</span><span aria-hidden="true">&rarr;</span></a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
