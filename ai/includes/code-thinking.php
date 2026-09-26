<?php
declare(strict_types=1);

/** Load explicitly configured repository roots. Paths are trusted server config, never request data. */
function jaguar_code_projects(): array
{
    $raw = trim((string)getenv('JAGUAR_CODE_PROJECTS_JSON'));
    if ($raw === '') {
        $root = realpath(dirname(__DIR__, 2));
        if ($root === false || !file_exists($root . DIRECTORY_SEPARATOR . '.git')) return [];
        return ['beyond-os' => ['label' => 'Beyond OS', 'path' => $root, 'checks' => [['git', 'diff', '--check']]]];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) return [];
    $projects = [];
    foreach ($decoded as $id => $definition) {
        if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9_-]{0,47}$/', $id) || !is_array($definition)) continue;
        $path = realpath((string)($definition['path'] ?? ''));
        if ($path === false || !is_dir($path) || jaguar_code_revision($path) === null) continue;
        $checks = [];
        foreach (($definition['checks'] ?? []) as $check) {
            if (!is_array($check) || $check === [] || count($check) > 12) continue;
            $argv = array_values(array_filter($check, static fn($part) => is_string($part) && $part !== '' && strlen($part) <= 300));
            if (count($argv) === count($check)) $checks[] = $argv;
        }
        if ($checks === []) $checks[] = ['git', 'diff', '--check'];
        $projects[$id] = ['label' => trim((string)($definition['label'] ?? $id)), 'path' => $path, 'checks' => $checks];
    }
    return $projects;
}

function jaguar_code_revision(string $root): ?string
{
    if (!function_exists('proc_open')) return null;
    $pipes = [];
    $process = @proc_open(['git', '-C', $root, 'rev-parse', 'HEAD'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, null, ['bypass_shell' => true]);
    if (!is_resource($process)) return null;
    fclose($pipes[0]);
    $revision = trim((string)stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    return proc_close($process) === 0 && preg_match('/^[a-f0-9]{40,64}$/i', $revision) ? strtolower($revision) : null;
}

function jaguar_code_project(string $id): ?array
{
    return jaguar_code_projects()[$id] ?? null;
}

function jaguar_code_require_admin(): void
{
    $role = strtolower((string)($_SESSION['role'] ?? ''));
    if (empty($_SESSION['user_id']) || !in_array($role, ['admin', 'super_admin'], true)) {
        http_response_code(403);
        throw new RuntimeException('Code Thinking is available to Beyond administrators only.');
    }
}

function jaguar_code_read_context(array $project, array $requestedFiles): array
{
    $root = $project['path'];
    $files = [];
    foreach (['AGENTS.md', 'README.md'] as $relative) {
        $path = $root . DIRECTORY_SEPARATOR . $relative;
        if (is_file($path)) $files[$relative] = $path;
    }
    foreach (array_slice($requestedFiles, 0, 10) as $relative) {
        if (!is_string($relative) || strlen($relative) > 240 || str_contains($relative, "\0")) continue;
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if ($relative === '' || preg_match('/(^|[\\\/])\.\.([\\\/]|$)/', $relative) || str_starts_with($relative, DIRECTORY_SEPARATOR)) continue;
        $segments = array_map('strtolower', explode(DIRECTORY_SEPARATOR, $relative));
        $baseName = end($segments) ?: '';
        if (array_intersect($segments, ['.git', '.ssh', '.aws', '.config', '.secrets', 'private', 'var', 'storage', 'uploads', 'secrets', '.codex'])
            || $baseName === '.env' || str_starts_with($baseName, '.env.') || $baseName === 'live.php'
            || preg_match('/\.(pem|key|p12|pfx|kdbx)$/i', $baseName)
            || preg_match('/(secret|credential|private[-_]?key)/i', $baseName)) continue;
        $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if ($path === false || !str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) || !is_file($path)) continue;
        if (filesize($path) > 24000) continue;
        $files[$relative] = $path;
    }
    $context = [];
    foreach ($files as $relative => $path) {
        $content = @file_get_contents($path);
        if (is_string($content)) $context[] = "--- PROJECT FILE: {$relative} ---\n" . mb_substr($content, 0, 18000);
    }
    return $context;
}

function jaguar_code_memory_file(): string
{
    return beyond_private_file('ai/jaguar-code-memory.json', 'jaguar-code-memory.json');
}

function jaguar_code_current_notes(string $projectId, string $revision): array
{
    $path = jaguar_code_memory_file();
    if (!is_file($path)) return [];
    $data = json_decode((string)@file_get_contents($path), true);
    $notes = is_array($data[$projectId] ?? null) ? $data[$projectId] : [];
    return array_values(array_filter($notes, static fn($note) => is_array($note) && ($note['revision'] ?? '') === $revision));
}

function jaguar_code_save_notes(array $data): bool
{
    $path = jaguar_code_memory_file();
    if (!is_dir(dirname($path)) && !@mkdir(dirname($path), 0750, true) && !is_dir(dirname($path))) return false;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}
