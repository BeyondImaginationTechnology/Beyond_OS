<?php
declare(strict_types=1);

// Run once from the hosting CLI after a current webspace snapshot completes.
// The public web server must never be able to invoke this migration.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/config/bootstrap.php';

if (($argv[1] ?? '') !== '--apply') {
    fwrite(STDERR, "Usage: php tools/migrate-private-var.php --apply\n");
    exit(2);
}

$root = beyond_private_root();
$lockPath = $root . '/tmp/var-layout.lock';
$lock = fopen($lockPath, 'c');
if ($lock === false) {
    throw new RuntimeException('Cannot open the private storage migration lock.');
}

$deadline = microtime(true) + 120;
while (!flock($lock, LOCK_EX | LOCK_NB)) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Active requests did not release private storage in time.');
    }
    usleep(250000);
}

$databases = [
    'beyond-os.sqlite' => 'db/beyond-os.sqlite',
    'daily-studio.sqlite' => 'db/daily-studio.sqlite',
    'learning-academy.sqlite' => 'db/learning-academy.sqlite',
    'data/beyond.sqlite' => 'db/beyond-french.sqlite',
    'beyond-baby-names/couples.sqlite' => 'db/beyond-baby-names.sqlite',
];
$files = [
    'data/africa-expansion.json' => 'data/daily-studio/africa-expansion.json',
    'data/beyond-tattoo-stencil-day.json' => 'data/beyond-tattoo/stencil-day.json',
    'tv/channel-1-library-state.json' => 'data/beyond-tv/channel-1-library-state.json',
];
$directories = [
    'daily-studio-published' => 'data/daily-studio/published',
];

try {
    foreach ($databases as $old => $new) {
        if (!is_file($root . '/' . $old) && !is_file($root . '/' . $new)) {
            throw new RuntimeException("Missing database: {$old}");
        }
    }
    foreach ([$databases, $files] as $moves) {
        foreach ($moves as $old => $new) {
            if (file_exists($root . '/' . $old) && file_exists($root . '/' . $new)) {
                throw new RuntimeException("Both storage paths exist: {$old} and {$new}");
            }
        }
    }
    foreach ($directories as $old => $new) {
        if (is_dir($root . '/' . $old) && is_dir($root . '/' . $new)) {
            throw new RuntimeException("Both storage directories exist: {$old} and {$new}");
        }
    }

    foreach ($databases as $old => $new) {
        $source = $root . '/' . $old;
        $target = $root . '/' . $new;
        if (!is_file($source)) {
            continue;
        }
        $db = new PDO('sqlite:' . $source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA busy_timeout=5000');
        $checkpoint = $db->query('PRAGMA wal_checkpoint(TRUNCATE)')->fetch(PDO::FETCH_NUM);
        if ($checkpoint !== false && (int)$checkpoint[0] !== 0) {
            throw new RuntimeException("SQLite checkpoint is busy: {$old}");
        }
        $db = null;
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create target directory for {$new}");
        }
        if (!rename($source, $target)) {
            throw new RuntimeException("Cannot move database {$old}");
        }
        foreach (['-wal', '-shm'] as $suffix) {
            if (is_file($source . $suffix) && !rename($source . $suffix, $target . $suffix)) {
                throw new RuntimeException("Cannot move SQLite sidecar for {$old}");
            }
        }
        echo "Moved {$old} to {$new}\n";
    }

    foreach ($files as $old => $new) {
        $source = $root . '/' . $old;
        $target = $root . '/' . $new;
        if (!is_file($source)) {
            continue;
        }
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create target directory for {$new}");
        }
        if (!rename($source, $target)) {
            throw new RuntimeException("Cannot move private file {$old}");
        }
        echo "Moved {$old} to {$new}\n";
    }

    foreach ($directories as $old => $new) {
        $source = $root . '/' . $old;
        $target = $root . '/' . $new;
        if (!is_dir($source)) {
            continue;
        }
        $directory = dirname($target);
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create target directory for {$new}");
        }
        if (!rename($source, $target)) {
            throw new RuntimeException("Cannot move private directory {$old}");
        }
        echo "Moved {$old} to {$new}\n";
    }
    echo "Private storage migration complete.\n";
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
