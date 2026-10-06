<?php
declare(strict_types=1);

final class AcademyRepository
{
    public static function catalog(): array
    {
        static $catalog = null;
        if (is_array($catalog)) {
            return $catalog;
        }
        $catalog = read_json(FRENCH_ACADEMY_FILE, ['age_groups' => [], 'modules' => []]);
        return $catalog;
    }

    public static function ageGroups(): array
    {
        $groups = [];
        foreach ((array)(self::catalog()['age_groups'] ?? []) as $group) {
            $slug = (string)($group['slug'] ?? '');
            if ($slug !== '') {
                $groups[$slug] = $group;
            }
        }
        return $groups;
    }

    public static function modules(): array
    {
        $modules = [];
        foreach ((array)(self::catalog()['modules'] ?? []) as $module) {
            $slug = (string)($module['slug'] ?? '');
            if ($slug !== '') {
                $modules[$slug] = $module;
            }
        }
        return $modules;
    }

    public static function preschoolGreetingsLessons(): array
    {
        return [
            ['title' => 'French ABC: A to C', 'english' => 'A, B, C', 'french' => 'A, B, C', 'pronunciation' => 'ah, bay, say', 'teaching' => 'French uses the same alphabet letters as English, but many names sound different. Start by listening and repeating A, B, and C.', 'practice' => 'Point to three letter cards and say A, B, C slowly with a grown-up.', 'culture' => 'French alphabet songs are a playful first step for young learners.'],
            ['title' => 'French ABC: D to F', 'english' => 'D, E, F', 'french' => 'D, E, F', 'pronunciation' => 'day, uh, eff', 'teaching' => 'Listen for the short vowel sound in the French letter E.', 'practice' => 'Clap once for each letter as you say D, E, F.', 'culture' => 'Singing and clapping helps children remember sound patterns.'],
            ['title' => 'French ABC: G to I', 'english' => 'G, H, I', 'french' => 'G, H, I', 'pronunciation' => 'zhay, ash, ee', 'teaching' => 'French G is called zhay, and H is ash.', 'practice' => 'Trace G, H, and I in the air while saying their French names.', 'culture' => 'The letter H is often silent inside French words.'],
            ['title' => 'French ABC: J to L', 'english' => 'J, K, L', 'french' => 'J, K, L', 'pronunciation' => 'zhee, kah, ell', 'teaching' => 'The French J begins with a soft zhee sound.', 'practice' => 'Find J, K, and L in a book or on a keyboard. Say each one.', 'culture' => 'Letter names help children spell names and simple words later.'],
            ['title' => 'French ABC: M to O', 'english' => 'M, N, O', 'french' => 'M, N, O', 'pronunciation' => 'emm, enn, oh', 'teaching' => 'M and N have gentle ending sounds in French.', 'practice' => 'Hum M, then N, then say O with a big round mouth.', 'culture' => 'Listening closely builds a strong foundation for pronunciation.'],
            ['title' => 'French ABC: P to R', 'english' => 'P, Q, R', 'french' => 'P, Q, R', 'pronunciation' => 'pay, koo, air', 'teaching' => 'French Q is called koo and French R has a special throat sound.', 'practice' => 'Repeat P, Q, R three times like a tiny alphabet parade.', 'culture' => 'It is okay if French R feels new; it gets easier with practice.'],
            ['title' => 'French ABC: S to U', 'english' => 'S, T, U', 'french' => 'S, T, U', 'pronunciation' => 'ess, tay, oo', 'teaching' => 'French U is not the same as English oo, but oo is a helpful first try.', 'practice' => 'Say S, T, U while tapping your knees three times.', 'culture' => 'Young learners grow their accent by listening often, not by being perfect.'],
            ['title' => 'French ABC: V to X', 'english' => 'V, W, X', 'french' => 'V, W, X', 'pronunciation' => 'vay, doo-bluh-vay, eeks', 'teaching' => 'W is called double V in French.', 'practice' => 'Make a V shape with your fingers, then repeat V, W, X.', 'culture' => 'French uses the same 26 letters, with its own names for them.'],
            ['title' => 'French ABC: Y and Z', 'english' => 'Y, Z', 'french' => 'Y, Z', 'pronunciation' => 'ee-grek, zed', 'teaching' => 'French Y is called Greek I, and Z is zed.', 'practice' => 'Wave in the air to make Y, then zigzag for Z.', 'culture' => 'Zed is used in French and many other languages.'],
            ['title' => 'Sing the French ABCs', 'english' => 'The French alphabet', 'french' => 'L’alphabet français', 'pronunciation' => 'lah-fah-bay frahn-say', 'teaching' => 'Put all the letters together and celebrate what you can say.', 'practice' => 'Sing or say as much of the French alphabet as you remember with a grown-up.', 'culture' => 'Every new sound you learn prepares you for French words and greetings.'],
        ];
    }

    public static function courseLessons(string $age, string $module): array
    {
        $module = self::validModule($module);
        if (self::validAgeGroup($age) === 'kids' && $module === 'greetings') {
            return self::preschoolGreetingsLessons();
        }
        return (array)(self::modules()[$module]['lessons'] ?? []);
    }

    public static function validAgeGroup(string $age): string
    {
        $age = strtolower(trim($age));
        return isset(self::ageGroups()[$age]) ? $age : 'kids';
    }

    public static function validModule(string $module): string
    {
        $module = strtolower(trim($module));
        return isset(self::modules()[$module]) ? $module : 'greetings';
    }

    public static function courseLesson(string $age, string $module, int $lessonNumber): ?array
    {
        $age = self::validAgeGroup($age);
        $module = self::validModule($module);
        $course = self::modules()[$module] ?? null;
        $group = self::ageGroups()[$age] ?? null;
        if (!$course || !$group || $lessonNumber < 1 || $lessonNumber > 10) {
            return null;
        }
        $lesson = self::courseLessons($age, $module)[$lessonNumber - 1] ?? null;
        if (!is_array($lesson)) {
            return null;
        }
        return $lesson + [
            'age_group' => $age,
            'age_title' => $group['title'],
            'age_guidance' => $group['guidance'],
            'module_slug' => $module,
            'module_title' => $course['title'],
            'module_icon' => $course['icon'],
            'lesson_number' => $lessonNumber,
        ];
    }

    public static function learnerKey(): string
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            return 'u:' . $userId;
        }
        if (empty($_SESSION['french_learner_key'])) {
            $_SESSION['french_learner_key'] = bin2hex(random_bytes(16));
        }
        return 's:' . (string)$_SESSION['french_learner_key'];
    }

    public static function hasFullAccess(): bool
    {
        return is_admin() || !empty($_SESSION['french_academy_entitled']);
    }

    public static function isGuest(): bool
    {
        return empty($_SESSION['user_id']) && !is_admin();
    }

    public static function guestDailyLessonAllowed(?array $lesson): bool
    {
        if (!self::isGuest()) {
            return true;
        }
        $today = LessonRepository::todaysLesson();
        return $lesson !== null && $today !== null && (int)($lesson['id'] ?? 0) === (int)($today['id'] ?? 0);
    }

    public static function moduleAccessible(string $module): bool
    {
        $course = self::modules()[self::validModule($module)] ?? [];
        return !empty($course['free']) || self::hasFullAccess();
    }

    public static function lessonPassed(string $age, string $module, int $lesson): bool
    {
        $stmt = sqlite_db()->prepare('SELECT passed FROM french_academy_progress WHERE learner_key=? AND age_group=? AND module_slug=? AND lesson_number=?');
        $stmt->execute([self::learnerKey(), self::validAgeGroup($age), self::validModule($module), $lesson]);
        return (int)$stmt->fetchColumn() === 1;
    }

    public static function examPassed(string $age, string $module): bool
    {
        $stmt = sqlite_db()->prepare('SELECT 1 FROM french_academy_exam_attempts WHERE learner_key=? AND age_group=? AND module_slug=? AND passed=1 LIMIT 1');
        $stmt->execute([self::learnerKey(), self::validAgeGroup($age), self::validModule($module)]);
        return (bool)$stmt->fetchColumn();
    }

    public static function lessonUnlocked(string $age, string $module, int $lesson): bool
    {
        $age = self::validAgeGroup($age);
        $module = self::validModule($module);
        if (is_admin()) {
            return true;
        }
        if (!self::moduleAccessible($module) || $lesson < 1 || $lesson > 10) {
            return false;
        }
        if ($lesson > 1) {
            return self::lessonPassed($age, $module, $lesson - 1);
        }
        $keys = array_keys(self::modules());
        $index = array_search($module, $keys, true);
        if ($index === 0) {
            return true;
        }
        return $index !== false && self::examPassed($age, $keys[$index - 1]);
    }

    public static function moduleProgress(string $age, string $module): array
    {
        $stmt = sqlite_db()->prepare('SELECT COUNT(*) lessons_passed,COALESCE(MAX(best_score),0) best_score FROM french_academy_progress WHERE learner_key=? AND age_group=? AND module_slug=? AND passed=1');
        $stmt->execute([self::learnerKey(), self::validAgeGroup($age), self::validModule($module)]);
        $row = $stmt->fetch() ?: [];
        return [
            'lessons_passed' => (int)($row['lessons_passed'] ?? 0),
            'best_score' => (int)($row['best_score'] ?? 0),
            'exam_passed' => self::examPassed($age, $module),
        ];
    }

    public static function practiceResponses(string $age, string $module, int $lesson): array
    {
        $stmt = sqlite_db()->prepare('SELECT round_number,response FROM french_academy_practice WHERE learner_key=? AND age_group=? AND module_slug=? AND lesson_number=? ORDER BY round_number');
        $stmt->execute([self::learnerKey(), self::validAgeGroup($age), self::validModule($module), $lesson]);
        $responses = [];
        foreach ($stmt->fetchAll() as $row) {
            $responses[(int)$row['round_number']] = (string)$row['response'];
        }
        return $responses;
    }

    public static function recordPractice(string $age, string $module, int $lesson, int $round, string $response): bool
    {
        $response = trim($response);
        if ($round < 1 || $round > 3 || mb_strlen($response) < 3) {
            return false;
        }
        sqlite_db()->prepare("INSERT INTO french_academy_practice(learner_key,age_group,module_slug,lesson_number,round_number,response) VALUES(?,?,?,?,?,?) ON CONFLICT(learner_key,age_group,module_slug,lesson_number,round_number) DO UPDATE SET response=excluded.response,updated_at=CURRENT_TIMESTAMP")
            ->execute([self::learnerKey(), self::validAgeGroup($age), self::validModule($module), $lesson, $round, $response]);
        return true;
    }

    public static function recordLessonTest(string $age, string $module, int $lesson, int $score, int $questionCount = 10): bool
    {
        $age = self::validAgeGroup($age);
        $module = self::validModule($module);
        $passed = $score >= 8;
        $pdo = sqlite_db();
        $pdo->prepare('INSERT INTO french_academy_test_attempts(learner_key,age_group,module_slug,lesson_number,score,question_count,passed) VALUES(?,?,?,?,?,?,?)')
            ->execute([self::learnerKey(), $age, $module, $lesson, $score, $questionCount, $passed ? 1 : 0]);
        $pdo->prepare("INSERT INTO french_academy_progress(learner_key,age_group,module_slug,lesson_number,best_score,passed,completed_at) VALUES(?,?,?,?,?,?,?) ON CONFLICT(learner_key,age_group,module_slug,lesson_number) DO UPDATE SET best_score=MAX(best_score,excluded.best_score),passed=MAX(passed,excluded.passed),completed_at=CASE WHEN excluded.passed=1 THEN CURRENT_TIMESTAMP ELSE completed_at END,updated_at=CURRENT_TIMESTAMP")
            ->execute([self::learnerKey(), $age, $module, $lesson, $score, $passed ? 1 : 0, $passed ? date(DATE_ATOM) : null]);
        return $passed;
    }

    public static function recordModuleExam(string $age, string $module, int $score, int $questionCount = 10): bool
    {
        $age = self::validAgeGroup($age);
        $module = self::validModule($module);
        $passed = $score >= 8;
        sqlite_db()->prepare('INSERT INTO french_academy_exam_attempts(learner_key,age_group,module_slug,score,question_count,passed) VALUES(?,?,?,?,?,?)')
            ->execute([self::learnerKey(), $age, $module, $score, $questionCount, $passed ? 1 : 0]);
        return $passed;
    }
}
