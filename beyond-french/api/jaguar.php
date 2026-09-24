<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'POST a translation request.']);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
$input = trim((string)($payload['input'] ?? ''));
$from = trim((string)($payload['from'] ?? 'english'));
$to = trim((string)($payload['to'] ?? 'french'));
$mode = trim((string)($payload['mode'] ?? 'translate'));
$supportedLanguages = ['english','french','spanish','kreyol','patois','italian','german','portuguese','russian','lingala','swahili','arabic'];
if (!in_array($from, $supportedLanguages, true) || !in_array($to, $supportedLanguages, true)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','status'=>'unsupported_language','message'=>'Choose languages offered in Beyond French.','supported_languages'=>$supportedLanguages]);
    exit;
}
if ($input === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'Enter a phrase first.']);
    exit;
}

$endpoint = trim((string)getenv('BEYOND1_LLAMA_JAGUAR_URL'));
// Cost-free local mode is the default. Remote inference requires explicit opt-in.
$modelOptIn = trim((string)getenv('BEYOND1_LLAMA_JAGUAR_ENABLE')) === '1';
$dictionary = json_decode((string)@file_get_contents(__DIR__ . '/../data/dictionary.json'), true) ?: [];
$multilingualBank = json_decode((string)@file_get_contents(__DIR__ . '/../data/multilingual-bank.json'), true) ?: [];
foreach ($multilingualBank as $phrase) {
    $dictionary[] = [
        'english' => $phrase['english'] ?? '',
        'french' => $phrase['french'] ?? '',
        'french_pronunciation' => $phrase['french_pronunciation'] ?? '',
        'italian' => $phrase['italian'] ?? '',
        'italian_pronunciation' => $phrase['italian_pronunciation'] ?? '',
        'german' => $phrase['german'] ?? '',
        'german_pronunciation' => $phrase['german_pronunciation'] ?? '',
        'portuguese' => $phrase['portuguese'] ?? '',
        'portuguese_pronunciation' => $phrase['portuguese_pronunciation'] ?? '',
        'russian' => $phrase['russian'] ?? '',
        'russian_pronunciation' => $phrase['russian_pronunciation'] ?? '',
        'type' => 'multilingual',
    ];
}
$normalize = static function (string $value): string {
    $value = strtolower(trim($value));
    if (function_exists('iconv')) $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    return preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';
};
$lookupInput = preg_replace('/^(?:how do (?:i|you) say|how can i say|translate)\s+/i', '', $input) ?: $input;
$lookupInput = preg_replace('/\s+(?:in|to)\s+(?:english|french|spanish|kreyol|patois|italian|german|portuguese|russian|lingala|swahili|arabic)\s*\??$/i', '', $lookupInput) ?: $lookupInput;
$query = $normalize($lookupInput);
$entry = null;
foreach ($dictionary as $candidate) {
    foreach (['english', 'french', 'spanish', 'kreyol', 'patois', 'italian', 'german', 'portuguese', 'russian', 'lingala', 'swahili', 'arabic'] as $field) {
        if ($query !== '' && $query === $normalize((string)($candidate[$field] ?? ''))) {
            $entry = $candidate;
            break 2;
        }
    }
}
$languageKey = ['english'=>'english','french'=>'french','kreyol'=>'kreyol','patois'=>'patois','spanish'=>'spanish','italian'=>'italian','german'=>'german','portuguese'=>'portuguese','russian'=>'russian','lingala'=>'lingala','swahili'=>'swahili','arabic'=>'arabic'];
$localTranslation = $entry[$languageKey[$to] ?? 'french'] ?? null;
$targetPronunciation = $entry[$to . '_pronunciation'] ?? ($to === 'french' ? ($entry['pronunciation'] ?? null) : null);
$localResponse = [
    'ok' => true,
    'assistant' => 'Beyond-1 Llama Jaguar',
    'status' => 'local_only',
    'model_usage' => 'disabled',
    'mode' => $mode,
    'message' => $entry
        ? ($mode === 'question' ? 'Free language guide: this phrase is in the Beyond French dictionary. Ask a more specific question when the optional assistant connection is enabled.' : 'Free dictionary match: ' . (string)($localTranslation ?? 'entry found'))
        : 'Free mode is active. Try an exact phrase from the Beyond French dictionary, or use Dictionary mode to browse matches.',
    'translation' => $localTranslation,
    'pronunciation' => $targetPronunciation,
    'comparison' => $entry ? array_intersect_key($entry, array_flip($supportedLanguages)) : null,
    'request' => ['input'=>$input,'from'=>$from,'to'=>$to,'mode'=>$mode],
];
if (!$modelOptIn || $endpoint === '' || !function_exists('curl_init')) {
    http_response_code(200);
    echo json_encode($localResponse, JSON_UNESCAPED_UNICODE);
    exit;
}

$request = json_encode(['assistant'=>'Beyond-1 Llama Jaguar','task'=>$mode === 'dictionary' ? 'dictionary_lookup' : ($mode === 'question' ? 'language_question' : 'translation'),'mode'=>$mode,'input'=>$input,'source_language'=>$from,'target_language'=>$to,'return_format'=>$mode === 'dictionary' ? 'dictionary_entry_pronunciation_examples' : ($mode === 'question' ? 'answer_with_examples_and_context' : 'translation_pronunciation_context')]);
$headers = ['Content-Type: application/json'];
$token = trim((string)getenv('BEYOND1_LLAMA_JAGUAR_TOKEN'));
if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
$curl = curl_init($endpoint);
curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$request,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30]);
$response = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$error = curl_error($curl);
curl_close($curl);
if ($response === false || $error !== '') {
    http_response_code(502);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'Beyond-1 Llama Jaguar could not reach the model service.']);
    exit;
}
http_response_code($status >= 200 && $status < 300 ? 200 : $status);
echo json_encode(['ok'=>$status >= 200 && $status < 300,'assistant'=>'Beyond-1 Llama Jaguar','response'=>json_decode($response, true) ?? $response]);
