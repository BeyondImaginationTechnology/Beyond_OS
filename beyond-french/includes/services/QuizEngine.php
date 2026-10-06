<?php
declare(strict_types=1);

final class QuizEngine
{
    public static function quizOptions(string $correct, array $pool, int $seed): array
    {
        $values = array_values(array_unique(array_filter(array_map('strval', [$correct, ...$pool]), static fn(string $value): bool => $value !== '')));
        while (count($values) < 4) {
            $values[] = 'Review the lesson and try again ' . (count($values) + 1);
        }
        $values = array_slice($values, 0, 4);
        $shift = $seed % count($values);
        return array_values(array_merge(array_slice($values, $shift), array_slice($values, 0, $shift)));
    }

    public static function phraseWord(string $phrase, bool $last = false): string
    {
        $clean = trim((string)preg_replace('/[^\pL\pN\'’-]+/u', ' ', $phrase));
        $words = preg_split('/\s+/u', $clean) ?: [];
        return (string)($last ? ($words[count($words) - 1] ?? '') : ($words[0] ?? ''));
    }

    public static function lessonTestQuestions(string $age, string $module, int $lessonNumber): array
    {
        $module = AcademyRepository::validModule($module);
        $course = AcademyRepository::modules()[$module];
        $lessons = AcademyRepository::courseLessons($age, $module);
        $target = $lessons[$lessonNumber - 1] ?? $lessons[0];
        $others = [];
        for ($i = 1; $i <= 3; $i++) {
            $others[] = $lessons[($lessonNumber - 1 + $i) % 10];
        }
        $french = array_column($others, 'french');
        $english = array_column($others, 'english');
        $pronunciation = array_column($others, 'pronunciation');
        $lastPool = array_map(static fn(array $l): string => self::phraseWord((string)$l['french'], true), $others);
        $firstPool = array_map(static fn(array $l): string => self::phraseWord((string)$l['french']), $others);
        $moduleTitles = array_column(array_values(AcademyRepository::modules()), 'title');
        $pairs = array_map(static fn(array $l): string => $l['english'] . ' — ' . $l['french'], $others);

        return [
            ['prompt' => 'Choose the French for “' . $target['english'] . '”', 'answer' => $target['french'], 'options' => self::quizOptions($target['french'], $french, $lessonNumber)],
            ['prompt' => 'What does “' . $target['french'] . '” mean?', 'answer' => $target['english'], 'options' => self::quizOptions($target['english'], $english, $lessonNumber + 1)],
            ['prompt' => 'Choose the pronunciation for “' . $target['french'] . '”', 'answer' => $target['pronunciation'], 'options' => self::quizOptions($target['pronunciation'], $pronunciation, $lessonNumber + 2)],
            ['prompt' => 'Which French phrase matches the lesson “' . $target['title'] . '”?', 'answer' => $target['french'], 'options' => self::quizOptions($target['french'], $french, $lessonNumber + 3)],
            ['prompt' => 'Complete the phrase: ' . preg_replace('/\s+[^\s]+\s*$/u', ' _____', (string)$target['french']), 'answer' => self::phraseWord((string)$target['french'], true), 'options' => self::quizOptions(self::phraseWord((string)$target['french'], true), $lastPool, $lessonNumber + 4)],
            ['prompt' => 'Which word begins the phrase “' . $target['english'] . '”?', 'answer' => self::phraseWord((string)$target['french']), 'options' => self::quizOptions(self::phraseWord((string)$target['french']), $firstPool, $lessonNumber + 5)],
            ['prompt' => 'Which Academy topic contains this lesson?', 'answer' => $course['title'], 'options' => self::quizOptions($course['title'], $moduleTitles, $lessonNumber + 6)],
            ['prompt' => 'Choose the correct English–French pair.', 'answer' => $target['english'] . ' — ' . $target['french'], 'options' => self::quizOptions($target['english'] . ' — ' . $target['french'], $pairs, $lessonNumber + 7)],
            ['prompt' => 'Which phrase should you practice for this action: ' . $target['practice'], 'answer' => $target['french'], 'options' => self::quizOptions($target['french'], $french, $lessonNumber + 8)],
            ['prompt' => 'Final check: select the meaning of “' . $target['french'] . '”', 'answer' => $target['english'], 'options' => self::quizOptions($target['english'], $english, $lessonNumber + 9)],
        ];
    }

    public static function moduleExamQuestions(string $age, string $module): array
    {
        $course = AcademyRepository::modules()[AcademyRepository::validModule($module)];
        $questions = [];
        $lessons = AcademyRepository::courseLessons($age, $module);
        foreach ($lessons as $index => $lesson) {
            $pool = [];
            for ($i = 1; $i <= 3; $i++) {
                $pool[] = $lessons[($index + $i) % 10]['french'];
            }
            $questions[] = ['prompt' => 'Choose the French for “' . $lesson['english'] . '”', 'answer' => $lesson['french'], 'options' => self::quizOptions($lesson['french'], $pool, $index + 1)];
        }
        return $questions;
    }

    public static function scoreQuiz(array $questions, array $answers): int
    {
        $score = 0;
        foreach ($questions as $index => $question) {
            if (trim((string)($answers[$index] ?? '')) === trim((string)$question['answer'])) {
                $score++;
            }
        }
        return $score;
    }
}
