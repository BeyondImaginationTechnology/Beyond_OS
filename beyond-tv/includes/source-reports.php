<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

function beyond_tv_source_reports_db(): PDO
{
    $path = beyond_private_file('db/beyond-tv-source-reports.sqlite', 'beyond-tv-source-reports.sqlite');
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Source report storage is unavailable.');
    }
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE TABLE IF NOT EXISTS source_reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at TEXT NOT NULL,
        category TEXT NOT NULL,
        channel_slug TEXT NOT NULL,
        title TEXT NOT NULL,
        source_url TEXT NOT NULL,
        page_url TEXT NOT NULL,
        details TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT \'new\',
        review_note TEXT NOT NULL DEFAULT \'\',
        reviewed_at TEXT
    )');
    return $db;
}
