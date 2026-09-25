<?php
declare(strict_types=1);

/**
 * Beyond Studio authentication bridge.
 *
 * Studio is an internal Beyond ID admin module. It intentionally uses the
 * same BEYOND_ID cookie, user_id and role values as /beyond-id/admin/ and
 * does not perform a second database login or user lookup.
 */
require_once dirname(__DIR__, 3) . '/beyond-id/includes/admin-check.php';
require_once dirname(__DIR__, 3) . '/beyond-id/includes/functions.php';

if (!class_exists('Auth', false)) {
    final class Auth
    {
        public static function check(): bool
        {
            return !empty($_SESSION['user_id'])
                && in_array(strtolower((string)($_SESSION['role'] ?? '')), ['admin', 'super_admin'], true);
        }

        public static function user(): ?array
        {
            if (!self::check()) {
                return null;
            }

            $name = trim((string)($_SESSION['name'] ?? ''));
            if ($name === '') {
                $name = trim((string)(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')));
            }

            return [
                'id' => (int)$_SESSION['user_id'],
                'name' => $name !== '' ? $name : 'Administrator',
                'email' => (string)($_SESSION['email'] ?? ($_SESSION['user']['email'] ?? '')),
                'role' => (string)($_SESSION['role'] ?? 'admin'),
                'status' => 'active',
                'is_admin' => true,
            ];
        }

        public static function csrf(): string
        {
            return csrf_token();
        }

        public static function verifyCsrf(?string $token): bool
        {
            return verify_csrf_token($token);
        }
    }
}

final class DailyStudio
{
    public static function db(): PDO
    {
        static $pdo;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $path = beyond_private_file('db/daily-studio.sqlite', 'daily-studio.sqlite');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        self::migrate($pdo);
        return $pdo;
    }

    private static function migrate(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS channels (id INTEGER PRIMARY KEY AUTOINCREMENT, channel_key TEXT UNIQUE NOT NULL, name TEXT NOT NULL, icon TEXT NOT NULL DEFAULT '✨', enabled INTEGER NOT NULL DEFAULT 1);");
        $db->exec("CREATE TABLE IF NOT EXISTS events (id INTEGER PRIMARY KEY AUTOINCREMENT, channel_key TEXT NOT NULL, title TEXT NOT NULL, content_type TEXT NOT NULL DEFAULT 'daily', content_json TEXT NOT NULL DEFAULT '{}', scheduled_at TEXT NOT NULL, ends_at TEXT, timezone TEXT NOT NULL DEFAULT 'America/Vancouver', recurrence_rule TEXT, status TEXT NOT NULL DEFAULT 'draft', requires_approval INTEGER NOT NULL DEFAULT 1, approved_by INTEGER, approved_at TEXT, published_at TEXT, attempts INTEGER NOT NULL DEFAULT 0, last_error TEXT, created_by INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_events_schedule ON events(status, scheduled_at);");
        $db->exec("CREATE TABLE IF NOT EXISTS publish_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id INTEGER NOT NULL, status TEXT NOT NULL, detail TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);");
        $db->exec("CREATE TABLE IF NOT EXISTS jaguar_training_examples (id INTEGER PRIMARY KEY AUTOINCREMENT, instruction TEXT NOT NULL, input_text TEXT NOT NULL DEFAULT '', output_text TEXT NOT NULL, example_type TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'draft', created_by INTEGER NOT NULL DEFAULT 0, reviewed_by INTEGER, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_jaguar_training_status ON jaguar_training_examples(status, updated_at);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_jaguar_training_type ON jaguar_training_examples(example_type);");

        $count = (int)$db->query('SELECT COUNT(*) FROM channels')->fetchColumn();
        if (!$count) {
            $rows = [
                ['daily_breath', 'Daily Breath', '🙏'],
                ['daily_french', 'Beyond French', '🇫🇷'],
                ['daily_tattoo', 'Beyond Tattoo', '✒️'],
                ['daily_ancient', 'Beyond Ancient', '𓂀'],
                ['daily_tv', 'Beyond TV', '📺'],
                ['daily_health', 'Beyond Health', '❤️'],
                ['daily_space', 'Beyond Space', '🪐'],
                ['daily_math', 'Beyond Math', '➗'],
            ];
            $statement = $db->prepare('INSERT INTO channels(channel_key,name,icon) VALUES(?,?,?)');
            foreach ($rows as $row) {
                $statement->execute($row);
            }
        }
    }

    public static function esc(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
