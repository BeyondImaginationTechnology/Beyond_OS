<?php
declare(strict_types=1);

require __DIR__ . '/../../includes/admin-check.php';
require_once __DIR__ . '/../../../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
function azure_test_out(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') azure_test_out(405, ['ok' => false, 'error' => 'POST required.']);
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)) {
    azure_test_out(419, ['ok' => false, 'error' => 'Reload the page and try again.']);
}

$key = trim((string)beyond_config('narration.azure.api_key', ''));
$region = strtolower(trim((string)beyond_config('narration.azure.region', '')));
if ($key === '' || $region === '') azure_test_out(422, ['ok' => false, 'error' => 'Azure Speech key and region must both be saved.']);
if (!preg_match('/^[a-z0-9-]+$/', $region)) azure_test_out(422, ['ok' => false, 'error' => 'Azure Speech region is invalid.']);
if (!function_exists('curl_init')) azure_test_out(500, ['ok' => false, 'error' => 'PHP cURL is not enabled on this server.']);

$ch = curl_init('https://' . $region . '.tts.speech.microsoft.com/cognitiveservices/voices/list');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => ['Ocp-Apim-Subscription-Key: ' . $key, 'Accept: application/json'],
]);
$body = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);
if ($body === false) azure_test_out(503, ['ok' => false, 'error' => 'Could not connect to Azure Speech: ' . $error]);
if ($status < 200 || $status >= 300) {
    $message = $status === 401 || $status === 403
        ? 'Azure rejected the key or region. Confirm that the saved region matches the Speech resource.'
        : 'Azure Speech connection failed (HTTP ' . $status . ').';
    azure_test_out($status, ['ok' => false, 'error' => $message]);
}
$voices = json_decode((string)$body, true);
if (!is_array($voices)) azure_test_out(502, ['ok' => false, 'error' => 'Azure returned an invalid voice list.']);
$required = ['en-US-JennyNeural', 'fr-FR-DeniseNeural', 'fr-CA-SylvieNeural', 'es-ES-ElviraNeural', 'it-IT-IsabellaNeural', 'de-DE-KatjaNeural', 'ru-RU-SvetlanaNeural', 'pt-PT-RaquelNeural'];
$available = array_fill_keys(array_filter(array_map(static fn($voice) => is_array($voice) ? (string)($voice['ShortName'] ?? '') : '', $voices)), true);
$missing = array_values(array_filter($required, static fn($voice) => !isset($available[$voice])));
$message = 'Azure Speech connected in ' . $region . '. ' . count($voices) . ' voices available.';
if ($missing) $message .= ' Review unavailable defaults: ' . implode(', ', $missing) . '.';
azure_test_out(200, ['ok' => true, 'message' => $message]);
