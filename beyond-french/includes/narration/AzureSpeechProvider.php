<?php
declare(strict_types=1);

require_once __DIR__ . '/NarrationProvider.php';

final class AzureSpeechProvider implements NarrationProvider
{
    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function name(): string
    {
        return 'azure';
    }

    public function voices(string $language = ''): array
    {
        $configured = (array)($this->config['voices'] ?? []);
        $languages = $language !== '' ? [$language => ($configured[$language] ?? [])] : $configured;
        $voices = [];
        foreach ($languages as $locale => $items) {
            if (is_string($items) && trim($items) !== '') {
                $voices[] = ['id' => trim($items), 'label' => trim($items), 'language' => (string)$locale, 'provider' => $this->name()];
                continue;
            }
            if (!is_array($items)) continue;
            foreach ($items as $id => $label) {
                if (is_int($id)) {
                    $id = (string)$label;
                    $label = (string)$id;
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
        $region = strtolower(trim((string)($this->config['region'] ?? '')));
        if ($apiKey === '' || $region === '') {
            throw new NarrationProviderException('Azure Speech narration is not configured.', 'provider_not_configured');
        }
        if (!preg_match('/^[a-z0-9-]+$/', $region)) {
            throw new NarrationProviderException('Azure Speech region is invalid.', 'provider_not_configured');
        }
        if (!function_exists('curl_init')) {
            throw new NarrationProviderException('The cURL extension is required.', 'server_http_client_unavailable');
        }

        $language = trim((string)($request['language'] ?? ''));
        $availableVoices = $this->voices($language);
        if (!$availableVoices) {
            throw new NarrationProviderException('No Azure Speech voice is configured for this language.', 'provider_not_configured');
        }

        $requestedVoice = trim((string)($request['voice'] ?? ''));
        $allowedIds = array_column($availableVoices, 'id');
        if ($requestedVoice === '' || (!in_array($requestedVoice, $allowedIds, true) && !empty($request['_is_fallback']))) {
            $requestedVoice = (string)$availableVoices[0]['id'];
        }
        if (!in_array($requestedVoice, $allowedIds, true)) {
            throw new NarrationProviderException('The selected Azure Speech voice is invalid.', 'invalid_voice');
        }

        $text = htmlspecialchars((string)$request['text'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        // The voice prefix is the locale Azure expects in SSML.
        $speechLanguage = preg_match('/^([a-z]{2,3}-[A-Z]{2})-/', $requestedVoice, $match) ? $match[1] : $language;
        $safeLanguage = htmlspecialchars($speechLanguage, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $safeVoice = htmlspecialchars($requestedVoice, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $speed = max(0.25, min(4.0, (float)($request['speed'] ?? 1.0)));
        $relativeRate = ($speed - 1.0) * 100;
        $rate = ($relativeRate >= 0 ? '+' : '') . number_format($relativeRate, 0, '.', '') . '%';
        $ssml = '<speak version="1.0" xml:lang="' . $safeLanguage . '"><voice name="' . $safeVoice . '"><prosody rate="' . $rate . '">' . $text . '</prosody></voice></speak>';

        $url = 'https://' . $region . '.tts.speech.microsoft.com/cognitiveservices/v1';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 45),
            CURLOPT_HTTPHEADER => [
                'Ocp-Apim-Subscription-Key: ' . $apiKey,
                'Content-Type: application/ssml+xml',
                'X-Microsoft-OutputFormat: ' . (string)($this->config['output_format'] ?? 'audio-24khz-48kbitrate-mono-mp3'),
                'User-Agent: BeyondFrenchNarration',
                'Accept: audio/mpeg',
            ],
            CURLOPT_POSTFIELDS => $ssml,
        ]);

        $audio = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlNumber = curl_errno($ch);
        curl_close($ch);

        if ($curlNumber !== 0) {
            error_log('Azure Speech narration network failure: ' . $curlNumber . ' ' . $curlError);
            throw new NarrationProviderException('Azure Speech is temporarily unavailable.', 'provider_network_error', true);
        }
        if ($status < 200 || $status >= 300) {
            error_log('Azure Speech narration HTTP failure: ' . $status);
            $temporary = $status === 408 || $status === 409 || $status === 425 || $status === 429 || $status >= 500;
            $code = $status === 429 ? 'provider_rate_limited' : ($temporary ? 'provider_temporary_failure' : 'provider_request_rejected');
            throw new NarrationProviderException('Azure Speech narration request failed.', $code, $temporary, $status);
        }
        if (!is_string($audio) || strlen($audio) < 128) {
            throw new NarrationProviderException('Azure Speech returned invalid audio.', 'invalid_provider_response', true);
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
