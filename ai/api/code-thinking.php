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
        if ($body === '' || mb_strlen($body) > 1000 || mb_strlen($source) > 240) code_json(422, ['error' => 'Notes need text under 1,000 characters and a short source reference.']);
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
    $results = [];
    foreach (array_slice($project['checks'], 0, 5) as $argv) {
        $pipes = [];
        $process = @proc_open($argv, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $project['path'], null, ['bypass_shell' => true]);
        if (!is_resource($process)) { $results[] = ['command' => implode(' ', $argv), 'ok' => false, 'output' => 'Configured check could not start.']; continue; }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        $stdout = ''; $stderr = ''; $deadline = microtime(true) + 45; $exitCode = null;
        do {
            $stdout .= (string)stream_get_contents($pipes[1]); $stderr .= (string)stream_get_contents($pipes[2]);
            $state = proc_get_status($process);
            if (!$state['running']) { $exitCode = (int)$state['exitcode']; break; }
            usleep(100000);
        } while (microtime(true) < $deadline);
        $timedOut = $state['running'];
        if ($timedOut) proc_terminate($process);
        $stdout .= (string)stream_get_contents($pipes[1]); $stderr .= (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit === -1 && $exitCode !== null) $exit = $exitCode;
        $results[] = ['command' => implode(' ', $argv), 'ok' => !$timedOut && $exit === 0, 'timed_out' => $timedOut, 'output' => mb_substr(trim($stdout . "\n" . $stderr), 0, 5000)];
    }
    code_json(200, ['revision' => $revision, 'results' => $results, 'configured' => count($project['checks'])]);
}

if (!in_array($action, ['read', 'plan', 'patch'], true)) code_json(422, ['error' => 'Choose read, plan, patch, or a configured project check.']);
$task = trim((string)($payload['task'] ?? ''));
if ($task === '' || mb_strlen($task) > 6000) code_json(422, ['error' => 'Describe the project question or change in 1–6,000 characters.']);
$contextParts = jaguar_code_read_context($project, is_array($payload['files'] ?? null) ? $payload['files'] : []);
$fileContext = array_values(array_filter($contextParts, static fn($entry) => str_starts_with($entry, '--- PROJECT FILE: ')));
if ($fileContext === []) code_json(200, ['revision' => $revision, 'action' => $action, 'message' => 'I could not read project files for this request, so I will not make repository-specific claims. No files were changed. Plan: provide relative paths for the relevant files or add an AGENTS.md/README.md project guide, then retry. I verified only the workspace Git revision.']);
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
    'patch' => 'Draft a review-only unified diff for the selected project. First state affected files, assumptions, risks, and verification steps. Then provide a unified diff. Do not claim it was applied or that checks ran. If context is insufficient, ask for the missing files instead of inventing code. Use approved BIT-wide notes only as shared guidance.',
];
$runtimeUrl = rtrim((string)getenv('JAGUAR_RUNTIME_URL'), '/');
if ($runtimeUrl === '' || !filter_var($runtimeUrl, FILTER_VALIDATE_URL) || !function_exists('curl_init')) code_json(200, ['revision' => $revision, 'action' => $action, 'context_files' => array_map(static fn($entry) => preg_match('/^--- PROJECT FILE: (.+) ---/', $entry, $m) ? $m[1] : null, $fileContext), 'message' => 'Jaguar’s model runtime is unavailable, so I could not analyze the supplied files or draft a repository-grounded diff. No files were changed. Plan: configure the private runtime URL and token, then retry this task; the selected context is ready.']);
$runtimeToken = trim((string)getenv('JAGUAR_RUNTIME_TOKEN'));
$headers = ['Content-Type: application/json'];
if ($runtimeToken !== '') $headers[] = 'Authorization: Bearer ' . $runtimeToken;
$runtime = curl_init($runtimeUrl . '/v1/chat');
$request = [
    'mode' => 'code', 'language' => 'en', 'max_new_tokens' => 1024,
    'project_context' => mb_substr(implode("\n\n", $contextParts), 0, 24000),
    'messages' => [['role' => 'user', 'content' => $instructions[$action] . "\n\nTask: " . $task]],
];
curl_setopt_array($runtime, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 12, CURLOPT_TIMEOUT => 110, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
$response = curl_exec($runtime); $status = (int)curl_getinfo($runtime, CURLINFO_RESPONSE_CODE); curl_close($runtime);
$decoded = is_string($response) ? json_decode($response, true) : null;
if (!is_array($decoded) || $status < 200 || $status >= 300 || !is_string($decoded['message'] ?? null)) code_json(200, ['revision' => $revision, 'action' => $action, 'message' => 'Jaguar’s model runtime did not complete this request. No repository files were changed. Plan: confirm the private runtime is healthy, then retry the same task at the displayed revision.']);
code_json(200, ['revision' => $revision, 'action' => $action, 'message' => $decoded['message'], 'context_files' => array_map(static fn($entry) => preg_match('/^--- PROJECT FILE: (.+) ---/', $entry, $m) ? $m[1] : null, array_filter($contextParts, static fn($entry) => str_starts_with($entry, '--- PROJECT FILE: ')))]);
