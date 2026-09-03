<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

function beyond_deployment_directory(): string
{
    return beyond_private_root() . DIRECTORY_SEPARATOR . 'deployments';
}

function beyond_deployment_paths(): array
{
    $directory = beyond_deployment_directory();
    return [
        'directory' => $directory,
        'queue' => $directory . DIRECTORY_SEPARATOR . 'queue.json',
        'processing' => $directory . DIRECTORY_SEPARATOR . 'processing.json',
        'status' => $directory . DIRECTORY_SEPARATOR . 'status.json',
        'lock' => $directory . DIRECTORY_SEPARATOR . 'deploy.lock',
        'history' => $directory . DIRECTORY_SEPARATOR . 'history',
    ];
}

function beyond_deployment_ensure_directory(): bool
{
    $paths = beyond_deployment_paths();
    if (!is_dir($paths['directory']) && !@mkdir($paths['directory'], 0700, true) && !is_dir($paths['directory'])) return false;
    if (!is_dir($paths['history']) && !@mkdir($paths['history'], 0700, true) && !is_dir($paths['history'])) return false;
    @chmod($paths['directory'], 0700);
    @chmod($paths['history'], 0700);
    return is_writable($paths['directory']);
}

function beyond_deployment_read_json(string $path): ?array
{
    if (!is_file($path)) return null;
    $contents = @file_get_contents($path);
    if (!is_string($contents) || $contents === '') return null;
    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : null;
}

function beyond_deployment_write_json(string $path, array $payload): bool
{
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) return false;
    $temporary = @tempnam($directory, '.deploy-');
    if (!is_string($temporary)) return false;
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($encoded)) { @unlink($temporary); return false; }
    $json = $encoded . "\n";
    if (@file_put_contents($temporary, $json, LOCK_EX) === false) { @unlink($temporary); return false; }
    @chmod($temporary, 0600);
    if (!@rename($temporary, $path)) { @unlink($temporary); return false; }
    @chmod($path, 0600);
    return true;
}

function beyond_deployment_status(): array
{
    $paths = beyond_deployment_paths();
    return beyond_deployment_read_json($paths['status']) ?? [
        'result' => 'never', 'message' => 'No deployment has run through the admin queue yet.',
        'branch' => '', 'commit' => '', 'requested_at' => '', 'started_at' => '', 'finished_at' => '',
    ];
}

function beyond_git_state(string $repository): array
{
    $gitPath = rtrim($repository, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.git';
    if (is_file($gitPath)) {
        $pointer = trim((string)@file_get_contents($gitPath));
        if (str_starts_with($pointer, 'gitdir:')) {
            $candidate = trim(substr($pointer, 7));
            $gitPath = str_starts_with($candidate, DIRECTORY_SEPARATOR) ? $candidate : dirname($gitPath) . DIRECTORY_SEPARATOR . $candidate;
        }
    }
    $head = trim((string)@file_get_contents($gitPath . DIRECTORY_SEPARATOR . 'HEAD'));
    if ($head === '') return ['branch' => '', 'commit' => ''];
    if (!str_starts_with($head, 'ref: ')) return ['branch' => 'detached', 'commit' => $head];
    $reference = trim(substr($head, 5));
    $branch = basename($reference);
    $commit = trim((string)@file_get_contents($gitPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $reference)));
    if ($commit === '') {
        $packed = @file($gitPath . DIRECTORY_SEPARATOR . 'packed-refs', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($packed as $line) {
            if ($line[0] === '#' || $line[0] === '^') continue;
            [$hash, $ref] = array_pad(preg_split('/\s+/', trim($line), 2), 2, '');
            if ($ref === $reference) { $commit = $hash; break; }
        }
    }
    return ['branch' => $branch, 'commit' => $commit];
}

function beyond_queue_deployment(array $requester): array
{
    if (!beyond_deployment_ensure_directory()) return ['ok' => false, 'message' => 'The private deployment queue is not writable.'];
    $paths = beyond_deployment_paths();
    $lock = @fopen($paths['lock'], 'c+');
    if (!is_resource($lock) || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) fclose($lock);
        return ['ok' => false, 'message' => 'A deployment is already running.'];
    }
    $status = beyond_deployment_status();
    if (is_file($paths['queue']) || is_file($paths['processing']) || in_array($status['result'] ?? '', ['queued', 'running'], true)) {
        flock($lock, LOCK_UN); fclose($lock);
        return ['ok' => false, 'message' => 'A deployment is already queued or running.'];
    }
    $request = [
        'id' => bin2hex(random_bytes(12)), 'branch' => 'main', 'requested_at' => gmdate(DATE_ATOM),
        'requested_by_id' => (int)($requester['id'] ?? 0), 'requested_by' => (string)($requester['email'] ?? 'admin'),
    ];
    if (!beyond_deployment_write_json($paths['queue'], $request)) {
        flock($lock, LOCK_UN); fclose($lock);
        return ['ok' => false, 'message' => 'The deployment request could not be queued.'];
    }
    beyond_deployment_write_json($paths['status'], $request + [
        'result' => 'queued', 'message' => 'Waiting for the deployment cron worker.',
        'commit' => '', 'started_at' => '', 'finished_at' => '',
    ]);
    flock($lock, LOCK_UN); fclose($lock);
    return ['ok' => true, 'message' => 'Deployment queued. The cron worker will start it shortly.'];
}

function beyond_deployment_public_status(array $status): array
{
    $public = [];
    foreach (['result', 'message', 'branch', 'commit', 'requested_at', 'started_at', 'finished_at'] as $key) $public[$key] = (string)($status[$key] ?? '');
    return $public;
}
