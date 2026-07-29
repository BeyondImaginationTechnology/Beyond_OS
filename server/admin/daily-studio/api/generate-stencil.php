<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/beyond-ai.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function stencilJson(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function stencilRenderToken(string $png): string {
    $directory = beyond_private_root() . '/tmp/stencil-generations';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('The private stencil preview folder could not be created.');
    }
    foreach (glob($directory . '/*.png') ?: [] as $oldFile) {
        if (is_file($oldFile) && filemtime($oldFile) < time() - 7200) @unlink($oldFile);
    }
    $token = bin2hex(random_bytes(18));
    $file = $directory . '/' . $token . '.png';
    if (file_put_contents($file, $png, LOCK_EX) === false) {
        throw new RuntimeException('The stencil preview could not be prepared for Remotion.');
    }
    @chmod($file, 0600);
    $_SESSION['stencil_render_tokens'][$token] = time();
    return $token;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') stencilJson(['ok'=>false,'error'=>'POST required.'], 405);
    if (!Auth::check()) stencilJson(['ok'=>false,'error'=>'Administrator access required.'], 403);
    if (!Auth::verifyCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) stencilJson(['ok'=>false,'error'=>'Invalid security token.'], 403);
    $input = json_decode((string)file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    $idea = mb_substr(trim((string)($input['idea'] ?? '')), 0, 700);
    $style = mb_substr(trim((string)($input['style'] ?? 'Fine-line blackwork')), 0, 80);
    $placement = mb_substr(trim((string)($input['placement'] ?? 'Outer forearm')), 0, 80);
    $composition = mb_substr(trim((string)($input['composition'] ?? 'Centered vertical emblem')), 0, 100);
    $lineWeight = mb_substr(trim((string)($input['line_weight'] ?? 'Balanced transfer-ready hierarchy')), 0, 100);
    $detail = mb_substr(trim((string)($input['detail'] ?? 'High detail with controlled open skin breaks')), 0, 120);
    $uploadedImage = trim((string)($input['stencil_image'] ?? ''));
    if ($uploadedImage !== '') {
        if (!preg_match('#^data:(image/(?:png|jpeg|webp));base64,(.+)$#s', $uploadedImage, $uploadMatch)) {
            stencilJson(['ok'=>false,'error'=>'Upload a PNG, JPG or WebP stencil image.'], 422);
        }
        $uploadedBytes = base64_decode($uploadMatch[2], true);
        $uploadInfo = is_string($uploadedBytes) ? @getimagesizefromstring($uploadedBytes) : false;
        if (!is_string($uploadedBytes) || strlen($uploadedBytes) > 15 * 1024 * 1024 || $uploadInfo === false) {
            stencilJson(['ok'=>false,'error'=>'The uploaded stencil image is invalid or too large.'], 422);
        }
        $uploadMime = (string)($uploadInfo['mime'] ?? '');
        if ($uploadMime !== 'image/png') {
            if (!function_exists('imagecreatefromstring')) {
                stencilJson(['ok'=>false,'error'=>'PHP GD is required to convert uploaded JPG or WebP artwork to PNG.'], 503);
            }
            $uploadCanvas = @imagecreatefromstring($uploadedBytes);
            if ($uploadCanvas === false) stencilJson(['ok'=>false,'error'=>'The uploaded stencil image could not be decoded.'], 422);
            ob_start();
            imagepng($uploadCanvas, null, 9);
            $converted = ob_get_clean();
            imagedestroy($uploadCanvas);
            if (!is_string($converted) || $converted === '') stencilJson(['ok'=>false,'error'=>'The uploaded stencil could not be converted to PNG.'], 500);
            $uploadedBytes = $converted;
        }
        stencilJson([
            'ok'=>true,
            'image'=>'data:image/png;base64,'.base64_encode($uploadedBytes),
            'render_token'=>stencilRenderToken($uploadedBytes),
            'model'=>'Approved artist upload',
            'provider'=>'upload',
            'quality'=>'source',
            'size'=>(string)$uploadInfo[0].'×'.(string)$uploadInfo[1],
        ]);
    }
    if (mb_strlen($idea) < 8) stencilJson(['ok'=>false,'error'=>'Describe the stencil concept in a little more detail.'], 422);
    if (!function_exists('curl_init')) stencilJson(['ok'=>false,'error'=>'The server cURL extension is required.'], 503);
    $prompt = <<<PROMPT
Create one original, premium tattoo stencil master suitable for a professional artist.

DESIGN BRIEF
- Concept: {$idea}
- Tattoo style: {$style}
- Intended body placement: {$placement}
- Composition: {$composition}
- Line-weight plan: {$lineWeight}
- Detail density: {$detail}

ART DIRECTION
Build a strong readable silhouette first, then intentional internal detail. Follow the natural anatomy and visual flow of the stated placement. Use confident black transfer lines with a deliberate hierarchy: bold structural contours, medium secondary forms, and restrained fine detail. Preserve generous, purposeful negative space and open skin breaks so the design remains readable after transfer and aging. Keep the focal point unmistakable. Make every ornamental element structurally connected and tattooable. Use clean symmetry only when the concept calls for it; otherwise use balanced organic flow.

OUTPUT REQUIREMENTS
Return a single isolated vertical stencil on a pure white background. Crisp black linework only. No skin, body, person, studio scene, paper texture, mockup, frame, border, crop marks, typography, letters, numbers, signature, logo, watermark, color, gray wash, soft shading, drop shadow, glow, or photographic rendering. Keep the entire design inside the canvas with comfortable white margins. The result must look like a high-end transfer-ready master an experienced tattoo artist can print, size, and refine.
PROMPT;
    $openAiKey = trim((string)beyond_ai_config('api_key', ''));
    $googleKey = trim((string)beyond_ai_config('google_image_key', ''));
    $errors = [];
    if ($openAiKey !== '' && !str_contains($openAiKey, 'YOUR_')) {
        $body = json_encode(['model'=>'gpt-image-2','prompt'=>$prompt,'size'=>'1024x1536','quality'=>'high','output_format'=>'png','background'=>'opaque'], JSON_THROW_ON_ERROR);
        $curl = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>180,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$openAiKey,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>$body]);
        $raw = curl_exec($curl); $curlError = curl_error($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        $response = is_string($raw) ? json_decode($raw, true) : null;
        $image = is_array($response) ? (string)($response['data'][0]['b64_json'] ?? '') : '';
        $decodedImage = $image !== '' ? base64_decode($image, true) : false;
        if ($status >= 200 && $status < 300 && is_string($decodedImage)) {
            stencilJson([
                'ok'=>true,
                'image'=>'data:image/png;base64,'.$image,
                'render_token'=>stencilRenderToken($decodedImage),
                'model'=>'gpt-image-2',
                'provider'=>'openai',
                'quality'=>'high',
                'size'=>'1024x1536',
                'usage'=>$response['usage']??null,
            ]);
        }
        $errors[] = is_array($response) ? (string)($response['error']['message'] ?? 'OpenAI image generation failed.') : ($curlError ?: 'OpenAI image generation failed.');
    }
    if ($googleKey !== '' && !str_contains($googleKey, 'YOUR_')) {
        $model = trim((string)beyond_ai_config('google_image_model', 'gemini-3.1-flash-image')) ?: 'gemini-3.1-flash-image';
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $model)) throw new RuntimeException('The configured Google image model is invalid.');
        $body = json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['responseModalities'=>['TEXT','IMAGE'],'imageConfig'=>['aspectRatio'=>'2:3']]], JSON_THROW_ON_ERROR);
        $curl = curl_init('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent');
        curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>180,CURLOPT_HTTPHEADER=>['x-goog-api-key: '.$googleKey,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>$body]);
        $raw = curl_exec($curl); $curlError = curl_error($curl); $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        $response = is_string($raw) ? json_decode($raw, true) : null; $image=''; $mime='image/png';
        foreach ((array)($response['candidates'][0]['content']['parts'] ?? []) as $part) { if (!empty($part['inlineData']['data'])) { $image=(string)$part['inlineData']['data']; $mime=(string)($part['inlineData']['mimeType']??'image/png'); break; } }
        if ($status >= 200 && $status < 300 && $image !== '' && ($bytes=base64_decode($image, true)) !== false) {
            // Publishing expects a PNG. Normalize Google's JPEG/WebP output
            // when GD is available so preview and publishing use one format.
            if ($mime !== 'image/png' && function_exists('imagecreatefromstring')) {
                $canvas=@imagecreatefromstring($bytes);
                if ($canvas !== false) { ob_start(); imagepng($canvas, null, 9); $png=ob_get_clean(); imagedestroy($canvas); if (is_string($png) && $png!=='') { $image=base64_encode($png); $mime='image/png'; } }
            }
            if ($mime !== 'image/png') throw new RuntimeException('Google returned an image, but PHP GD is required to convert it to PNG for publishing.');
            stencilJson(['ok'=>true,'image'=>'data:image/png;base64,'.$image,'render_token'=>stencilRenderToken((string)base64_decode($image, true)),'model'=>$model,'provider'=>'google']);
        }
        $errors[] = is_array($response) ? (string)($response['error']['message'] ?? 'Google Imagen generation failed.') : ($curlError ?: 'Google Imagen generation failed.');
    }
    if (!$errors) throw new RuntimeException('Add an OpenAI or Google Imagen API key in protected Site Settings.');
    throw new RuntimeException('Image providers failed: '.implode(' | ', $errors));
} catch (Throwable $error) {
    error_log('Stencil generation failed: '.$error->getMessage());
    stencilJson(['ok'=>false,'error'=>$error->getMessage()], 400);
}
