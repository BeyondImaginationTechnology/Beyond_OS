<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/code-thinking.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function code_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    jaguar_code_require_admin();
} catch (Throwable $error) {
    code_json(403, ['error' => 'Code Thinking is available to Beyond administrators only.']);
}

$projects = jaguar_code_projects();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $items = [];
    $memoryPath = jaguar_code_memory_file();
    $allMemory = is_file($memoryPath) ? json_decode((string)@file_get_contents($memoryPath), true) : [];
    if (!is_array($allMemory)) $allMemory = [];
    foreach ($projects as $id => $project) {
        $revision = jaguar_code_revision($project['path']);
        $projectNotes = is_array($allMemory[$id] ?? null) ? $allMemory[$id] : [];
        $currentNotes = $revision ? jaguar_code_current_notes($id, $revision) : [];
        $currentIds = array_column($currentNotes, 'id');
        $staleNotes = array_values(array_filter($projectNotes, static fn($note) => is_array($note) && !in_array($note['id'] ?? '', $currentIds, true)));
        $items[] = ['id' => $id, 'label' => $project['label'], 'revision' => $revision, 'checks' => count($project['checks']), 'notes' => $currentNotes, 'stale_notes' => $staleNotes];
    }
    $sharedNotes = is_array($allMemory['_bit_architecture'] ?? null) ? $allMemory['_bit_architecture'] : [];
    foreach ($sharedNotes as &$note) {
        if (!is_array($note)) continue;
        $sourceProject = $projects[$note['source_project'] ?? ''] ?? null;
        $sourceRevision = is_array($sourceProject) ? jaguar_code_revision($sourceProject['path']) : null;
        $note['stale'] = empty($note['approved']) || ($note['scope'] ?? '') !== 'bit' || $sourceRevision === null || ($note['revision'] ?? '') !== $sourceRevision;
        $note['source_label'] = is_array($sourceProject) ? $sourceProject['label'] : 'Unavailable project';
    }
    unset($note);
    code_json(200, ['projects' => $items, 'shared_notes' => $sharedNotes, 'ready' => $items !== []]);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') code_json(405, ['error' => 'Method not allowed.']);
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) code_json(403, ['error' => 'Your secure session expired. Refresh Code Thinking and try again.']);

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) code_json(400, ['error' => 'Invalid request.']);
$projectId = is_string($payload['project'] ?? null) ? $payload['project'] : '';
$project = $projects[$projectId] ?? null;
if (!is_array($project)) code_json(422, ['error' => 'Choose an authorized BIT project.']);
$action = is_string($payload['action'] ?? null) ? $payload['action'] : 'plan';
$revision = jaguar_code_revision($project['path']);

if (in_array($action, ['note_add', 'note_edit', 'note_delete'], true)) {
    $memoryPath = jaguar_code_memory_file();
    $memory = is_file($memoryPath) ? json_decode((string)@file_get_contents($memoryPath), true) : [];
    if (!is_array($memory)) $memory = [];
    $scope = is_string($payload['scope'] ?? null) ? $payload['scope'] : (!empty($payload['share_bit_wide']) ? 'bit' : 'project');
    if (!in_array($scope, ['project', 'bit'], true)) code_json(422, ['error' => 'Choose a valid project memory scope.']);
    $memoryKey = $scope === 'bit' ? '_bit_architecture' : $projectId;
    $notes = is_array($memory[$memoryKey] ?? null) ? $memory[$memoryKey] : [];
    $noteId = is_string($payload['note_id'] ?? null) ? $payload['note_id'] : '';
    if ($action === 'note_delete') {
        $notes = array_values(array_filter($notes, static fn($note) => !is_array($note) || ($note['id'] ?? '') !== $noteId));
    } else {
        if ($revision === null) code_json(409, ['error' => 'Project notes require a readable Git revision. You can still remove stale notes.']);
        $body = trim((string)($payload['note'] ?? ''));
        $source = trim((string)($payload['source'] ?? ''));
        if ($body === '' || $source === '' || mb_strlen($body) > 1000 || mb_strlen($source) > 240) code_json(422, ['error' => 'Notes need text under 1,000 characters and a source reference.']);
        if ($action === 'note_edit') {
            $found = false;
            foreach ($notes as &$note) {
                if (is_array($note) && ($note['id'] ?? '') === $noteId) {
                    $note['text'] = $body; $note['source'] = $source; $note['revision'] = $revision; $note['updated_at'] = gmdate('c');
                    if ($scope === 'bit') { $note['source_project'] = $projectId; $note['approved'] = true; $note['scope'] = 'bit'; }
                    $found = true; break;
                }
            }
            unset($note);
            if (!$found) code_json(404, ['error' => 'That project note is no longer available.']);
        } else {
            $notes[] = ['id' => bin2hex(random_bytes(12)), 'text' => $body, 'source' => $source, 'revision' => $revision, 'updated_at' => gmdate('c')];
            if ($scope === 'bit') { $index = array_key_last($notes); $notes[$index]['source_project'] = $projectId; $notes[$index]['approved'] = true; $notes[$index]['scope'] = 'bit'; }
        }
    }
    $memory[$memoryKey] = $notes;
    if (!jaguar_code_save_notes($memory)) code_json(503, ['error' => 'Private project memory could not be saved.']);
    code_json(200, ['ok' => true, 'notes' => $scope === 'project' && $revision !== null ? jaguar_code_current_notes($projectId, $revision) : ($scope === 'bit' ? $notes : [])]);
}

if ($revision === null) code_json(200, ['project' => $project['label'], 'revision' => null, 'action' => $action, 'message' => 'I cannot inspect or cite this workspace because Git did not provide a repository revision. No files were read or changed. Plan: configure this authorized workspace as a readable Git checkout, then retry; until then, provide the relevant files or ask for a general plan that does not claim repository knowledge.']);

if ($action === 'check') {
    $review = jaguar_code_with_checkout($project, $revision, static function (string $checkout, string $projectCheckout) use ($project): array {
        $results = [];
        foreach (array_slice($project['checks'], 0, 5) as $argv) {
            $results[] = ['command' => implode(' ', $argv), ...jaguar_code_review_check($argv, $projectCheckout)];
        }
        return ['results' => $results];
    });
    if (isset($review['error'])) code_json(503, ['error' => $review['error']]);
    code_json(200, ['revision' => $revision, 'results' => $review['results'], 'configured' => count($project['checks']), 'checkout' => 'detached review checkout']);
}

if (!in_array($action, ['read', 'plan', 'patch'], true)) code_json(422, ['error' => 'Choose read, plan, patch, or a configured project check.']);
$task = trim((string)($payload['task'] ?? ''));
if ($task === '' || mb_strlen($task) > 2500) code_json(422, ['error' => 'Describe the project question or change in 1–2,500 characters.']);
$requestedFiles = is_array($payload['files'] ?? null) ? $payload['files'] : [];
if ($action === 'patch' && array_filter($requestedFiles, static fn($value) => is_string($value) && trim($value) !== '') === []) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => [], 'verified' => false, 'message' => 'Choose the existing project source files to change before requesting a patch. No diff was accepted or checks run.']);
$contextParts = jaguar_code_read_context($project, $requestedFiles, $revision);
$fileContext = array_values(array_filter($contextParts, static fn($entry) => str_starts_with($entry, '--- PROJECT FILE: ')));
$contextFiles = array_values(array_filter(array_map(static fn($entry) => preg_match('/^--- PROJECT FILE: (.+) ---/', $entry, $m) ? $m[1] : null, $fileContext)));
if ($fileContext === []) code_json(200, ['revision' => $revision, 'action' => $action, 'message' => 'I could not read project files for this request, so I will not make repository-specific claims. No files were changed. Plan: provide relative paths for the relevant files; for a large file, append a line range such as ai/chat.php:280-380. I verified only the workspace Git revision.']);
$patchFiles = [];
if ($action === 'patch') {
    $missing = [];
    foreach (array_slice($requestedFiles, 0, 10) as $specification) {
        if (!is_string($specification) || trim($specification) === '') continue;
        $relative = preg_replace('/:[1-9][0-9]{0,5}-[1-9][0-9]{0,5}$/', '', $specification);
        $relative = str_replace('\\', '/', (string)$relative);
        if (!in_array($relative, $contextFiles, true)) $missing[] = mb_substr($relative, 0, 240);
        else $patchFiles[$relative] = $relative;
    }
    if ($missing !== []) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'verified' => false, 'message' => 'One or more requested files could not be supplied at this revision: ' . implode(', ', array_slice($missing, 0, 10)) . '. For a large file, request a bounded line range such as ai/chat.php:280-380. No diff was accepted or checks run.']);
}
$notes = jaguar_code_current_notes($projectId, $revision);
$contextParts[] = "PROJECT ID: {$projectId}\nPROJECT LABEL: {$project['label']}\nGIT REVISION: {$revision}";
foreach ($notes as $note) $contextParts[] = '--- REVISION-LINKED PROJECT NOTE (' . ($note['source'] ?: 'no source supplied') . ") ---\n" . $note['text'];
$memoryPath = jaguar_code_memory_file();
$memory = is_file($memoryPath) ? json_decode((string)@file_get_contents($memoryPath), true) : [];
$sharedNotes = is_array($memory['_bit_architecture'] ?? null) ? $memory['_bit_architecture'] : [];
foreach ($sharedNotes as $note) {
    if (!is_array($note) || empty($note['approved']) || ($note['scope'] ?? '') !== 'bit') continue;
    $sourceProject = $projects[$note['source_project'] ?? ''] ?? null;
    if (!is_array($sourceProject) || jaguar_code_revision($sourceProject['path']) !== ($note['revision'] ?? '')) continue;
    $contextParts[] = '--- ADMIN-APPROVED BIT-WIDE ARCHITECTURE NOTE (source: ' . $sourceProject['label'] . ' / ' . ($note['source'] ?: 'no reference') . ' / ' . substr((string)$note['revision'], 0, 12) . ") ---\n" . $note['text'];
}
$instructions = [
    'read' => 'Read the selected project context and answer the administrator. Cite file paths for repository claims. State which files were available. Use a BIT-wide architecture note only as explicitly approved shared guidance, not as evidence about this project.',
    'plan' => 'Create a concise implementation plan for the selected project. Show affected files (or say they are not yet known), assumptions, risks, and verification steps. Cite file paths for claims. Use approved BIT-wide notes only as shared guidance.',
    'patch' => 'Draft a review-only unified Git diff against the supplied revision. First state affected files, assumptions, risks, and verification steps. Then provide a diff starting with diff --git lines. Some files may be cited excerpts; do not infer unseen lines. Edit only the existing project files supplied in this request; no new files, renames, or binary changes. The server will apply it only in a disposable checkout and run configured static checks there. Do not claim checks passed. If context is insufficient, ask for missing files instead of inventing code.',
];
$runtimeUrl = rtrim(jaguar_runtime_config('runtime_url'), '/');
if ($runtimeUrl === '' || !filter_var($runtimeUrl, FILTER_VALIDATE_URL) || !function_exists('curl_init')) code_json(200, ['revision' => $revision, 'action' => $action, 'context_files' => array_map(static fn($entry) => preg_match('/^--- PROJECT FILE: (.+) ---/', $entry, $m) ? $m[1] : null, $fileContext), 'message' => 'Jaguar’s model runtime is unavailable, so I could not analyze the supplied files or draft a repository-grounded diff. No files were changed. Plan: configure the private runtime URL and token, then retry this task; the selected context is ready.']);
$runtimeToken = jaguar_runtime_config('runtime_token');
if ($runtimeToken === '') code_json(200, ['revision' => $revision, 'action' => $action, 'context_files' => array_map(static fn($entry) => preg_match('/^--- PROJECT FILE: (.+) ---/', $entry, $m) ? $m[1] : null, $fileContext), 'message' => 'Jaguar’s runtime authentication is not configured, so I could not analyze the supplied files or draft a repository-grounded diff. No files were changed. Plan: configure the private runtime URL and token, then retry this task; the selected context is ready.']);
$headers = ['Content-Type: application/json'];
$headers[] = 'Authorization: Bearer ' . $runtimeToken;
$runtime = curl_init($runtimeUrl . '/v1/chat');
$fullProjectContext = implode("\n\n", $contextParts);
$phpContextTruncated = mb_strlen($fullProjectContext) > 24000;
if ($action === 'patch' && $phpContextTruncated) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'message' => 'The selected project context exceeds the 24,000-character limit. Select fewer files before requesting a patch; no diff was accepted or checks run.', 'verified' => false]);
$request = [
    'mode' => 'code', 'language' => 'en', 'max_new_tokens' => 1024,
    'project_context' => mb_substr($fullProjectContext, 0, 24000),
    'messages' => [['role' => 'user', 'content' => $instructions[$action] . "\n\nTask: " . $task]],
];
curl_setopt_array($runtime, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 110, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
$response = curl_exec($runtime); $status = (int)curl_getinfo($runtime, CURLINFO_RESPONSE_CODE); curl_close($runtime);
$decoded = is_string($response) ? json_decode($response, true) : null;
if (is_array($decoded) && $status === 422 && is_string($decoded['detail'] ?? null)) code_json(422, ['error' => $decoded['detail'], 'revision' => $revision]);
if (!is_array($decoded) || $status < 200 || $status >= 300 || !is_string($decoded['message'] ?? null)) code_json(200, ['revision' => $revision, 'action' => $action, 'message' => 'Jaguar’s model runtime did not complete this request. No repository files were changed. Plan: confirm the private runtime is healthy, then retry the same task at the displayed revision.']);
if ($action === 'patch') {
    if (!empty($decoded['context_truncated'])) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'message' => 'The supplied project context exceeded the model limit. No diff was accepted or checks run. Select fewer files and retry.', 'verified' => false]);
    $diff = jaguar_code_patch_diff($decoded['message'], array_values($patchFiles));
    if ($diff === null) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'message' => 'Jaguar did not produce a bounded unified diff for the supplied files. No patch was accepted or checks run. Review the task and provide the affected source files; do not use the model text as a patch.', 'verified' => false]);
    $review = jaguar_code_verify_patch($project, $revision, $diff, array_values($patchFiles));
    if (isset($review['error'])) code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'message' => $review['error'] . ' No project files were changed. ' . ($review['detail'] ?? ''), 'verified' => false]);
    $checkSummary = implode("\n", array_map(static fn($check) => ($check['ok'] ? 'PASS' : 'FAIL') . ' ' . $check['command'] . ($check['timed_out'] ? ' (timed out)' : '') . ($check['output'] !== '' ? "\n" . $check['output'] : ''), $review['checks']));
    $modelNotes = trim(substr($decoded['message'], 0, (int)strpos($decoded['message'], 'diff --git')));
    code_json(200, ['revision' => $revision, 'action' => 'patch', 'context_files' => $contextFiles, 'affected_files' => $review['affected_files'], 'checks' => $review['checks'], 'verified' => $review['verified'], 'review' => ['diff_applies' => true, 'checks_passed' => $review['verified'], 'human_review_required' => true], 'diff' => $review['diff'], 'message' => "Affected files: " . implode(', ', $review['affected_files']) . "\nModel assumptions, risks, and plan (unverified):\n" . ($modelNotes === '' ? 'Not supplied; review these before using the patch.' : mb_substr($modelNotes, 0, 3000)) . "\nVerification: " . ($review['verified'] ? 'all configured checks passed' : 'one or more checks failed') . " in a detached checkout; human review is still required. No project files were changed.\n\n" . $checkSummary . "\n\nReview diff:\n" . $review['diff']]);
}
code_json(200, ['revision' => $revision, 'action' => $action, 'message' => $decoded['message'], 'context_truncated' => $phpContextTruncated || !empty($decoded['context_truncated']), 'context_files' => $contextFiles]);
