<?php
declare(strict_types=1);

/** Store raw Gemini fallback pairs for the scheduled training-dataset review. */
function jaguar_gemini_training_table(PDO $db): void
{
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $db->exec("CREATE TABLE IF NOT EXISTS jaguar_gemini_training_examples (
            id INTEGER PRIMARY KEY AUTOINCREMENT, prompt TEXT NOT NULL, answer TEXT NOT NULL,
            citations_json TEXT NOT NULL, model TEXT NOT NULL, language TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $db->exec('CREATE INDEX IF NOT EXISTS idx_jaguar_gemini_training_created ON jaguar_gemini_training_examples(created_at)');
        return;
    }
    $db->exec("CREATE TABLE IF NOT EXISTS jaguar_gemini_training_examples (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, prompt LONGTEXT NOT NULL,
        answer LONGTEXT NOT NULL, citations_json LONGTEXT NOT NULL, model VARCHAR(100) NOT NULL,
        language VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_jaguar_gemini_training_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function jaguar_gemini_record_training(PDO $db, string $prompt, string $answer, array $citations, string $model, string $language): void
{
    jaguar_gemini_training_table($db);
    $statement = $db->prepare('INSERT INTO jaguar_gemini_training_examples(prompt,answer,citations_json,model,language) VALUES(?,?,?,?,?)');
    $statement->execute([$prompt, $answer, json_encode($citations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $model, $language]);
}

/** Returns a Gemini answer or null when fallback is unavailable or unsuccessful. */
function jaguar_gemini_answer(string $prompt, string $language): ?array
{
    $key = jaguar_runtime_config('gemini_api_key');
    if ($key === '' || !function_exists('curl_init')) return null;
    $model = jaguar_runtime_config('gemini_model');
    if ($model === '') $model = 'gemini-2.5-flash';
    if (!preg_match('/^[A-Za-z0-9._-]{1,100}$/', $model)) return null;
    $payload = [
        'systemInstruction' => ['parts' => [['text' => 'Answer the user clearly and concisely. Use Google Search when it improves accuracy. Do not claim to be Jaguar or invent sources.']]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'tools' => [['google_search' => (object)[]]],
        'generationConfig' => ['temperature' => 0.35, 'maxOutputTokens' => 700],
    ];
    $request = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($key));
    curl_setopt_array($request, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    $body = curl_exec($request); $status = (int)curl_getinfo($request, CURLINFO_RESPONSE_CODE); curl_close($request);
    if (!is_string($body) || $status < 200 || $status >= 300) return null;
    $decoded = json_decode($body, true);
    $candidate = is_array($decoded['candidates'][0] ?? null) ? $decoded['candidates'][0] : null;
    $parts = is_array($candidate['content']['parts'] ?? null) ? $candidate['content']['parts'] : [];
    $text = trim(implode("\n", array_values(array_filter(array_map(static fn($part): string => is_array($part) ? trim((string)($part['text'] ?? '')) : '', $parts)))));
    if ($text === '') return null;
    $citations = [];
    foreach (($candidate['groundingMetadata']['groundingChunks'] ?? []) as $chunk) {
        $web = is_array($chunk['web'] ?? null) ? $chunk['web'] : [];
        $url = trim((string)($web['uri'] ?? ''));
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) $citations[] = ['title' => trim((string)($web['title'] ?? $url)), 'url' => $url];
    }
    return ['message' => $text, 'model' => $model, 'citations' => array_values(array_unique($citations, SORT_REGULAR))];
}
