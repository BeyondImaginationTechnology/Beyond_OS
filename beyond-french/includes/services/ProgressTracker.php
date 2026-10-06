<?php
declare(strict_types=1);

final class ProgressTracker
{
    public static function getProgress(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }
        $stmt = sqlite_db()->prepare('SELECT * FROM french_learning_progress WHERE user_id=?');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }

    public static function continueLesson(int $userId): ?array
    {
        $ordered = LessonRepository::orderedLessons();
        if (!$ordered) {
            return null;
        }
        $progress = self::getProgress($userId);
        $target = (int)($progress['current_lesson_id'] ?? 0);
        foreach ($ordered as $lesson) {
            if ((int)$lesson['id'] === $target) {
                return $lesson;
            }
        }
        return $ordered[0];
    }

    public static function markStarted(int $userId, int $lessonId): void
    {
        if ($userId < 1 || $lessonId < 1) {
            return;
        }
        $pdo = sqlite_db();
        $stmt = $pdo->prepare('INSERT INTO french_learning_progress(user_id,current_lesson_id,last_lesson_id) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET current_lesson_id=excluded.current_lesson_id,last_lesson_id=excluded.last_lesson_id,updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([$userId, $lessonId, $lessonId]);
    }

    public static function markCompleted(int $userId, int $lessonId): ?array
    {
        if ($userId < 1) {
            return null;
        }
        $ordered = LessonRepository::orderedLessons();
        $next = null;
        foreach ($ordered as $index => $lesson) {
            if ((int)$lesson['id'] === $lessonId) {
                $next = $ordered[$index + 1] ?? $lesson;
                break;
            }
        }
        $progress = self::getProgress($userId);
        $done = json_decode((string)($progress['completed_lessons_json'] ?? '[]'), true);
        if (!is_array($done)) {
            $done = [];
        }
        $done = array_values(array_unique([...$done, $lessonId]));

        $pdo = sqlite_db();
        $stmt = $pdo->prepare('INSERT INTO french_learning_progress(user_id,current_lesson_id,last_lesson_id,completed_lessons_json) VALUES(?,?,?,?) ON CONFLICT(user_id) DO UPDATE SET current_lesson_id=excluded.current_lesson_id,last_lesson_id=excluded.last_lesson_id,completed_lessons_json=excluded.completed_lessons_json,updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([$userId, (int)($next['id'] ?? $lessonId), $lessonId, json_encode($done)]);
        return $next;
    }
}
