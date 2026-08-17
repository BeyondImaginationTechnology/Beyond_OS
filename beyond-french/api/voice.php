<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/narration/NarrationApi.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $payload = json_decode((string)file_get_contents('php://input'), true);
    $text = trim((string)($payload['text'] ?? ''));
    $locale = trim((string)($payload['locale'] ?? ''));
    $allowed = ['fr-FR', 'fr-CA', 'es-ES', 'ht-HT', 'en-JM', 'en-US'];
    $textLength = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($text === '' || $textLength > 300 || !in_array($locale, $allowed, true)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $cacheDir = beyond_private_root() . '/cache/voices';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0700, true);
    $providerOrder = ['azure', 'openai', 'elevenlabs'];
    $voicePlan = [];
    foreach ($providerOrder as $providerName) {
        try {
            $provider = narration_service()->provider($providerName);
            $voices = $provider->voices($locale);
            $voiceId = public_voice_id($providerName, $locale, $voices);
            if ($voiceId !== '') {
                $voicePlan[] = ['provider' => $providerName, 'voice' => $voiceId];
            }
        } catch (Throwable $error) {
            error_log('Public voice provider setup failed for ' . $providerName . ': ' . $error->getMessage());
        }
    }
    if (!$voicePlan) {
        http_response_code(503);
        echo json_encode(['error' => 'No premium voice provider is configured']);
        exit;
    }

    $key = hash('sha256', implode(',', array_map(static fn($item): string => $item['provider'] . ':' . $item['voice'], $voicePlan)) . '|' . $locale . '|' . preg_replace('/\s+/u', ' ', $text));
    $cache = $cacheDir . '/' . $key . '.mp3';
    $usedProvider = '';
    $usedVoice = '';

    if (!is_file($cache) || filesize($cache) < 128) {
        $audio = '';
        $lastError = null;
        foreach ($voicePlan as $item) {
            try {
                $result = narration_service()->generate($item['provider'], [
                    'provider' => $item['provider'],
                    'voice' => $item['voice'],
                    'language' => $locale,
                    'text' => $text,
                    'instructions' => 'Clear, warm, natural language-learning pronunciation.',
                    'speed' => isset($payload['speed']) && is_numeric($payload['speed']) ? max(0.25, min(4.0, (float)$payload['speed'])) : 0.95,
                    'format' => 'mp3',
                ]);
                $audio = (string)($result['audio_content'] ?? '');
                $usedProvider = (string)($result['provider'] ?? $item['provider']);
                $usedVoice = (string)($result['voice'] ?? $item['voice']);
                break;
            } catch (Throwable $error) {
                $lastError = $error;
                error_log('Public voice provider ' . $item['provider'] . ' failed: ' . $error->getMessage());
            }
        }
        if (!narration_valid_mp3($audio)) {
            if ($lastError instanceof Throwable) throw $lastError;
            throw new NarrationProviderException('Generated audio failed MP3 validation.', 'invalid_audio_file', true);
        }
        file_put_contents($cache, $audio, LOCK_EX);
    }

    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($cache));
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    if ($usedProvider !== '') header('X-Narration-Provider: ' . preg_replace('/[^A-Za-z0-9_-]/', '', $usedProvider));
    if ($usedVoice !== '') header('X-Narration-Voice: ' . preg_replace('/[^A-Za-z0-9._-]/', '', $usedVoice));
    readfile($cache);
    exit;
} catch (Throwable $error) {
    error_log('Public premium voice failed: ' . $error->getMessage());
    http_response_code($error instanceof NarrationProviderException && $error->errorCode() === 'provider_not_configured' ? 503 : 502);
    echo json_encode(['error' => 'Voice service unavailable']);
}

function public_voice_id(string $provider, string $locale, array $voices): string
{
    if ($provider === 'openai') {
        $configured = trim((string)beyond_config('narration.openai.voices.' . $locale, beyond_config('voice.openai_voice', 'coral')));
        return $configured !== '' ? $configured : 'coral';
    }
    $first = trim((string)($voices[0]['id'] ?? ''));
    if ($first !== '') return $first;
    if ($provider === 'azure') {
        return 'en-US-JennyMultilingualNeural';
    }
    return '';
}
