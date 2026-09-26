<?php
declare(strict_types=1);

if (!function_exists('studio_safe_json_encode')) {
    function studio_safe_json_encode(array $payload)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Could not encode narration request.');
        }
        return $json;
    }
}

require_once __DIR__ . '/NarrationProvider.php';

final class ElevenLabsProvider implements NarrationProvider
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function name(): string
    {
        return 'elevenlabs';
    }

    public function voices(string $language = ''): array
    {
        $configured = (array)($this->config['voices'] ?? []);
        $languages = $language !== '' ? [$language => ($configured[$language] ?? [])] : $configured;
        $voices = [];
        foreach ($languages as $locale => $items) {
            if (is_string($items) && trim($items) !== '') {
                $voices[] = ['id' => trim($items), 'label' => $locale . ' configured voice', 'language' => (string)$locale, 'provider' => $this->name()];
                continue;
            }
            if (!is_array($items)) continue;
            foreach ($items as $id => $label) {
                if (is_int($id)) {
                    $id = (string)$label;
                    $label = $locale . ' voice';
                }
                if (trim((string)$id) === '') continue;
                $voices[] = ['id' => (string)$id, 'label' => (string)$label, 'language' => (string)$locale, 'provider' => $this->name()];
            }
        }
        return $voices;
    }

    public function generate(array $request): array
    {
        $apiKey = trim((string)($this->config['api_key'] ?? ''));
        if ($apiKey === '') {
            throw new NarrationProviderException('ElevenLabs narration is not configured.', 'provider_not_configured');
        }
        if (!function_exists('curl_init')) {
            throw new NarrationProviderException('The cURL extension is required.', 'server_http_client_unavailable');
        }

        $language = trim((string)($request['language'] ?? ''));
        $availableVoices = $this->voices($language);
        if (!$availableVoices) {
            throw new NarrationProviderException('No ElevenLabs voice is configured for this language.', 'provider_not_configured');
        }

        $requestedVoice = trim((string)($request['voice'] ?? ''));
        $allowedIds = array_column($availableVoices, 'id');
        if ($requestedVoice === '' || (!in_array($requestedVoice, $allowedIds, true) && !empty($request['_is_fallback']))) {
            $requestedVoice = (string)$availableVoices[0]['id'];
        }
        if (!in_array($requestedVoice, $allowedIds, true)) {
            throw new NarrationProviderException('The selected ElevenLabs voice is invalid.', 'invalid_voice');
        }

        $model = trim((string)($this->config['model'] ?? 'eleven_multilingual_v2'));
        if ($model === '') $model = 'eleven_multilingual_v2';
        $body = ['text' => (string)$request['text'], 'model_id' => $model];
        $languageCode = strtolower(substr($language, 0, 2));
        // ElevenLabs documents language_code as unsupported by multilingual_v2.
        if ($languageCode !== '' && $model !== 'eleven_multilingual_v2') $body['language_code'] = $languageCode;

        $endpoint = rtrim((string)($this->config['endpoint'] ?? 'https://api.elevenlabs.io/v1/text-to-speech'), '/');
        $outputFormat = rawurlencode((string)($this->config['output_format'] ?? 'mp3_44100_128'));
        $url = $endpoint . '/' . rawurlencode($requestedVoice) . '?output_format=' . $outputFormat;
        $perform = static function (array $payload) use ($url, $apiKey): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => ['xi-api-key: ' . $apiKey, 'Content-Type: application/json', 'Accept: audio/mpeg'],
                CURLOPT_POSTFIELDS => studio_safe_json_encode($payload),
            ]);
            $content = curl_exec($ch);
            $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            return [$content, $httpStatus, $error, $errno];
        };

        [$audio, $status, $curlError, $curlNumber] = $perform($body);

        // Some accounts/voices reject v3 even though voice listing succeeds. Retry once
        // with ElevenLabs' stable multilingual model and without language_code.
        if (($status === 400 || $status === 422) && $model !== 'eleven_multilingual_v2' && strtolower(substr($language, 0, 2)) !== 'he') {
            $retryBody = ['text' => (string)$request['text'], 'model_id' => 'eleven_multilingual_v2'];
            [$retryAudio, $retryStatus, $retryError, $retryNumber] = $perform($retryBody);
            if ($retryStatus >= 200 && $retryStatus < 300) {
                $audio = $retryAudio;
                $status = $retryStatus;
                $curlError = $retryError;
                $curlNumber = $retryNumber;
                $model = 'eleven_multilingual_v2';
            } elseif ($retryStatus !== 0) {
                $audio = $retryAudio;
                $status = $retryStatus;
                $curlError = $retryError;
                $curlNumber = $retryNumber;
            }
        }

        if ($curlNumber !== 0) {
            error_log('ElevenLabs narration network failure: ' . $curlNumber . ' ' . $curlError);
            throw new NarrationProviderException('ElevenLabs narration is temporarily unavailable.', 'provider_network_error', true);
        }
        if ($status < 200 || $status >= 300) {
            $detail = '';
            if (is_string($audio) && $audio !== '') {
                $decoded = json_decode($audio, true);
                if (is_array($decoded)) {
                    $candidate = $decoded['detail']['message'] ?? $decoded['detail']['status'] ?? $decoded['message'] ?? '';
                    if (is_string($candidate)) $detail = trim($candidate);
                }
            }
            error_log('ElevenLabs narration HTTP failure: ' . $status . ($detail !== '' ? ' ' . $detail : ''));
            $temporary = $status === 408 || $status === 409 || $status === 425 || $status === 429 || $status >= 500;
            $code = $status === 401 ? 'invalid_api_key' : ($status === 404 ? 'invalid_voice' : ($status === 429 ? 'provider_rate_limited' : ($temporary ? 'provider_temporary_failure' : 'provider_request_rejected')));
            $message = $status === 401 ? 'ElevenLabs rejected the API key.' : ($status === 404 ? 'ElevenLabs could not find the selected voice.' : ($status === 429 ? 'ElevenLabs rate limit or plan quota reached.' : 'ElevenLabs narration request failed.'));
            if ($detail !== '') $message .= ' ' . $detail;
            throw new NarrationProviderException($message, $code, $temporary, $status);
        }
        if (!is_string($audio) || strlen($audio) < 128) {
            throw new NarrationProviderException('ElevenLabs returned invalid audio.', 'invalid_provider_response', true);
        }

        return [
            'success' => true,
            'provider' => $this->name(),
            'voice' => $requestedVoice,
            'format' => 'mp3',
            'audio_content' => $audio,
            'duration' => null,
        ];
    }
}
