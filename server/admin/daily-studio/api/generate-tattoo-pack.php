<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/beyond-ai.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function tattooPackJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function tattooPackText(array $input, string $key, int $limit, string $default = ''): string
{
    return mb_substr(trim((string)($input[$key] ?? $default)), 0, $limit);
}

function tattooPackImage(string $dataUri): array
{
    if (!preg_match('#^data:(image/(?:png|jpeg|webp));base64,(.+)$#s', $dataUri, $matches)) {
        throw new RuntimeException('Generate or upload a stencil before creating a derived asset.');
    }
    $bytes = base64_decode($matches[2], true);
    $info = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
    if (
        !is_string($bytes)
        || strlen($bytes) > 15 * 1024 * 1024
        || $info === false
        || !in_array((string)($info['mime'] ?? ''), ['image/png', 'image/jpeg', 'image/webp'], true)
    ) {
        throw new RuntimeException('The stencil reference image is invalid or too large.');
    }
    return [$bytes, (string)$info['mime']];
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        tattooPackJson(['ok' => false, 'error' => 'POST required.'], 405);
    }
    if (!Auth::check()) {
        tattooPackJson(['ok' => false, 'error' => 'Administrator access required.'], 403);
    }
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        tattooPackJson(['ok' => false, 'error' => 'Invalid security token.'], 403);
    }
    if (!function_exists('curl_init')) {
        tattooPackJson(['ok' => false, 'error' => 'The server cURL extension is required.'], 503);
    }

    $input = json_decode((string)file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new RuntimeException('Invalid image request.');
    [$stencilBytes, $stencilMime] = tattooPackImage((string)($input['stencil_image'] ?? ''));

    $assetType = tattooPackText($input, 'asset_type', 20, 'pack');
    if (!in_array($assetType, ['pack', 'reference', 'placement'], true)) {
        throw new RuntimeException('Choose a supported tattoo asset type.');
    }
    $title = tattooPackText($input, 'title', 100, 'Daily Stencil');
    $collection = tattooPackText($input, 'collection', 100, 'Beyond Tattoo Collection');
    $releaseDate = tattooPackText($input, 'release_date', 20);
    $sequence = max(1, min(55, (int)($input['sequence'] ?? 1)));
    $packStyle = tattooPackText($input, 'pack_style', 100, 'Premium retail hanging pack');
    $concept = tattooPackText($input, 'concept', 700);
    $placement = tattooPackText($input, 'placement', 100, 'Artist-selected placement');
    if ($title === '' || $collection === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        throw new RuntimeException('Choose a scheduled drop before generating its artwork.');
    }

    $prompts = [];
    $prompts['pack'] = <<<PROMPT
Create a premium, straight-on 2:3 vertical product-packaging hero for Beyond Tattoo.

SOURCE ART
Use the attached black-and-white tattoo stencil as the exact central featured artwork. Preserve its identity, linework, proportions and white background. Do not redraw it as a different subject.

DROP METADATA
- Scheduled design: {$title}
- Collection: {$collection}
- Release date: {$releaseDate}
- Season sequence: {$sequence} of 55
- Creative context: {$concept}
- Package format: {$packStyle}

ART DIRECTION
Luxury black retail packaging with dimensional foil, embossed borders, subtle material folds and museum-grade ornamental detailing. Use antique gold as the primary accent and a restrained collection accent color. Present the stencil in a large clean white or warm-ivory window so it remains recognizably transfer-ready. Include elegant blank header, date, sequence, collection and footer label panels for exact typography to be overlaid later. Include five small premium feature-icon positions along the lower area. Keep the layout symmetrical, front-facing, commercially believable and suitable for a high-end tattoo supply launch.

CONSTRAINTS
No people, hands, skin, tattooed bodies, store shelf, extra products or environmental scene. Do not invent readable words, letters, dates, numbers, logos or watermarks; keep all label panels blank. Do not crop the package. Return one finished vertical package design filling the frame with safe margins.
PROMPT;
    $prompts['reference'] = <<<PROMPT
Create a museum-quality, high-detail 2:3 vertical reference artwork for the Beyond Tattoo daily stencil "{$title}".

SOURCE ART
Use the attached black-and-white tattoo stencil as the exact design blueprint. Preserve the central subject, pose, silhouette, major symbols, framing and proportions. Translate the line drawing into a premium cinematic realism render without changing its identity.

DROP DIRECTION
- Collection: {$collection}
- Scheduled sequence: {$sequence} of 55
- Creative context: {$concept}

ART DIRECTION
Build a dramatic collection-appropriate environment around the subject with dimensional lighting, realistic materials, antique gold detail, deep obsidian shadows and a restrained collection accent color. The result should look like luxury tattoo reference art: centered, symmetrical where the source is symmetrical, highly legible and rich enough to guide an artist while remaining faithful to the stencil.

CONSTRAINTS
No packaging, cards, mockups, people, body parts, readable text, letters, dates, numbers, logos or watermarks. Do not crop the subject. Do not add unrelated figures or replace the source design. Return one finished vertical reference artwork with safe margins.
PROMPT;
    $prompts['placement'] = <<<PROMPT
Create a premium editorial tattoo placement mockup in a 2:3 vertical frame for the Beyond Tattoo daily stencil "{$title}".

SOURCE ART
Apply the attached black-and-white stencil as the exact tattoo design. Preserve its subject, orientation, line hierarchy and proportions. Render it as healed black-and-grey tattoo ink with believable skin integration and professional studio photography.

PLACEMENT
- Requested body placement: {$placement}
- Collection: {$collection}
- Creative context: {$concept}

ART DIRECTION
Show one consenting adult model in a tasteful, non-sexual, modest crop that clearly demonstrates scale and anatomical flow at the requested placement. Use a dark neutral studio background, directional rim light and natural skin texture. Keep the tattoo fully visible, undistorted and the clear focal point. Faces are unnecessary and should be outside the frame when possible.

CONSTRAINTS
Adults only. No nudity, lingerie, sexualized pose, blood, needles, active tattooing, packaging, cards, readable text, letters, dates, numbers, logos or watermarks. Do not change or mirror the design. Return one finished vertical placement mockup with safe margins.
PROMPT;
    $prompt = $prompts[$assetType];

    $openAiKey = trim((string)beyond_ai_config('api_key', ''));
    $googleKey = trim((string)beyond_ai_config('google_image_key', ''));
    $errors = [];

    if ($openAiKey !== '' && !str_contains($openAiKey, 'YOUR_')) {
        $temporary = tempnam(sys_get_temp_dir(), 'bt-asset-reference-');
        if (!is_string($temporary) || file_put_contents($temporary, $stencilBytes, LOCK_EX) === false) {
            throw new RuntimeException('The stencil reference image could not be staged.');
        }
        try {
            $editFields = [
                'model' => 'gpt-image-2',
                'prompt' => $prompt,
                'size' => '1024x1536',
                'quality' => 'high',
                'output_format' => 'png',
                'background' => 'opaque',
                'image[]' => new CURLFile($temporary, $stencilMime, 'stencil-reference.png'),
            ];
            $curl = curl_init('https://api.openai.com/v1/images/edits');
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $openAiKey],
                CURLOPT_POSTFIELDS => $editFields,
            ]);
            $raw = curl_exec($curl);
            $curlError = curl_error($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $response = is_string($raw) ? json_decode($raw, true) : null;
            $image = is_array($response) ? (string)($response['data'][0]['b64_json'] ?? '') : '';
            if ($status >= 200 && $status < 300 && $image !== '' && base64_decode($image, true) !== false) {
                tattooPackJson([
                    'ok' => true,
                    'asset_type' => $assetType,
                    'image' => 'data:image/png;base64,' . $image,
                    'provider' => 'openai',
                    'model' => 'gpt-image-2',
                    'quality' => 'high',
                    'size' => '1024x1536',
                    'usage' => $response['usage'] ?? null,
                ]);
            }
            $errors[] = is_array($response)
                ? (string)($response['error']['message'] ?? 'OpenAI tattoo asset generation failed.')
                : ($curlError ?: 'OpenAI tattoo asset generation failed.');
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    if ($googleKey !== '' && !str_contains($googleKey, 'YOUR_')) {
        $model = trim((string)beyond_ai_config('google_image_model', 'gemini-3.1-flash-image')) ?: 'gemini-3.1-flash-image';
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $model)) throw new RuntimeException('The configured Google image model is invalid.');
        $body = json_encode([
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inlineData' => ['mimeType' => $stencilMime, 'data' => base64_encode($stencilBytes)]],
                ],
            ]],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
                'imageConfig' => ['aspectRatio' => '2:3'],
            ],
        ], JSON_THROW_ON_ERROR);
        $curl = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_HTTPHEADER => ['x-goog-api-key: ' . $googleKey, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
        ]);
        $raw = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $response = is_string($raw) ? json_decode($raw, true) : null;
        $image = '';
        $mime = 'image/png';
        foreach ((array)($response['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (!empty($part['inlineData']['data'])) {
                $image = (string)$part['inlineData']['data'];
                $mime = (string)($part['inlineData']['mimeType'] ?? 'image/png');
                break;
            }
        }
        if ($status >= 200 && $status < 300 && $image !== '' && ($bytes = base64_decode($image, true)) !== false) {
            if ($mime !== 'image/png' && function_exists('imagecreatefromstring')) {
                $canvas = @imagecreatefromstring($bytes);
                if ($canvas !== false) {
                    ob_start();
                    imagepng($canvas, null, 9);
                    $png = ob_get_clean();
                    imagedestroy($canvas);
                    if (is_string($png) && $png !== '') {
                        $image = base64_encode($png);
                        $mime = 'image/png';
                    }
                }
            }
            if ($mime !== 'image/png') throw new RuntimeException('Google returned a pack image, but PHP GD is required to convert it to PNG.');
            tattooPackJson([
                'ok' => true,
                'asset_type' => $assetType,
                'image' => 'data:image/png;base64,' . $image,
                'provider' => 'google',
                'model' => $model,
                'quality' => 'high',
                'size' => '1024x1536',
            ]);
        }
        $errors[] = is_array($response)
            ? (string)($response['error']['message'] ?? 'Google tattoo asset generation failed.')
            : ($curlError ?: 'Google tattoo asset generation failed.');
    }

    if (!$errors) throw new RuntimeException('Add an OpenAI or Google image API key in protected Site Settings.');
    throw new RuntimeException('Image providers failed: ' . implode(' | ', $errors));
} catch (Throwable $error) {
    error_log('Tattoo asset generation failed: ' . $error->getMessage());
    tattooPackJson(['ok' => false, 'error' => $error->getMessage()], 400);
}
