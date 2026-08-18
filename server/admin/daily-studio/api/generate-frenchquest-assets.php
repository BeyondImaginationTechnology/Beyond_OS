<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/beyond-ai.php';
require_once dirname(__DIR__, 4) . '/beyond-french/includes/narration/AzureSpeechProvider.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required.');
}
if (!Auth::check()) {
    http_response_code(403);
    exit('Administrator access required.');
}
if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token.');
}
if (!class_exists('ZipArchive')) {
    http_response_code(503);
    exit('The PHP Zip extension is required.');
}
if (!function_exists('curl_init')) {
    http_response_code(503);
    exit('The PHP cURL extension is required.');
}

@set_time_limit(600);

function frenchquestAzureImage(string $prompt): string
{
    $key = trim((string)beyond_ai_config('azure_image_key', ''));
    $endpoint = rtrim(trim((string)beyond_ai_config('azure_image_endpoint', '')), '/');
    $model = trim((string)beyond_ai_config('azure_image_model', 'MAI-Image-2.5')) ?: 'MAI-Image-2.5';
    if ($key === '' || $endpoint === '') throw new RuntimeException('Azure image generation is not configured.');
    if (!preg_match('#^https://[a-z0-9.-]+\.services\.ai\.azure\.com$#i', $endpoint)) {
        throw new RuntimeException('Use the base Azure services.ai.azure.com endpoint.');
    }
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $model)) throw new RuntimeException('The Azure image model is invalid.');

    $body = json_encode(['model'=>$model, 'prompt'=>$prompt, 'width'=>768, 'height'=>1365], JSON_THROW_ON_ERROR);
    $curl = curl_init($endpoint . '/mai/v1/images/generations');
    curl_setopt_array($curl, [
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>15,
        CURLOPT_TIMEOUT=>180,
        CURLOPT_HTTPHEADER=>['api-key: ' . $key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>$body,
    ]);
    $raw = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = is_string($raw) ? json_decode($raw, true) : null;
    $encoded = is_array($response) ? (string)($response['data'][0]['b64_json'] ?? '') : '';
    $image = $encoded !== '' ? base64_decode($encoded, true) : false;
    if ($status < 200 || $status >= 300 || !is_string($image)) {
        $message = is_array($response) ? (string)($response['error']['message'] ?? '') : '';
        throw new RuntimeException($message !== '' ? $message : ($error ?: 'Azure image generation failed.'));
    }
    return $image;
}

function frenchquestImageset(string $filename): string
{
    return json_encode([
        'images'=>[
            ['filename'=>$filename, 'idiom'=>'universal', 'scale'=>'1x'],
            ['idiom'=>'universal', 'scale'=>'2x'],
            ['idiom'=>'universal', 'scale'=>'3x'],
        ],
        'info'=>['author'=>'xcode', 'version'=>1],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

$destinations = [
    'port-au-prince'=>'A vibrant Port-au-Prince street adventure at golden hour, colorful Caribbean architecture, tap-tap bus, market awnings, palms and distant mountains',
    'haiti-highlands'=>'A lush Haitian mountain highlands adventure, winding trail, Citadelle-inspired stone fortress in the distance, tropical forest, waterfalls and morning mist',
    'morocco'=>'A cinematic Moroccan medina adventure, warm terracotta lanes, mosaic archways, lanterns, market textiles and Atlas Mountains beyond the city',
    'montreal'=>'A lively Montréal winter adventure, old stone buildings, glowing cafés, snowy cobblestones, metro sign and a distant skyline at blue hour',
    'france'=>'A celebratory France finale, Paris rooftops and river promenade at sunset, elegant bridges, gardens and a distant iron tower silhouette',
];
$artDirection = 'Original premium 3D animated mobile game environment, playful world-tour platform adventure, layered depth, rounded stylized forms, rich materials, cinematic lighting, energetic but clean composition. Portrait 9:16 background with a clear darker central play area for readable UI. Environment only: no people, characters, words, letters, logos, flags, interface, buttons, icons, watermark or border.';
$feedback = [
    'good-job'=>'Good job!',
    'try-again'=>'Nice try! You can do it!',
    'destination-cleared'=>'Congratulations! Destination cleared!',
];

$temporary = tempnam(sys_get_temp_dir(), 'frenchquest-assets-');
if (!is_string($temporary)) {
    http_response_code(500);
    exit('Could not create the asset pack.');
}
$zip = new ZipArchive();
try {
    if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Could not open the asset ZIP.');
    foreach ($destinations as $id=>$scene) {
        $asset = 'Destination-' . $id;
        $filename = $asset . '.png';
        $directory = 'Assets.xcassets/' . $asset . '.imageset/';
        $zip->addFromString($directory . $filename, frenchquestAzureImage($scene . '. ' . $artDirection));
        $zip->addFromString($directory . 'Contents.json', frenchquestImageset($filename));
    }

    $narration = require dirname(__DIR__, 4) . '/beyond-french/config/narration.php';
    $provider = new AzureSpeechProvider((array)$narration['providers']['azure']);
    $voices = $provider->voices('en-US');
    $voice = (string)($voices[0]['id'] ?? '');
    if ($voice === '') throw new RuntimeException('Configure an Azure English voice before generating the pack.');
    foreach ($feedback as $filename=>$line) {
        $result = $provider->generate(['text'=>$line, 'language'=>'en-US', 'voice'=>$voice, 'format'=>'mp3', 'speed'=>1.0]);
        $audio = (string)($result['audio_content'] ?? '');
        if ($audio === '') throw new RuntimeException('Azure Speech returned an empty feedback file.');
        $zip->addFromString('Audio/Feedback/' . $filename . '.mp3', $audio);
    }
    $zip->addFromString('FRENCHQUEST-ASSETS.txt', "Azure-generated FrenchQuest asset pack.\nExtract into FrenchQuestApple/Resources and rebuild Build 5 or later.\nNo API credentials are included.\n");
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="FrenchQuest-Azure-Assets.zip"');
    header('Content-Length: ' . (string)filesize($temporary));
    header('Cache-Control: no-store');
    readfile($temporary);
} catch (Throwable $error) {
    if ($zip->status === ZipArchive::ER_OK) $zip->close();
    http_response_code(502);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'FrenchQuest asset generation failed: ' . $error->getMessage();
} finally {
    @unlink($temporary);
}
