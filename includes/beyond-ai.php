<?php
declare(strict_types=1);

function beyond_ai_config(string $key, $default = null)
{
    $envMap = [
        'api_key' => 'OPENAI_API_KEY',
        'azure_image_key' => 'AZURE_IMAGE_KEY',
        'azure_image_endpoint' => 'AZURE_IMAGE_ENDPOINT',
        'azure_image_model' => 'AZURE_IMAGE_MODEL',
        'google_image_key' => 'GEMINI_API_KEY',
        'google_image_model' => 'BEYOND_GOOGLE_IMAGE_MODEL',
        'quick_model' => 'BEYOND_AI_QUICK_MODEL',
        'advanced_model' => 'BEYOND_AI_ADVANCED_MODEL',
    ];
    if (isset($envMap[$key])) {
        $value = getenv($envMap[$key]);
        if (is_string($value) && trim($value) !== '') return trim($value);
    }
    $paths = [
        'api_key' => ['ai.openai.api_key', 'narration.openai.api_key'],
        'azure_image_key' => ['ai.azure_image.api_key', 'ai.azure.image_api_key', 'azure.image.api_key'],
        'azure_image_endpoint' => ['ai.azure_image.endpoint', 'ai.azure.image_endpoint', 'azure.image.endpoint'],
        'azure_image_model' => ['ai.azure_image.model', 'ai.azure.image_model', 'azure.image.model'],
        'azure_image_width' => ['ai.azure_image.width', 'ai.azure.image_width'],
        'azure_image_height' => ['ai.azure_image.height', 'ai.azure.image_height'],
        'google_image_key' => ['ai.google.imagen_api_key', 'ai.google.api_key', 'ai.gemini.api_key', 'google.imagen.api_key'],
        'google_image_model' => ['ai.google.imagen_model', 'ai.google.image_model'],
        'quick_model' => ['ai.openai.quick_model'],
        'advanced_model' => ['ai.openai.advanced_model'],
        'quick_input_per_million' => ['ai.pricing.quick_input_per_million'],
        'quick_output_per_million' => ['ai.pricing.quick_output_per_million'],
        'advanced_input_per_million' => ['ai.pricing.advanced_input_per_million'],
        'advanced_output_per_million' => ['ai.pricing.advanced_output_per_million'],
        'daily_request_limit' => ['ai.daily_request_limit'],
    ];
    foreach ($paths[$key] ?? [] as $path) {
        try {
            $value = beyond_config($path, null);
            if ($value !== null && $value !== '') return $value;
        } catch (Throwable $e) {}
    }
    return $default;
}

function beyond_ai_usage_file(): string
{
    $dir = beyond_private_root() . '/data';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir . '/beyond-ai-usage.json';
}

function beyond_ai_read_usage(): array
{
    $file = beyond_ai_usage_file();
    if (!is_file($file)) return [];
    $decoded = json_decode((string)@file_get_contents($file), true);
    return is_array($decoded) ? $decoded : [];
}

function beyond_ai_record_usage(int $inputTokens, int $outputTokens, float $cost): array
{
    $date = (new DateTimeImmutable('now', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    $usage = beyond_ai_read_usage();
    $today = $usage[$date] ?? ['requests'=>0,'input_tokens'=>0,'output_tokens'=>0,'estimated_cost'=>0.0];
    $today['requests'] = (int)$today['requests'] + 1;
    $today['input_tokens'] = (int)$today['input_tokens'] + max(0, $inputTokens);
    $today['output_tokens'] = (int)$today['output_tokens'] + max(0, $outputTokens);
    $today['estimated_cost'] = round((float)$today['estimated_cost'] + max(0, $cost), 6);
    $usage[$date] = $today;
    foreach (array_keys($usage) as $key) {
        if ($key < (new DateTimeImmutable('-45 days'))->format('Y-m-d')) unset($usage[$key]);
    }
    @file_put_contents(beyond_ai_usage_file(), json_encode($usage, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $today;
}

function beyond_ai_today_usage(): array
{
    $date = (new DateTimeImmutable('now', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    return beyond_ai_read_usage()[$date] ?? ['requests'=>0,'input_tokens'=>0,'output_tokens'=>0,'estimated_cost'=>0.0];
}

function beyond_ai_estimate_cost(string $mode, int $inputTokens, int $outputTokens): float
{
    $prefix = $mode === 'advanced' ? 'advanced' : 'quick';
    $inputRate = (float)beyond_ai_config($prefix.'_input_per_million', 0);
    $outputRate = (float)beyond_ai_config($prefix.'_output_per_million', 0);
    return (($inputTokens / 1000000) * $inputRate) + (($outputTokens / 1000000) * $outputRate);
}

function beyond_ai_extract_text(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) return trim($response['output_text']);
    $parts = [];
    foreach (($response['output'] ?? []) as $item) {
        if (($item['type'] ?? '') !== 'message') continue;
        foreach (($item['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) $parts[] = (string)$content['text'];
        }
    }
    return trim(implode("\n", $parts));
}
