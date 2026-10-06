<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/services/LessonRepository.php';
require_once __DIR__ . '/services/ProgressTracker.php';
require_once __DIR__ . '/services/AcademyRepository.php';
require_once __DIR__ . '/services/QuizEngine.php';

// --- Core Utility Functions ---
function read_json(string $file, array $fallback = []): array {
    if (!is_file($file)) return $fallback;
    $raw = file_get_contents($file);
    if ($raw === false || trim($raw) === '') return $fallback;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_admin(): bool {
    if (!empty($_SESSION['admin_authenticated'])) return true;
    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    return !empty($_SESSION['user_id']) && in_array($role, ['admin', 'super_admin'], true);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

function french_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function french_verify_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    }
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    return $sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token);
}

function sqlite_db(): PDO {
    static $pdo = null; if ($pdo instanceof PDO) return $pdo;
    if (!is_dir(PRIVATE_DATA_DIR)) mkdir(PRIVATE_DATA_DIR, 0700, true);
    $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS french_subscribers (id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE COLLATE NOCASE, preferred_language TEXT NOT NULL, consent_at TEXT NOT NULL, created_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (ip TEXT PRIMARY KEY, attempts INTEGER NOT NULL DEFAULT 0, blocked_until INTEGER NOT NULL DEFAULT 0, updated_at INTEGER NOT NULL)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_lesson_audio (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lesson_id INTEGER NOT NULL,
        provider TEXT NOT NULL,
        voice TEXT NOT NULL,
        language TEXT NOT NULL,
        format TEXT NOT NULL DEFAULT 'mp3',
        audio_path TEXT NOT NULL DEFAULT '',
        content_hash TEXT NOT NULL,
        generation_status TEXT NOT NULL DEFAULT 'processing' CHECK(generation_status IN ('processing','ready','failed')),
        error_code TEXT NULL,
        created_by INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(lesson_id, content_hash)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_audio_lesson ON french_lesson_audio(lesson_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_audio_status ON french_lesson_audio(generation_status)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_learning_progress (user_id INTEGER PRIMARY KEY,current_lesson_id INTEGER NOT NULL DEFAULT 1,last_lesson_id INTEGER NOT NULL DEFAULT 0,completed_lessons_json TEXT NOT NULL DEFAULT '[]',updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_native_progress (
        user_id INTEGER PRIMARY KEY,
        completed_lesson_ids_json TEXT NOT NULL DEFAULT '[]',
        completed_daily_lesson_ids_json TEXT NOT NULL DEFAULT '[]',
        correct_practice_count INTEGER NOT NULL DEFAULT 0,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_progress (
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        best_score INTEGER NOT NULL DEFAULT 0,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        completed_at TEXT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(learner_key,age_group,module_slug,lesson_number)
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_test_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        score INTEGER NOT NULL,
        question_count INTEGER NOT NULL DEFAULT 10,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_exam_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        score INTEGER NOT NULL,
        question_count INTEGER NOT NULL DEFAULT 10,
        passed INTEGER NOT NULL DEFAULT 0 CHECK(passed IN (0,1)),
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_academy_practice (
        learner_key TEXT NOT NULL,
        age_group TEXT NOT NULL,
        module_slug TEXT NOT NULL,
        lesson_number INTEGER NOT NULL,
        round_number INTEGER NOT NULL,
        response TEXT NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(learner_key,age_group,module_slug,lesson_number,round_number)
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_academy_test_learner ON french_academy_test_attempts(learner_key,age_group,module_slug,lesson_number)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_french_academy_exam_learner ON french_academy_exam_attempts(learner_key,age_group,module_slug)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS french_narration_rate_limits (
        admin_id INTEGER NOT NULL,
        action TEXT NOT NULL,
        window_started_at INTEGER NOT NULL,
        request_count INTEGER NOT NULL DEFAULT 0,
        PRIMARY KEY(admin_id, action)
    )");
    return $pdo;
}

function login_blocked(string $ip): bool {
    $s = sqlite_db()->prepare('SELECT blocked_until FROM login_attempts WHERE ip=?');
    $s->execute([$ip]);
    $r = $s->fetch();
    return $r && (int)$r['blocked_until'] > time();
}

function record_login_failure(string $ip): void {
    $pdo = sqlite_db();
    $s = $pdo->prepare('SELECT attempts FROM login_attempts WHERE ip=?');
    $s->execute([$ip]);
    $attempts = ((int)($s->fetch()['attempts'] ?? 0)) + 1;
    $blocked = $attempts >= 5 ? time() + 900 : 0;
    $q = $pdo->prepare('INSERT INTO login_attempts(ip,attempts,blocked_until,updated_at) VALUES(?,?,?,?) ON CONFLICT(ip) DO UPDATE SET attempts=excluded.attempts,blocked_until=excluded.blocked_until,updated_at=excluded.updated_at');
    $q->execute([$ip, $attempts, $blocked, time()]);
}

function clear_login_failures(string $ip): void {
    $s = sqlite_db()->prepare('DELETE FROM login_attempts WHERE ip=?');
    $s->execute([$ip]);
}

// --- Lesson Repository Delegates ---
function all_lessons(): array { return LessonRepository::allLessons(); }
function lesson_by_id(int $id): ?array { return LessonRepository::lessonById($id); }
function todays_lesson(): ?array { return LessonRepository::todaysLesson(); }
function french_modules(): array { return LessonRepository::frenchModules(); }
function lesson_module(array $lesson): string { return LessonRepository::lessonModule($lesson); }
function ordered_lessons(): array { return LessonRepository::orderedLessons(); }
function lesson_position(int $id): array { return LessonRepository::lessonPosition($id); }
function lesson_is_today(?array $lesson): bool { return LessonRepository::lessonIsToday($lesson); }
function lesson_audio_map(int $lessonId): array { return LessonRepository::lessonAudioMap($lessonId); }

// --- Progress Tracker Delegates ---
function french_progress(int $userId): array { return ProgressTracker::getProgress($userId); }
function french_continue_lesson(int $userId): ?array { return ProgressTracker::continueLesson($userId); }
function french_mark_started(int $userId, int $lessonId): void { ProgressTracker::markStarted($userId, $lessonId); }
function french_mark_completed(int $userId, int $lessonId): ?array { return ProgressTracker::markCompleted($userId, $lessonId); }

// --- Academy Repository Delegates ---
function french_academy_catalog(): array { return AcademyRepository::catalog(); }
function french_age_groups(): array { return AcademyRepository::ageGroups(); }
function french_academy_modules(): array { return AcademyRepository::modules(); }
function french_preschool_greetings_lessons(): array { return AcademyRepository::preschoolGreetingsLessons(); }
function french_course_lessons(string $age, string $module): array { return AcademyRepository::courseLessons($age, $module); }
function french_valid_age_group(string $age): string { return AcademyRepository::validAgeGroup($age); }
function french_valid_module(string $module): string { return AcademyRepository::validModule($module); }
function french_course_lesson(string $age, string $module, int $lessonNumber): ?array { return AcademyRepository::courseLesson($age, $module, $lessonNumber); }
function french_learner_key(): string { return AcademyRepository::learnerKey(); }
function french_academy_has_full_access(): bool { return AcademyRepository::hasFullAccess(); }
function french_is_guest(): bool { return AcademyRepository::isGuest(); }
function french_guest_daily_lesson_allowed(?array $lesson): bool { return AcademyRepository::guestDailyLessonAllowed($lesson); }
function french_academy_module_accessible(string $module): bool { return AcademyRepository::moduleAccessible($module); }
function french_academy_lesson_passed(string $age, string $module, int $lesson): bool { return AcademyRepository::lessonPassed($age, $module, $lesson); }
function french_academy_exam_passed(string $age, string $module): bool { return AcademyRepository::examPassed($age, $module); }
function french_academy_lesson_unlocked(string $age, string $module, int $lesson): bool { return AcademyRepository::lessonUnlocked($age, $module, $lesson); }
function french_academy_module_progress(string $age, string $module): array { return AcademyRepository::moduleProgress($age, $module); }
function french_academy_practice_responses(string $age, string $module, int $lesson): array { return AcademyRepository::practiceResponses($age, $module, $lesson); }
function french_record_academy_practice(string $age, string $module, int $lesson, int $round, string $response): bool { return AcademyRepository::recordPractice($age, $module, $lesson, $round, $response); }
function french_record_lesson_test(string $age, string $module, int $lesson, int $score, int $questionCount = 10): bool { return AcademyRepository::recordLessonTest($age, $module, $lesson, $score, $questionCount); }
function french_record_module_exam(string $age, string $module, int $score, int $questionCount = 10): bool { return AcademyRepository::recordModuleExam($age, $module, $score, $questionCount); }

// --- Quiz Engine Delegates ---
function french_quiz_options(string $correct, array $pool, int $seed): array { return QuizEngine::quizOptions($correct, $pool, $seed); }
function french_phrase_word(string $phrase, bool $last = false): string { return QuizEngine::phraseWord($phrase, $last); }
function french_lesson_test_questions(string $age, string $module, int $lessonNumber): array { return QuizEngine::lessonTestQuestions($age, $module, $lessonNumber); }
function french_module_exam_questions(string $age, string $module): array { return QuizEngine::moduleExamQuestions($age, $module); }
function french_score_quiz(array $questions, array $answers): int { return QuizEngine::scoreQuiz($questions, $answers); }
