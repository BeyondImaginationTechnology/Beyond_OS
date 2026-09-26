<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$user = Auth::user();
$db = null;
$error = '';
$notice = '';
$adminId = (int)($user['id'] ?? 0);

function jaguar_text_length(string $text): int
{
    if (function_exists('mb_strlen')) return mb_strlen($text, 'UTF-8');
    if (function_exists('iconv_strlen')) return (int)iconv_strlen($text, 'UTF-8');
    return strlen($text);
}

function jaguar_excerpt(string $text, int $width = 180): string
{
    if (function_exists('mb_strimwidth')) return mb_strimwidth($text, 0, $width, '…', 'UTF-8');
    if (function_exists('iconv_substr') && function_exists('iconv_strlen')) {
        if (iconv_strlen($text, 'UTF-8') <= $width) return $text;
        return iconv_substr($text, 0, max(0, $width - 1), 'UTF-8') . '…';
    }
    return strlen($text) <= $width ? $text : substr($text, 0, max(0, $width - 3)) . '…';
}

$storageError = 'Training storage is temporarily unavailable. Please refresh the page or contact an administrator.';
try {
    $db = DailyStudio::db();
} catch (Throwable $exception) {
    error_log('Jaguar Studio database unavailable: ' . $exception->getMessage());
    http_response_code(503);
    $error = $storageError;
}

$types = [
    'sacred_text_history' => 'Quran / Bible / Tanakh history',
    'terminology' => 'Surah / ayah / scripture terminology',
    'concept_explanation' => 'Faith concept explanation',
    'passage_theme' => 'Passage theme',
    'app_help' => 'DailyBreath app help',
    'breath_reflection' => 'Breathing and reflection prompt',
    'sensitive_support' => 'Sensitive support and recovery',
    'lesson_qa' => 'General lesson Q&A',
    'socratic_tutor' => 'Socratic tutoring',
];
$statuses = ['draft' => 'Draft', 'approved' => 'Approved', 'rejected' => 'Rejected'];

if (($_GET['download'] ?? '') === 'approved') {
    if (!($db instanceof PDO)) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo $storageError;
        exit;
    }
    header('Content-Type: application/x-ndjson; charset=utf-8');
    header('Content-Disposition: attachment; filename="jaguar-approved-training.jsonl"');
    try {
        foreach ($db->query("SELECT instruction,input_text,output_text,example_type FROM jaguar_training_examples WHERE status='approved' ORDER BY id ASC") as $row) {
            echo json_encode(['instruction'=>$row['instruction'],'input'=>$row['input_text'],'output'=>$row['output_text'],'type'=>$row['example_type']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
    } catch (Throwable $exception) {
        error_log('Jaguar Studio training export failed: ' . $exception->getMessage());
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo $storageError;
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !($db instanceof PDO)) {
    $error = $storageError;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please reload the page.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action === 'create') {
                $instruction = trim((string)($_POST['instruction'] ?? ''));
                $input = trim((string)($_POST['input_text'] ?? ''));
                $output = trim((string)($_POST['output_text'] ?? ''));
                $type = (string)($_POST['example_type'] ?? '');
                if ($instruction === '' || $output === '') throw new RuntimeException('Instruction and answer are required.');
                if (!isset($types[$type])) throw new RuntimeException('Choose a valid training category.');
                if (jaguar_text_length($instruction) > 4000 || jaguar_text_length($input) > 8000 || jaguar_text_length($output) > 12000) throw new RuntimeException('This example is too long.');
                $q = $db->prepare('INSERT INTO jaguar_training_examples(instruction,input_text,output_text,example_type,status,created_by) VALUES(?,?,?,?,?,?)');
                $q->execute([$instruction,$input,$output,$type,'draft',$adminId]);
                $notice = 'Training example saved as a draft for review.';
            } elseif (in_array($action, ['approve','reject'], true) && $id > 0) {
                $status = $action === 'approve' ? 'approved' : 'rejected';
                $q = $db->prepare('UPDATE jaguar_training_examples SET status=?,reviewed_by=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $q->execute([$status,$adminId,$id]);
                $notice = $status === 'approved' ? 'Example approved for the next export.' : 'Example rejected.';
            } elseif ($action === 'delete' && $id > 0) {
                $db->prepare('DELETE FROM jaguar_training_examples WHERE id=?')->execute([$id]);
                $notice = 'Example deleted.';
            }
        } catch (Throwable $exception) {
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The training example could not be saved.';
            error_log('Jaguar Studio training action failed: ' . $exception->getMessage());
        }
    }
}

$counts = array_fill_keys(array_keys($statuses), 0);
$rows = [];
if ($db instanceof PDO) {
    try {
        foreach ($db->query('SELECT status,COUNT(*) AS total FROM jaguar_training_examples GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) $counts[$row['status']] = (int)$row['total'];
        }
        $rows = $db->query('SELECT id,instruction,input_text,output_text,example_type,status FROM jaguar_training_examples ORDER BY updated_at DESC,id DESC LIMIT 100')->fetchAll();
    } catch (Throwable $exception) {
        error_log('Jaguar Studio training queue failed: ' . $exception->getMessage());
        http_response_code(503);
        $error = $storageError;
    }
}
require dirname(__DIR__) . '/_header.php';
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css"><link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css">
<style>
.jaguar-training{max-width:1220px}.jaguar-training .studio-head{align-items:flex-start}.jaguar-training .lead{max-width:760px}.training-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(280px,.75fr);gap:18px}.training-card{padding:24px;border:1px solid #dbe3f0;border-radius:22px;background:#fff}.training-card h2{margin-top:0}.training-card label{display:grid;gap:7px;margin:14px 0;font-weight:800;color:#30405b}.training-card input,.training-card textarea,.training-card select{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cbd7e7;border-radius:10px;background:#fbfdff;color:#13203a;font:inherit}.training-card textarea{resize:vertical}.training-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:18px 0}.training-stat{padding:16px;border-radius:16px;background:#edf2ff}.training-stat strong{display:block;font-size:28px}.training-stat small{color:#66748b}.training-table{width:100%;border-collapse:collapse}.training-table th,.training-table td{padding:13px 10px;border-bottom:1px solid #e5eaf2;text-align:left;vertical-align:top}.training-table th{font-size:12px;color:#66748b}.training-table small{display:block;max-width:460px;color:#66748b;white-space:pre-wrap}.training-actions{display:flex;gap:6px;flex-wrap:wrap}.training-actions button{border:0;cursor:pointer}.studio-alert{margin:15px 0;padding:13px 16px;border-radius:12px;background:#e8f7ed;color:#17653a}.studio-alert.error{background:#ffeded;color:#9b2929}@media(max-width:850px){.training-grid{grid-template-columns:1fr}.training-table{min-width:850px}}
</style>
<style>
.jaguar-training .training-card{color:#172033;background:#fff}.jaguar-training .training-card h2{color:#172033}.jaguar-training .training-card .muted{color:#475467!important}.jaguar-training .training-stat{color:#172033;background:#edf2ff}.jaguar-training .training-stat small{color:#475467}.jaguar-training .training-card input::placeholder,.jaguar-training .training-card textarea::placeholder{color:#667085;opacity:1;font-weight:500}
</style>
<section class="studio-console jaguar-training">
  <header class="studio-head"><div><p class="studio-eyebrow">Beyond Studio · DailyBreath · Jaguar</p><h1>Teach Jaguar to answer <span>with care.</span></h1><p class="lead">Create reviewed examples for scripture history, terminology, passage themes, app help, breathing prompts, and sensitive support. Approved examples export in the JSONL format used by Beyond-1.</p></div><a class="btn" href="?download=approved">Download approved JSONL</a></header>
  <?php if ($notice): ?><div class="studio-alert"><?=DailyStudio::esc($notice)?></div><?php endif; ?><?php if ($error): ?><div class="studio-alert error"><?=DailyStudio::esc($error)?></div><?php endif; ?>
  <div class="training-stats"><?php foreach ($statuses as $status=>$label): ?><div class="training-stat"><strong><?=number_format($counts[$status])?></strong><small><?=DailyStudio::esc($label)?> examples</small></div><?php endforeach; ?></div>
  <div class="training-grid">
    <article class="training-card"><h2>Add a training example</h2><p class="muted">Write the ideal answer Jaguar should learn. Never include private chats, personal data, API keys, or copyrighted training dumps.</p><form method="post"><input type="hidden" name="csrf" value="<?=DailyStudio::esc(Auth::csrf())?>"><input type="hidden" name="action" value="create"><label>Category<select name="example_type" required><?php foreach($types as $value=>$label): ?><option value="<?=DailyStudio::esc($value)?>"><?=DailyStudio::esc($label)?></option><?php endforeach; ?></select></label><label>Instruction<input name="instruction" maxlength="4000" placeholder="Explain when the Quran was compiled." required></label><label>Optional context or user question<textarea name="input_text" maxlength="8000" rows="4" placeholder="A learner asks this in a DailyBreath chat."></textarea></label><label>Ideal answer<textarea name="output_text" maxlength="12000" rows="9" placeholder="Write a careful, concise, respectful answer. Mention uncertainty when dates or traditions differ." required></textarea></label><button class="btn" type="submit">Save draft</button></form></article>
    <article class="training-card"><h2>Training workflow</h2><p class="muted">DailyBreath training is separated from production content so an editor can review every example before it is exported.</p><ol><li>Add a human-edited example.</li><li>Approve only accurate, safe examples.</li><li>Download the approved JSONL.</li><li>Upload it to the deliberate Jaguar training job.</li></ol><p class="muted">This page does not start GPU jobs or store Hugging Face, Modal, or runtime credentials.</p></article>
  </div>
  <article class="training-card" style="margin-top:18px"><div class="studio-head"><div><p class="studio-eyebrow">Review queue</p><h2>Recent examples</h2></div><span class="muted">Up to 100 latest</span></div><div style="overflow:auto"><table class="training-table"><thead><tr><th>Example</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><strong><?=DailyStudio::esc($row['instruction'])?></strong><?php if($row['input_text']!==''): ?><small><?=DailyStudio::esc($row['input_text'])?></small><?php endif; ?><small><?=DailyStudio::esc(jaguar_excerpt($row['output_text']))?></small></td><td><?=DailyStudio::esc($types[$row['example_type']]??$row['example_type'])?></td><td><?=DailyStudio::esc($statuses[$row['status']]??$row['status'])?></td><td><form class="training-actions" method="post"><input type="hidden" name="csrf" value="<?=DailyStudio::esc(Auth::csrf())?>"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><?php if($row['status']!=='approved'): ?><button class="btn alt" name="action" value="approve">Approve</button><?php endif; ?><?php if($row['status']!=='rejected'): ?><button class="btn alt" name="action" value="reject">Reject</button><?php endif; ?><button class="btn alt" name="action" value="delete">Delete</button></form></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="4" class="muted">No training examples yet.</td></tr><?php endif; ?></tbody></table></div></article>
</section>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
