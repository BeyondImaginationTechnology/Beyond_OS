<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/lib/deployment.php';

$paths = beyond_deployment_paths();
if (!beyond_deployment_ensure_directory()) { fwrite(STDERR, "Deployment directory is not writable.\n"); exit(1); }
$lock = fopen($paths['lock'], 'c+');
if (!is_resource($lock) || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDOUT, "Another deployment worker is active.\n"); exit(0); }
register_shutdown_function(static function () use ($lock): void { if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); } });
if (!is_file($paths['queue'])) { fwrite(STDOUT, "No deployment is queued.\n"); exit(0); }
if (!@rename($paths['queue'], $paths['processing'])) { fwrite(STDERR, "Could not claim the queued deployment.\n"); exit(1); }

$request = beyond_deployment_read_json($paths['processing']) ?? [];
$requestId = preg_replace('/[^a-f0-9]/', '', (string)($request['id'] ?? '')) ?: bin2hex(random_bytes(8));
$projectRoot = dirname(__DIR__, 2);
$script = $projectRoot . '/tools/deploy-production.sh';
$startedAt = gmdate(DATE_ATOM);
$status = $request + ['branch' => 'main', 'commit' => '', 'requested_at' => ''];
$status += ['result' => '', 'message' => '', 'started_at' => '', 'finished_at' => ''];
$status['result'] = 'running'; $status['message'] = 'Updating the repository and deploying the current main branch.';
$status['started_at'] = $startedAt; $status['finished_at'] = '';
beyond_deployment_write_json($paths['status'], $status);

$stdout = ''; $stderr = ''; $exitCode = 1;
try {
    if (!is_file($script)) throw new RuntimeException('Deployment script is missing.');
    $process = proc_open(['/usr/bin/env', 'bash', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $projectRoot);
    if (!is_resource($process)) throw new RuntimeException('The deployment process could not be started.');
    $stdout = stream_get_contents($pipes[1]) ?: ''; $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]); fclose($pipes[2]); $exitCode = proc_close($process);
} catch (Throwable $exception) { $stderr .= ($stderr === '' ? '' : "\n") . $exception->getMessage(); }

$git = beyond_git_state($projectRoot); $finishedAt = gmdate(DATE_ATOM); $success = $exitCode === 0;
$status['result'] = $success ? 'success' : 'failed';
$status['message'] = $success ? 'Deployment completed successfully.' : 'Deployment failed. Review the protected deployment log.';
$status['branch'] = $git['branch'] ?: 'main'; $status['commit'] = $git['commit'];
$status['finished_at'] = $finishedAt; $status['exit_code'] = $exitCode;
beyond_deployment_write_json($paths['status'], $status);
$log = [
    'request' => $request, 'result' => $status['result'], 'branch' => $status['branch'], 'commit' => $status['commit'],
    'started_at' => $startedAt, 'finished_at' => $finishedAt, 'exit_code' => $exitCode,
    'stdout' => substr($stdout, -20000), 'stderr' => substr($stderr, -20000),
];
beyond_deployment_write_json($paths['history'] . DIRECTORY_SEPARATOR . $finishedAt . '-' . $requestId . '.json', $log);
@unlink($paths['processing']);
fwrite($success ? STDOUT : STDERR, $status['message'] . "\n");
exit($success ? 0 : 1);
