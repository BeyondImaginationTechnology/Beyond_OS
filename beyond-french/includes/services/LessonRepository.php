<?php
declare(strict_types=1);

final class LessonRepository
{
    public static function allLessons(): array
    {
        $lessons = read_json(LESSONS_FILE);
        usort($lessons, static fn(array $a, array $b): int => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        return $lessons;
    }

    public static function lessonById(int $id): ?array
    {
        foreach (self::allLessons() as $lesson) {
            if ((int)($lesson['id'] ?? 0) === $id) {
                return $lesson;
            }
        }
        return null;
    }

    public static function todaysLesson(): ?array
    {
        $lessons = self::allLessons();
        $today = date('Y-m-d');
        foreach ($lessons as $lesson) {
            if (($lesson['date'] ?? '') === $today) {
                return $lesson;
            }
        }
        foreach ($lessons as $lesson) {
            $date = (string)($lesson['date'] ?? '');
            if ($date !== '' && $date < $today) {
                return $lesson;
            }
        }
        return $lessons[count($lessons) - 1] ?? null;
    }

    public static function frenchModules(): array
    {
        return [
            'greetings' => ['title' => 'Greetings', 'icon' => '👋', 'description' => 'Meet people, introduce yourself, and handle everyday conversation.'],
            'food' => ['title' => 'Food', 'icon' => '🥐', 'description' => 'Order meals, shop for ingredients, and talk about what you enjoy.'],
            'transport-travel' => ['title' => 'Transportation & Travel', 'icon' => '🚆', 'description' => 'Use buses, trains, airports, hotels, directions, and travel times.'],
            'ocean' => ['title' => 'Ocean', 'icon' => '🌊', 'description' => 'Explore beaches, weather, boats, and life near the sea.'],
            'sports' => ['title' => 'Sports', 'icon' => '⚽', 'description' => 'Talk about games, movement, teams, and friendly competition.'],
        ];
    }

    public static function lessonModule(array $lesson): string
    {
        $slug = strtolower(trim((string)($lesson['module'] ?? 'greetings')));
        return isset(self::frenchModules()[$slug]) ? $slug : 'greetings';
    }

    public static function orderedLessons(): array
    {
        $lessons = self::allLessons();
        $order = array_flip(array_keys(self::frenchModules()));
        usort($lessons, static function (array $a, array $b) use ($order): int {
            $ma = $order[self::lessonModule($a)] ?? 99;
            $mb = $order[self::lessonModule($b)] ?? 99;
            return $ma <=> $mb ?: strcmp($a['date'] ?? '', $b['date'] ?? '') ?: ((int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));
        });
        return $lessons;
    }

    public static function lessonPosition(int $id): array
    {
        $ordered = self::orderedLessons();
        $modules = self::frenchModules();
        $moduleKeys = array_keys($modules);
        foreach ($ordered as $index => $lesson) {
            if ((int)($lesson['id'] ?? 0) === $id) {
                $slug = self::lessonModule($lesson);
                $within = 0;
                foreach ($ordered as $candidate) {
                    if (self::lessonModule($candidate) === $slug) {
                        $within++;
                    }
                    if ((int)($candidate['id'] ?? 0) === $id) {
                        break;
                    }
                }
                return [
                    'index' => $index,
                    'module' => array_search($slug, $moduleKeys, true) + 1,
                    'module_slug' => $slug,
                    'module_title' => $modules[$slug]['title'],
                    'lesson' => $within,
                    'total' => count($ordered),
                ];
            }
        }
        return [
            'index' => 0,
            'module' => 1,
            'module_slug' => 'greetings',
            'module_title' => 'Greetings',
            'lesson' => 1,
            'total' => count($ordered),
        ];
    }

    public static function lessonIsToday(?array $lesson): bool
    {
        return $lesson !== null && ($lesson['date'] ?? '') === date('Y-m-d');
    }

    public static function lessonAudioMap(int $lessonId): array
    {
        if ($lessonId < 1) {
            return [];
        }
        $audio = [];
        foreach (self::allLessons() as $lesson) {
            if ((int)($lesson['id'] ?? 0) !== $lessonId) {
                continue;
            }
            foreach ((array)($lesson['audio_urls'] ?? []) as $language => $path) {
                if ((string)$path !== '') {
                    $audio[(string)$language] = (string)$path;
                }
            }
            if (!isset($audio['fr-FR']) && (string)($lesson['audio_url'] ?? '') !== '') {
                $audio['fr-FR'] = (string)$lesson['audio_url'];
            }
            break;
        }
        try {
            $stmt = sqlite_db()->prepare("SELECT language, audio_path FROM french_lesson_audio WHERE lesson_id=? AND generation_status='ready' AND audio_path<>'' ORDER BY id ASC");
            $stmt->execute([$lessonId]);
            foreach ($stmt->fetchAll() as $row) {
                $language = (string)($row['language'] ?? '');
                if ($language !== '' && (string)($row['audio_path'] ?? '') !== '') {
                    $audio[$language] = (string)$row['audio_path'];
                }
            }
            return $audio;
        } catch (Throwable $error) {
            error_log('Lesson audio lookup failed: ' . $error->getMessage());
            return $audio;
        }
    }
}
