<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/narration/StudioNarration.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function multilingualBankResponse(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function multilingualBankWrite(string $file, array $records): void {
    $json = json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $temporary = $file . '.tmp';
    if ($json === false || file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $file)) {
        @unlink($temporary);
        throw new RuntimeException('The multilingual bank could not be saved.');
    }
}
function multilingualRussianPronunciation(string $text): string {
    return strtr($text, [
        'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'Ye','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'Kh','Ц'=>'Ts','Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Shch','Ъ'=>'','Ы'=>'Y','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'ye','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ]);
}
function multilingualScheduleBuiltItem(string $scheduleFile, array $item): string {
    $scheduled = is_file($scheduleFile) ? json_decode((string)file_get_contents($scheduleFile), true) : [];
    if (!is_array($scheduled)) $scheduled = [];
    foreach ($scheduled as $lesson) {
        if ((string)($lesson['source_id'] ?? '') === (string)($item['source_id'] ?? '')) return (string)($lesson['date'] ?? '');
    }
    $dates = array_values(array_filter(array_map(static fn(array $lesson): string => (string)($lesson['date'] ?? ''), $scheduled), static fn(string $date): bool => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1));
    sort($dates);
    $today = new DateTimeImmutable('today');
    $next = $dates ? (new DateTimeImmutable((string)end($dates)))->modify('+1 day') : $today;
    if ($next < $today) $next = $today;
    $maxId = 0;
    foreach ($scheduled as $lesson) $maxId = max($maxId, (int)($lesson['id'] ?? 0));
    $date = $next->format('Y-m-d');
    $scheduled[] = [...$item, 'id'=>$maxId+1, 'date'=>$date, 'generator'=>['version'=>'1.3.0','provider'=>'azure','schedule'=>'automatic-bank','scheduled_at'=>date(DATE_ATOM)]];
    multilingualBankWrite($scheduleFile, $scheduled);
    return $date;
}
function azureTranslatePhrase(string $english): array {
    $key = trim((string)beyond_config('ai.azure_translator.api_key', ''));
    $usingSpeechFallback = $key === '';
    $region = trim((string)beyond_config('ai.azure_translator.region', ''));
    if ($key === '') $key = trim((string)beyond_config('narration.azure.api_key', ''));
    if ($region === '' && $usingSpeechFallback) $region = trim((string)beyond_config('narration.azure.region', ''));
    $endpoint = rtrim(trim((string)beyond_config('ai.azure_translator.endpoint', 'https://api.cognitive.microsofttranslator.com')), '/');
    if ($key === '' || ($usingSpeechFallback && $region === '')) throw new RuntimeException('Configure the Azure Translator key, or the Azure Speech key and region, in Premium Voices.');
    if (!preg_match('#^https://[a-z0-9.-]+$#i', $endpoint)) throw new RuntimeException('The Azure Translator endpoint is invalid.');
    $path = str_contains($endpoint, 'cognitive.microsofttranslator.com') ? '/translate' : '/translator/text/v3.0/translate';
    $url = $endpoint . $path . '?api-version=3.0&from=en&to=it&to=de&to=ru&to=pt';
    $curl = curl_init($url);
    $headers = ['Ocp-Apim-Subscription-Key: ' . $key, 'Content-Type: application/json'];
    if ($region !== '' && strtolower($region) !== 'global') $headers[] = 'Ocp-Apim-Subscription-Region: ' . $region;
    curl_setopt_array($curl, [
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>15,
        CURLOPT_TIMEOUT=>60,
        CURLOPT_HTTPHEADER=>$headers,
        CURLOPT_POSTFIELDS=>json_encode([['Text'=>$english]], JSON_THROW_ON_ERROR),
    ]);
    $raw = curl_exec($curl); $error = curl_error($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        throw new RuntimeException($error ?: (string)($decoded['error']['message'] ?? 'Azure translation failed.'));
    }
    $translations = [];
    foreach ((array)($decoded[0]['translations'] ?? []) as $translation) {
        $translations[(string)($translation['to'] ?? '')] = trim((string)($translation['text'] ?? ''));
    }
    foreach (['it','de','ru','pt'] as $language) if (($translations[$language] ?? '') === '') throw new RuntimeException('Azure did not return every requested translation.');
    return $translations;
}

$root = dirname(__DIR__, 4);
$bankFile = $root . '/beyond-french/data/multilingual-bank.json';
$scheduleFile = $root . '/beyond-french/data/multilingual-lessons.json';
$bank = is_file($bankFile) ? json_decode((string)file_get_contents($bankFile), true) : [];
if (!is_array($bank)) $bank = [];
$ready = count(array_filter($bank, static fn(array $item): bool => count((array)($item['audio_urls'] ?? [])) === 5));
$action = strtolower((string)($_GET['action'] ?? 'status'));

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'random') {
    $scheduled = is_file($scheduleFile) ? json_decode((string)file_get_contents($scheduleFile), true) : [];
    if (!is_array($scheduled)) $scheduled = [];
    $used = array_fill_keys(array_map(static fn(array $item): string => (string)($item['source_id'] ?? ''), $scheduled), true);
    $available = array_values(array_filter($bank, static fn(array $item): bool => count((array)($item['audio_urls'] ?? [])) === 5 && !isset($used[(string)($item['source_id'] ?? '')])));
    if (!$available) multilingualBankResponse(['ok'=>false,'error'=>'No unused prerecorded phrase is ready. Build more of the Azure bank.'], 404);
    $item = $available[random_int(0, count($available) - 1)];
    multilingualBankResponse(['ok'=>true,'item'=>$item,'remaining'=>count($available)-1,'ready'=>$ready,'target'=>100]);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') multilingualBankResponse(['ok'=>true,'ready'=>$ready,'target'=>100,'complete'=>$ready>=100]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') multilingualBankResponse(['ok'=>false,'error'=>'Unsupported request.'], 405);
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    multilingualBankResponse(['ok'=>false,'error'=>'Reload the generator and try again.'], 419);
}
if (!function_exists('curl_init')) multilingualBankResponse(['ok'=>false,'error'=>'The PHP cURL extension is required.'], 503);

try {
    @set_time_limit(240);
    $source = json_decode((string)file_get_contents($root . '/beyond-french/data/lessons.json'), true);
    if (!is_array($source)) throw new RuntimeException('The French source bank is unavailable.');
    $unique = []; $sources = [];
    foreach ($source as $lesson) {
        $english = trim((string)($lesson['english'] ?? ''));
        if ($english === '' || isset($unique[mb_strtolower($english)])) continue;
        $unique[mb_strtolower($english)] = true; $sources[] = $lesson;
        if (count($sources) === 100) break;
    }
    if (count($sources) < 100) throw new RuntimeException('At least 100 unique French source phrases are required.');
    $existing = array_fill_keys(array_map(static fn(array $item): string => (string)($item['source_id'] ?? ''), $bank), true);
    $built = null;
    foreach ($sources as $lesson) {
        $sourceId = (string)($lesson['id'] ?? sha1((string)$lesson['english']));
        if (isset($existing[$sourceId])) continue;
        $translated = azureTranslatePhrase((string)$lesson['english']);
        $texts = ['fr'=>(string)$lesson['french'], 'it'=>$translated['it'], 'de'=>$translated['de'], 'ru'=>$translated['ru'], 'pt'=>$translated['pt']];
        $locales = ['fr'=>'fr-FR','it'=>'it-IT','de'=>'de-DE','ru'=>'ru-RU','pt'=>'pt-PT'];
        $audioUrls = [];
        foreach ($texts as $language=>$text) {
            $generated = studio_narration_generate($text, $locales[$language], 'azure');
            $stored = studio_store_mp3((string)$generated['audio_content'], 'beyond-french', 'bank-' . str_pad($sourceId, 4, '0', STR_PAD_LEFT), $locales[$language], $text);
            $audioUrls[$language] = (string)$stored['url'];
        }
        $built = [
            'source_id'=>$sourceId,
            'english'=>(string)$lesson['english'],
            'meaning'=>(string)($lesson['meaning'] ?? 'A practical phrase for everyday conversation.'),
            'french'=>$texts['fr'], 'french_pronunciation'=>(string)($lesson['french_pronunciation'] ?? ''),
            'italian'=>$texts['it'], 'italian_pronunciation'=>$texts['it'],
            'german'=>$texts['de'], 'german_pronunciation'=>$texts['de'],
            'russian'=>$texts['ru'], 'russian_pronunciation'=>multilingualRussianPronunciation($texts['ru']),
            'portuguese'=>$texts['pt'], 'portuguese_pronunciation'=>$texts['pt'],
            'culture_note'=>(string)($lesson['culture_note'] ?? 'Practice this phrase aloud in a short conversation.'),
            'audio_urls'=>$audioUrls,
            'generated_at'=>date(DATE_ATOM),
        ];
        $bank[] = $built;
        multilingualBankWrite($bankFile, $bank);
        $built['scheduled_date'] = multilingualScheduleBuiltItem($scheduleFile, $built);
        break;
    }
    $ready = count(array_filter($bank, static fn(array $item): bool => count((array)($item['audio_urls'] ?? [])) === 5));
    multilingualBankResponse(['ok'=>true,'built'=>$built,'ready'=>$ready,'target'=>100,'complete'=>$ready>=100]);
} catch (Throwable $error) {
    error_log('Multilingual Azure bank: ' . $error->getMessage());
    multilingualBankResponse(['ok'=>false,'error'=>$error->getMessage(),'ready'=>$ready,'target'=>100], 502);
}
