<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function academy_courses(): array
{
    return [
        'essential-math' => [
            'title' => 'Essential Math',
            'short' => 'Math',
            'icon' => '∑',
            'accent' => '#51db78',
            'description' => 'Build confidence with numbers, fractions, percentages, measurement, and everyday problem solving.',
            'skills' => ['Numeracy', 'Fractions and percentages', 'Measurement', 'Applied problem solving'],
            'lessons' => [
                ['Numbers in daily life', 'Read, compare, round, and estimate numbers used in prices, schedules, quantities, and measurements.'],
                ['Operations with confidence', 'Choose and apply addition, subtraction, multiplication, and division to solve practical problems.'],
                ['Fractions, decimals, and percentages', 'Convert between common forms and use them for discounts, portions, and rates.'],
                ['Measurement and geometry', 'Work with length, area, perimeter, volume, time, and unit conversions.'],
                ['Applied problem solving', 'Break multi-step situations into known facts, a strategy, a calculation, and a reasonableness check.'],
            ],
            'questions' => [
                ['q' => 'What is 25% of 80?', 'options' => ['10', '20', '25', '40'], 'answer' => '20'],
                ['q' => 'Which decimal equals 3/4?', 'options' => ['0.25', '0.5', '0.75', '1.25'], 'answer' => '0.75'],
                ['q' => 'A $60 item is discounted by 10%. What is the sale price?', 'options' => ['$50', '$54', '$56', '$59'], 'answer' => '$54'],
                ['q' => 'What is the perimeter of a rectangle measuring 5 by 3?', 'options' => ['8', '15', '16', '30'], 'answer' => '16'],
                ['q' => 'Which is the best first step in a word problem?', 'options' => ['Guess', 'Identify known facts and the question', 'Round every number', 'Multiply everything'], 'answer' => 'Identify known facts and the question'],
            ],
        ],
        'web-development-foundations' => [
            'title' => 'Web Development Foundations',
            'short' => 'Coding',
            'icon' => '</>',
            'accent' => '#448cff',
            'description' => 'Create accessible web pages with HTML, CSS, JavaScript, responsive layouts, and safe development habits.',
            'skills' => ['Semantic HTML', 'Responsive CSS', 'JavaScript fundamentals', 'Accessibility'],
            'lessons' => [
                ['How the web works', 'Understand browsers, servers, URLs, requests, files, and the roles of HTML, CSS, and JavaScript.'],
                ['Semantic HTML', 'Structure pages with meaningful headings, landmarks, links, buttons, forms, and accessible labels.'],
                ['CSS and responsive layout', 'Style content using the cascade, flexible sizing, Grid, Flexbox, and mobile-first breakpoints.'],
                ['JavaScript fundamentals', 'Use variables, functions, events, conditions, and DOM updates to create safe interactions.'],
                ['Build and test a web page', 'Combine structure, presentation, and behavior; then test keyboard access and small screens.'],
            ],
            'questions' => [
                ['q' => 'Which language gives a web page its semantic structure?', 'options' => ['HTML', 'CSS', 'SQL', 'PNG'], 'answer' => 'HTML'],
                ['q' => 'Which element should trigger an action on the current page?', 'options' => ['<div>', '<span>', '<button>', '<title>'], 'answer' => '<button>'],
                ['q' => 'What does a responsive layout do?', 'options' => ['Only works on phones', 'Adapts to available screen space', 'Removes all images', 'Requires an app store'], 'answer' => 'Adapts to available screen space'],
                ['q' => 'Where should sensitive secrets be stored?', 'options' => ['Public JavaScript', 'HTML comments', 'Server-side protected configuration', 'A CSS file'], 'answer' => 'Server-side protected configuration'],
                ['q' => 'What is an important accessibility test?', 'options' => ['Keyboard-only navigation', 'Maximum animation speed', 'Tiny text', 'Removing labels'], 'answer' => 'Keyboard-only navigation'],
            ],
        ],
        'personal-finance-foundations' => [
            'title' => 'Personal Finance Foundations',
            'short' => 'Finance',
            'icon' => '$',
            'accent' => '#ffbf32',
            'description' => 'Learn practical budgeting, saving, credit, interest, and fraud-awareness skills for everyday decisions.',
            'skills' => ['Budgeting', 'Saving', 'Credit fundamentals', 'Fraud awareness'],
            'lessons' => [
                ['Income, needs, and wants', 'Separate take-home income, required expenses, flexible needs, wants, and financial goals.'],
                ['Build a realistic budget', 'Create a monthly plan, include irregular costs, and adjust when actual spending differs.'],
                ['Saving and emergency funds', 'Set specific goals, automate contributions, and understand how a buffer reduces financial risk.'],
                ['Credit and interest', 'Understand balances, minimum payments, interest, credit reports, and the cost of borrowing.'],
                ['Protect your money', 'Recognize pressure tactics, phishing, identity theft, and steps to verify a financial request.'],
            ],
            'questions' => [
                ['q' => 'What should a useful budget compare?', 'options' => ['Income and planned expenses', 'Likes and followers', 'Passwords and PINs', 'Only cash purchases'], 'answer' => 'Income and planned expenses'],
                ['q' => 'What is the purpose of an emergency fund?', 'options' => ['Cover unexpected essential costs', 'Increase impulse spending', 'Replace all insurance', 'Avoid reviewing bills'], 'answer' => 'Cover unexpected essential costs'],
                ['q' => 'Paying only a credit-card minimum generally does what?', 'options' => ['Eliminates interest', 'Can extend repayment and increase interest', 'Cancels the balance', 'Improves every credit score immediately'], 'answer' => 'Can extend repayment and increase interest'],
                ['q' => 'A message pressures you to share a verification code. What should you do?', 'options' => ['Share it quickly', 'Verify through the organization’s official channel', 'Post it publicly', 'Forward it to friends'], 'answer' => 'Verify through the organization’s official channel'],
                ['q' => 'Which savings goal is most actionable?', 'options' => ['Save more someday', 'Save $25 each payday for six months', 'Never spend money', 'Copy someone else’s budget'], 'answer' => 'Save $25 each payday for six months'],
            ],
        ],
    ];
}

function academy_course(string $slug): ?array
{
    $courses = academy_courses();
    return $courses[$slug] ?? null;
}

function academy_user_id(): int
{
    return max(0, (int)($_SESSION['user_id'] ?? 0));
}

function academy_require_user(): int
{
    $userId = academy_user_id();
    if ($userId < 1) {
        $_SESSION['beyond_return_to'] = $_SERVER['REQUEST_URI'] ?? '/academy/dashboard.php';
        header('Location: /beyond-id/auth/login.php?required=1');
        exit;
    }
    return $userId;
}

function academy_csrf(): string
{
    if (empty($_SESSION['academy_certificate_csrf'])) {
        $_SESSION['academy_certificate_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['academy_certificate_csrf'];
}

function academy_verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(academy_csrf(), $token);
}

function academy_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $root = beyond_private_root();
    if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
        throw new RuntimeException('Academy storage is unavailable.');
    }
    $pdo = new PDO('sqlite:' . $root . '/learning-academy.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_course_progress (
        user_id INTEGER NOT NULL, course_slug TEXT NOT NULL, lesson_number INTEGER NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, course_slug, lesson_number)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_assessment_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, course_slug TEXT NOT NULL,
        score INTEGER NOT NULL, question_count INTEGER NOT NULL, passed INTEGER NOT NULL DEFAULT 0,
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_academy_attempt_user ON academy_assessment_attempts(user_id, course_slug, passed)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_credentials (
        id INTEGER PRIMARY KEY AUTOINCREMENT, credential_id TEXT NOT NULL UNIQUE, user_id INTEGER NOT NULL,
        course_slug TEXT NOT NULL, learner_name TEXT NOT NULL, score INTEGER NOT NULL,
        issued_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, revoked_at TEXT NULL,
        UNIQUE(user_id, course_slug)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, badge_slug TEXT NOT NULL,
        title TEXT NOT NULL, credential_id TEXT NULL, awarded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, badge_slug)
    )');
    return $pdo;
}

function academy_completed_lessons(int $userId, string $courseSlug): array
{
    $statement = academy_db()->prepare('SELECT lesson_number FROM academy_course_progress WHERE user_id=? AND course_slug=? ORDER BY lesson_number');
    $statement->execute([$userId, $courseSlug]);
    return array_map('intval', array_column($statement->fetchAll(), 'lesson_number'));
}

function academy_progress(int $userId, string $courseSlug): array
{
    $course = academy_course($courseSlug);
    $total = count($course['lessons'] ?? []);
    $completed = academy_completed_lessons($userId, $courseSlug);
    $attempt = academy_db()->prepare('SELECT MAX(score) FROM academy_assessment_attempts WHERE user_id=? AND course_slug=?');
    $attempt->execute([$userId, $courseSlug]);
    $credential = academy_db()->prepare('SELECT * FROM academy_credentials WHERE user_id=? AND course_slug=? AND revoked_at IS NULL LIMIT 1');
    $credential->execute([$userId, $courseSlug]);
    return [
        'completed' => count($completed),
        'completed_lessons' => $completed,
        'total' => $total,
        'percent' => $total > 0 ? (int)round(count($completed) / $total * 100) : 0,
        'best_score' => (int)($attempt->fetchColumn() ?: 0),
        'credential' => $credential->fetch() ?: null,
    ];
}

function academy_complete_lesson(int $userId, string $courseSlug, int $lesson): void
{
    $course = academy_course($courseSlug);
    if (!$course || $lesson < 1 || $lesson > count($course['lessons'])) {
        throw new InvalidArgumentException('Unknown lesson.');
    }
    $statement = academy_db()->prepare('INSERT OR IGNORE INTO academy_course_progress(user_id,course_slug,lesson_number) VALUES(?,?,?)');
    $statement->execute([$userId, $courseSlug, $lesson]);
}

function academy_lesson_unlocked(int $userId, string $courseSlug, int $lesson): bool
{
    if ($lesson <= 1) {
        return true;
    }
    return in_array($lesson - 1, academy_completed_lessons($userId, $courseSlug), true);
}

function academy_score(array $questions, array $answers): int
{
    $score = 0;
    foreach ($questions as $index => $question) {
        if (hash_equals((string)$question['answer'], (string)($answers[$index] ?? ''))) {
            $score++;
        }
    }
    return $score;
}

function academy_learner_name(): string
{
    $name = trim((string)($_SESSION['name'] ?? ''));
    if ($name !== '') {
        return function_exists('mb_substr') ? mb_substr($name, 0, 120) : substr($name, 0, 120);
    }
    $email = (string)($_SESSION['email'] ?? 'Beyond learner');
    $label = strstr($email, '@', true) ?: $email;
    return function_exists('mb_substr') ? mb_substr($label, 0, 120) : substr($label, 0, 120);
}

function academy_issue_credential(int $userId, string $courseSlug, int $score): array
{
    $course = academy_course($courseSlug);
    if (!$course) {
        throw new InvalidArgumentException('Unknown pathway.');
    }
    $existing = academy_db()->prepare('SELECT * FROM academy_credentials WHERE user_id=? AND course_slug=? LIMIT 1');
    $existing->execute([$userId, $courseSlug]);
    $credential = $existing->fetch();
    if ($credential) {
        return $credential;
    }
    $credentialId = 'BVC-' . strtoupper(implode('-', str_split(bin2hex(random_bytes(12)), 6)));
    $pdo = academy_db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO academy_credentials(credential_id,user_id,course_slug,learner_name,score) VALUES(?,?,?,?,?)');
        $statement->execute([$credentialId, $userId, $courseSlug, academy_learner_name(), $score]);
        $badge = $pdo->prepare('INSERT OR IGNORE INTO academy_badges(user_id,badge_slug,title,credential_id) VALUES(?,?,?,?)');
        $badge->execute([$userId, $courseSlug, $course['title'] . ' Badge', $credentialId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    $existing->execute([$userId, $courseSlug]);
    return $existing->fetch();
}

function academy_record_assessment(int $userId, string $courseSlug, array $answers): array
{
    $course = academy_course($courseSlug);
    if (!$course) {
        throw new InvalidArgumentException('Unknown pathway.');
    }
    $progress = academy_progress($userId, $courseSlug);
    if ($progress['completed'] !== $progress['total']) {
        throw new RuntimeException('Complete every lesson before taking the assessment.');
    }
    $questions = $course['questions'];
    $score = academy_score($questions, $answers);
    $passed = $score >= (int)ceil(count($questions) * 0.8);
    $statement = academy_db()->prepare('INSERT INTO academy_assessment_attempts(user_id,course_slug,score,question_count,passed) VALUES(?,?,?,?,?)');
    $statement->execute([$userId, $courseSlug, $score, count($questions), $passed ? 1 : 0]);
    $credential = $passed ? academy_issue_credential($userId, $courseSlug, $score) : null;
    return ['score' => $score, 'total' => count($questions), 'passed' => $passed, 'credential' => $credential];
}

function academy_credential(string $credentialId): ?array
{
    if (!preg_match('/^BVC-(?:[A-F0-9]{6}-){3}[A-F0-9]{6}$/', strtoupper($credentialId))) {
        return null;
    }
    $statement = academy_db()->prepare('SELECT * FROM academy_credentials WHERE credential_id=? LIMIT 1');
    $statement->execute([strtoupper($credentialId)]);
    return $statement->fetch() ?: null;
}

function academy_badges(int $userId): array
{
    $statement = academy_db()->prepare('SELECT * FROM academy_badges WHERE user_id=? ORDER BY awarded_at DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}
