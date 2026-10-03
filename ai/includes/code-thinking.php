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

/** Run a fixed argv command without a shell; output is bounded for model-facing responses. */
function jaguar_code_command(array $argv, string $cwd, int $seconds = 45, int $outputLimit = 5000, bool $preserveOutput = false): array
{
    $pipes = [];
    $process = @proc_open($argv, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd, null, ['bypass_shell' => true]);
    if (!is_resource($process)) return ['ok' => false, 'output' => 'Command could not start.', 'timed_out' => false];
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = '';
    $deadline = microtime(true) + $seconds;
    $exitCode = null;
    do {
        $output .= (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
        $state = proc_get_status($process);
        if (!$state['running']) { $exitCode = (int)$state['exitcode']; break; }
        usleep(100000);
    } while (microtime(true) < $deadline);
    $timedOut = $state['running'];
    if ($timedOut) proc_terminate($process);
    $output .= (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit === -1 && $exitCode !== null) $exit = $exitCode;
    return ['ok' => !$timedOut && $exit === 0, 'output' => substr($preserveOutput ? $output : trim($output), 0, $outputLimit), 'timed_out' => $timedOut];
}

/** Review checkouts share the PHP host, so run only non-executing syntax/whitespace checks. */
function jaguar_code_review_check(array $argv, string $cwd): array
{
    $command = strtolower(basename(str_replace('\\', '/', (string)($argv[0] ?? ''))));
    if ($command === 'git' && $argv === ['git', 'diff', '--check']) return jaguar_code_command($argv, $cwd);
    $syntax = ($command === 'php' || $command === 'php.exe') && count($argv) === 3 && $argv[1] === '-l' && str_ends_with(strtolower((string)$argv[2]), '.php');
    $syntax = $syntax || (($command === 'node' || $command === 'node.exe') && count($argv) === 3 && $argv[1] === '--check' && str_ends_with(strtolower((string)$argv[2]), '.js'));
    if ($syntax) {
        $relative = str_replace('\\', '/', (string)$argv[2]);
        if (!preg_match('~^(?!/|[A-Za-z]:|.*(?:^|/)\.\.?/)[A-Za-z0-9_./-]+$~', $relative)) $syntax = false;
        $path = $syntax ? realpath($cwd . DIRECTORY_SEPARATOR . $relative) : false;
        if ($path === false || !str_starts_with($path, rtrim(realpath($cwd) ?: $cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) || !is_file($path)) $syntax = false;
    }
    if ($syntax) return jaguar_code_command($argv, $cwd);
    return ['ok' => false, 'output' => 'Skipped: this check could execute project code on the PHP host. Configure an isolated check runner before enabling it.', 'timed_out' => false];
}

function jaguar_code_repository_root(string $path): ?string
{
    $result = jaguar_code_command(['git', 'rev-parse', '--show-toplevel'], $path, 5);
    return $result['ok'] ? (realpath(trim($result['output'])) ?: null) : null;
}

/** Always read committed blobs: a HEAD citation must never describe working-tree edits. */
function jaguar_code_committed_file(string $root, string $relative, string $revision = 'HEAD', int $maxBytes = 24000): ?string
{
    $maxBytes = min(128000, max(1, $maxBytes));
    $repository = jaguar_code_repository_root($root);
    if ($repository === null) return null;
    $projectPrefix = str_replace('\\', '/', ltrim(substr($root, strlen($repository)), DIRECTORY_SEPARATOR));
    $gitPath = ltrim(($projectPrefix === '' ? '' : rtrim($projectPrefix, '/') . '/') . str_replace('\\', '/', $relative), '/');
    $type = jaguar_code_command(['git', 'ls-tree', $revision, '--', $gitPath], $repository, 5);
    if (!$type['ok'] || !preg_match('/^100(?:644|755) blob [a-f0-9]{40,64}\t/', $type['output'])) return null;
    $size = jaguar_code_command(['git', 'cat-file', '-s', $revision . ':' . $gitPath], $repository, 5);
    if (!$size['ok'] || !ctype_digit(trim($size['output'])) || (int)trim($size['output']) > $maxBytes) return null;
    $content = jaguar_code_command(['git', 'show', $revision . ':' . $gitPath], $repository, 5, $maxBytes + 1, true);
    return $content['ok'] && strlen($content['output']) <= $maxBytes && !str_contains($content['output'], "\0") ? $content['output'] : null;
}

/** Create and remove a detached checkout; no proposed patch touches the configured project. */
function jaguar_code_with_checkout(array $project, string $revision, callable $work): array
{
    $repository = jaguar_code_repository_root($project['path']);
    if ($repository === null) return ['error' => 'The selected Git repository is unavailable.'];
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jaguar-code-' . bin2hex(random_bytes(12));
    $add = jaguar_code_command(['git', 'worktree', 'add', '--detach', $directory, $revision], $repository, 30);
    if (!$add['ok']) return ['error' => 'A bounded review checkout could not be created.'];
    try {
        $suffix = ltrim(substr($project['path'], strlen($repository)), DIRECTORY_SEPARATOR);
        $projectCheckout = $directory . ($suffix === '' ? '' : DIRECTORY_SEPARATOR . $suffix);
        if (!is_dir($projectCheckout)) return ['error' => 'The selected project is absent from the review revision.'];
        return $work($directory, $projectCheckout);
    } finally {
        $cleanup = jaguar_code_command(['git', 'worktree', 'remove', '--force', $directory], $repository, 30);
        if (!$cleanup['ok']) {
            error_log('Jaguar review checkout cleanup failed: ' . $cleanup['output']);
            return ['error' => 'The review checkout could not be removed; an administrator must inspect temporary Jaguar workspaces.'];
        }
    }
}

/** Accept a plain unified Git diff only for source files actually supplied to the model. */
function jaguar_code_patch_diff(string $answer, array $contextFiles): ?string
{
    if (!preg_match('/^diff --git /m', $answer, $match, PREG_OFFSET_CAPTURE)) return null;
    $diff = substr($answer, $match[0][1]);
    $diff = preg_split('/^```/m', $diff, 2)[0];
    if (strlen($diff) > 80000 || str_contains($diff, "\0") || preg_match('/^(?:GIT binary patch|Binary files|old mode |new mode |rename |copy |new file mode |deleted file mode )/m', $diff)) return null;
    preg_match_all('/^diff --git a\/(.+) b\/(.+)$/m', $diff, $matches, PREG_SET_ORDER);
    if ($matches === []) return null;
    $allowed = array_fill_keys(array_map(static fn($path) => str_replace('\\', '/', $path), $contextFiles), true);
    foreach ($matches as $entry) {
        if ($entry[1] !== $entry[2] || !isset($allowed[$entry[1]])) return null;
    }
    if (preg_match_all('/^(?:---|\+\+\+) (.+)$/m', $diff, $headers)) {
        foreach ($headers[1] as $path) {
            if (!preg_match('~^[ab]/(.+)$~', $path, $file) || !isset($allowed[$file[1]])) return null;
        }
    }
    return rtrim($diff) . "\n";
}

function jaguar_code_verify_patch(array $project, string $revision, string $diff, array $contextFiles): array
{
    return jaguar_code_with_checkout($project, $revision, static function (string $checkout, string $projectCheckout) use ($project, $diff, $contextFiles): array {
        $patchFile = tempnam(sys_get_temp_dir(), 'jaguar-patch-');
        if ($patchFile === false) return ['error' => 'Could not stage the review diff.'];
        try {
            file_put_contents($patchFile, $diff);
            $repository = jaguar_code_repository_root($project['path']);
            $prefix = str_replace('\\', '/', ltrim(substr($project['path'], strlen((string)$repository)), DIRECTORY_SEPARATOR));
            $applyOptions = $prefix === '' ? [] : ['--directory=' . $prefix];
            $check = jaguar_code_command(['git', 'apply', '--check', ...$applyOptions, '--', $patchFile], $checkout, 15);
            if (!$check['ok']) return ['error' => 'The proposed diff does not apply cleanly to the cited revision.', 'detail' => $check['output']];
            $apply = jaguar_code_command(['git', 'apply', ...$applyOptions, '--', $patchFile], $checkout, 15);
            if (!$apply['ok']) return ['error' => 'The proposed diff could not be applied in the review checkout.', 'detail' => $apply['output']];
            $changed = jaguar_code_command(['git', 'diff', '--name-only'], $checkout, 10);
            if (!$changed['ok']) return ['error' => 'Could not inspect the patched review checkout.'];
            $paths = array_values(array_filter(explode("\n", $changed['output'])));
            $allowed = array_fill_keys(array_map(static fn($path) => ltrim(($prefix === '' ? '' : $prefix . '/') . str_replace('\\', '/', $path), '/'), $contextFiles), true);
            if ($paths === [] || array_diff($paths, array_keys($allowed))) return ['error' => 'The patch changed files outside the supplied project context.'];
            $actual = jaguar_code_command(['git', 'diff', '--no-ext-diff', '--', ...$paths], $checkout, 10, 80000);
            if (!$actual['ok']) return ['error' => 'Could not capture the patched review diff.'];
            $checks = [];
            foreach (array_slice($project['checks'], 0, 5) as $argv) {
                $result = jaguar_code_review_check($argv, $projectCheckout);
                $checks[] = ['command' => implode(' ', $argv), ...$result];
            }
            $whitespace = jaguar_code_command(['git', 'diff', '--check'], $checkout, 10);
            $checks[] = ['command' => 'git diff --check', ...$whitespace];
            $afterChecks = jaguar_code_command(['git', 'diff', '--no-ext-diff', '--', ...$paths], $checkout, 10, 80000);
            $unchanged = $afterChecks['ok'] && $afterChecks['output'] === $actual['output'];
            if (!$unchanged) $checks[] = ['command' => 'checks preserved the proposed diff', 'ok' => false, 'output' => 'A configured check changed the patch in the review checkout.', 'timed_out' => false];
            return ['diff' => $actual['output'], 'affected_files' => $paths, 'checks' => $checks, 'verified' => !in_array(false, array_column($checks, 'ok'), true)];
        } finally {
            @unlink($patchFile);
        }
    });
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

function jaguar_code_read_context(array $project, array $requestedFiles, string $revision = 'HEAD'): array
{
    $root = $project['path'];
    $files = [];
    foreach (['AGENTS.md', 'README.md'] as $relative) {
        if (jaguar_code_committed_file($root, $relative, $revision) !== null) $files[$relative] = null;
    }
    foreach (array_slice($requestedFiles, 0, 10) as $specification) {
        if (!is_string($specification) || strlen($specification) > 240 || str_contains($specification, "\0")) continue;
        $range = null;
        if (preg_match('/^(.+):([1-9][0-9]{0,5})-([1-9][0-9]{0,5})$/', $specification, $match)) {
            $range = [(int)$match[2], (int)$match[3]];
            if ($range[1] < $range[0] || $range[1] - $range[0] > 249) continue;
            $specification = $match[1];
        }
        $relative = $specification;
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if ($relative === '' || str_starts_with($relative, DIRECTORY_SEPARATOR)) continue;
        $segments = array_map('strtolower', explode(DIRECTORY_SEPARATOR, $relative));
        if (in_array('..', $segments, true)) continue;
        $baseName = end($segments) ?: '';
        if (array_intersect($segments, ['.git', '.ssh', '.aws', '.config', '.secrets', 'private', 'var', 'storage', 'uploads', 'secrets', '.codex'])
            || $baseName === '.env' || str_starts_with($baseName, '.env.') || $baseName === 'live.php'
            || preg_match('/\.(pem|key|p12|pfx|kdbx)$/i', $baseName)
            || preg_match('/(secret|credential|private[-_]?key)/i', $baseName)) continue;
        $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if ($path === false || !str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) || !is_file($path)) continue;
        $files[$relative] = $range;
    }
    $context = [];
    foreach ($files as $relative => $range) {
        $content = jaguar_code_committed_file($root, $relative, $revision, $range === null ? 24000 : 128000);
        if ($content === null) continue;
        $displayPath = str_replace('\\', '/', $relative);
        if ($range !== null) {
            $lines = preg_split('/(?<=\n)/', $content);
            if (is_array($lines) && end($lines) === '') array_pop($lines);
            if (!is_array($lines) || $range[0] > count($lines) || $range[1] > count($lines)) continue;
            $excerpt = implode('', array_slice($lines, $range[0] - 1, $range[1] - $range[0] + 1));
            if (strlen($excerpt) > 16000) continue;
            $context[] = "--- PROJECT FILE: {$displayPath} ---\nCommitted lines {$range[0]}-{$range[1]} of " . count($lines) . "; other lines were not supplied.\n" . $excerpt;
        } elseif (in_array($relative, ['AGENTS.md', 'README.md'], true)) {
            $excerpt = substr($content, 0, 4000);
            $context[] = "--- PROJECT FILE: {$displayPath} ---\n" . (strlen($content) > 4000 ? "First 4,000 bytes only; remaining instructions were not supplied.\n" : '') . $excerpt;
        } elseif (strlen($content) <= 16000) {
            $context[] = "--- PROJECT FILE: {$displayPath} ---\n" . $content;
        }
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
