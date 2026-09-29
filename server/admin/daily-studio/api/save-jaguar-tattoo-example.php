<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function jaguarTattooJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') jaguarTattooJson(['ok' => false, 'error' => 'POST required.'], 405);
    if (!Auth::check()) jaguarTattooJson(['ok' => false, 'error' => 'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) jaguarTattooJson(['ok' => false, 'error' => 'Invalid security token.'], 403);

    $input = json_decode((string)file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new RuntimeException('Invalid adapter example.');
    $instruction = mb_substr(trim((string)($input['instruction'] ?? '')), 0, 4000);
    $inputText = mb_substr(trim((string)($input['input_text'] ?? '')), 0, 8000);
    $outputText = mb_substr(trim((string)($input['output_text'] ?? '')), 0, 12000);
    if ($instruction === '' || $inputText === '' || $outputText === '') throw new RuntimeException('Instruction, brief and adapter prompt are required.');

    $db = DailyStudio::db();
    $query = $db->prepare('INSERT INTO jaguar_training_examples(instruction,input_text,output_text,example_type,status,created_by) VALUES(?,?,?,?,?,?)');
    $query->execute([
        $instruction,
        $inputText,
        $outputText,
        'tattoo_stencil_adapter',
        'draft',
        (int)(Auth::user()['id'] ?? 0),
    ]);
    jaguarTattooJson(['ok' => true, 'id' => (int)$db->lastInsertId(), 'status' => 'draft']);
} catch (Throwable $error) {
    error_log('Llama Jaguar tattoo adapter example failed: ' . $error->getMessage());
    jaguarTattooJson(['ok' => false, 'error' => 'The Llama Jaguar adapter example could not be saved.'], 400);
}
