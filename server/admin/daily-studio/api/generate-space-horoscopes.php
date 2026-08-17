<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/config/bootstrap.php';
require_once dirname(__DIR__, 4) . '/includes/beyond-ai.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function spaceJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function spaceSigns(): array
{
    return [
        ['slug'=>'aries','name'=>'Aries','symbol'=>'♈','season'=>'Mar 21 - Apr 19'],
        ['slug'=>'taurus','name'=>'Taurus','symbol'=>'♉','season'=>'Apr 20 - May 20'],
        ['slug'=>'gemini','name'=>'Gemini','symbol'=>'♊','season'=>'May 21 - Jun 20'],
        ['slug'=>'cancer','name'=>'Cancer','symbol'=>'♋','season'=>'Jun 21 - Jul 22'],
        ['slug'=>'leo','name'=>'Leo','symbol'=>'♌','season'=>'Jul 23 - Aug 22'],
        ['slug'=>'virgo','name'=>'Virgo','symbol'=>'♍','season'=>'Aug 23 - Sep 22'],
        ['slug'=>'libra','name'=>'Libra','symbol'=>'♎','season'=>'Sep 23 - Oct 22'],
        ['slug'=>'scorpio','name'=>'Scorpio','symbol'=>'♏','season'=>'Oct 23 - Nov 21'],
        ['slug'=>'sagittarius','name'=>'Sagittarius','symbol'=>'♐','season'=>'Nov 22 - Dec 21'],
        ['slug'=>'capricorn','name'=>'Capricorn','symbol'=>'♑','season'=>'Dec 22 - Jan 19'],
        ['slug'=>'aquarius','name'=>'Aquarius','symbol'=>'♒','season'=>'Jan 20 - Feb 18'],
        ['slug'=>'pisces','name'=>'Pisces','symbol'=>'♓','season'=>'Feb 19 - Mar 20'],
    ];
}

function spaceDate(string $value): DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/Vancouver'));
    if (!$date || $date->format('Y-m-d') !== $value) {
        throw new RuntimeException('Choose a valid publish date.');
    }
    return $date;
}

function cleanText(string $value, int $limit): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $limit);
}

function fallbackHoroscopes(DateTimeImmutable $date, string $theme): array
{
    $signs = spaceSigns();
    $tones = ['steady','magnetic','clear','restorative','bold','patient','creative','grounded','curious','focused','open-hearted','disciplined'];
    $moods = ['Centered & Brave','Grounded & Receptive','Bright & Curious','Tender & Clear','Inspired & Bold','Precise & Calm','Balanced & Magnetic','Deep & Renewed','Adventurous & Honest','Focused & Patient','Original & Awake','Dreamy & Guided'];
    $items = [];
    foreach ($signs as $index => $sign) {
        $tone = $tones[$index % count($tones)];
        $items[] = [
            'slug'=>$sign['slug'],
            'sign'=>$sign['name'],
            'symbol'=>$sign['symbol'],
            'season'=>$sign['season'],
            'date'=>$date->format('Y-m-d'),
            'headline'=>$theme !== '' ? strtoupper($theme) : 'COSMIC WEATHER',
            'paragraphs'=>[
                "Your {$tone} instincts are asking for room today, dear {$sign['name']}. Move slowly enough to notice the opening that has been waiting in plain sight.",
                'A conversation, small choice, or quiet adjustment can shift the whole rhythm of the day. Protect your focus and let the right people meet you halfway.',
                'Choose restoration over proving yourself. What settles inside you now becomes tomorrow\'s confidence.',
            ],
            'mood'=>$moods[$index],
            'source'=>'Beyond Space original fallback',
            'source_url'=>'',
        ];
    }
    return $items;
}

function astrologySourceSeeds(): array
{
    if (!function_exists('curl_init')) return [];
    $seeds = [];
    foreach (spaceSigns() as $sign) {
        $url = 'https://www.astrology.com/horoscope/daily/' . $sign['slug'] . '.html';
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>6,
            CURLOPT_TIMEOUT=>14,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_USERAGENT=>'Beyond-Space-Horoscope-Generator/1.0',
        ]);
        $html = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (!is_string($html) || $status < 200 || $status >= 300) continue;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        $needle = $sign['name'] . ' Daily Horoscope';
        $position = stripos($text, $needle);
        if ($position === false) continue;
        $chunk = mb_substr($text, $position, 1300);
        $chunk = preg_replace('/Explore More.*$/iu', '', $chunk) ?? $chunk;
        $chunk = preg_replace('/More Horoscopes.*$/iu', '', $chunk) ?? $chunk;
        $chunk = cleanText($chunk, 900);
        if ($chunk !== '') {
            $seeds[$sign['slug']] = ['source'=>'Astrology.com daily horoscope', 'source_url'=>$url, 'text'=>$chunk];
        }
    }
    return $seeds;
}

function aiHoroscopes(DateTimeImmutable $date, string $theme, array $sourceSeeds = []): array
{
    $key = trim((string)beyond_ai_config('api_key', ''));
    if ($key === '' || str_contains($key, 'YOUR_') || !function_exists('curl_init')) {
        return [];
    }
    $model = trim((string)beyond_ai_config('quick_model', 'gpt-4o-mini')) ?: 'gpt-4o-mini';
    if (!preg_match('/^[a-zA-Z0-9._:-]+$/', $model)) {
        return [];
    }
    $signNames = implode(', ', array_map(fn($sign) => $sign['name'], spaceSigns()));
    $themeLine = $theme !== '' ? "Creative theme: {$theme}." : 'Creative theme: refined daily astrology, hopeful and practical.';
    $sourceLine = $sourceSeeds ? "Use these source notes only as topical inputs; do not copy their wording:\n" . json_encode($sourceSeeds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'No external source notes are available today.';
    $prompt = <<<PROMPT
Write original entertainment-only daily astrology copy for Beyond Space.
Date: {$date->format('l, F j, Y')}.
Signs: {$signNames}.
{$themeLine}
{$sourceLine}

Return strict JSON only:
{"items":[{"sign":"Aries","paragraphs":["...","...","..."],"mood":"..."}]}

Rules:
- Include exactly 12 items, one per sign in the listed order.
- Three short paragraphs per sign, 18 to 30 words each.
- Do not copy, quote, or imitate Astrology.com or any named publication.
- No medical, legal, financial, or deterministic claims.
- Tone: premium, warm, mystical, grounded, social-media ready.
PROMPT;

    $body = json_encode([
        'model' => $model,
        'messages' => [
            ['role'=>'system','content'=>'You write concise original astrology entertainment copy as valid JSON.'],
            ['role'=>'user','content'=>$prompt],
        ],
        'response_format' => ['type'=>'json_object'],
        'temperature' => 0.85,
    ], JSON_THROW_ON_ERROR);
    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($curl, [
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>12,
        CURLOPT_TIMEOUT=>90,
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>$body,
    ]);
    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $response = is_string($raw) ? json_decode($raw, true) : null;
    $content = is_array($response) ? (string)($response['choices'][0]['message']['content'] ?? '') : '';
    $decoded = $content !== '' ? json_decode($content, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($decoded) || !is_array($decoded['items'] ?? null)) {
        return [];
    }

    $signs = spaceSigns();
    $bySign = [];
    foreach ((array)$decoded['items'] as $item) {
        $bySign[strtolower((string)($item['sign'] ?? ''))] = $item;
    }
    $items = [];
    foreach ($signs as $sign) {
        $rawItem = (array)($bySign[strtolower($sign['name'])] ?? []);
        $paragraphs = array_values(array_filter(array_map(fn($line) => cleanText((string)$line, 220), (array)($rawItem['paragraphs'] ?? []))));
        while (count($paragraphs) < 3) {
            $paragraphs[] = fallbackHoroscopes($date, $theme)[0]['paragraphs'][count($paragraphs)];
        }
        $items[] = [
            'slug'=>$sign['slug'],
            'sign'=>$sign['name'],
            'symbol'=>$sign['symbol'],
            'season'=>$sign['season'],
            'date'=>$date->format('Y-m-d'),
            'headline'=>$theme !== '' ? strtoupper($theme) : 'COSMIC WEATHER',
            'paragraphs'=>array_slice($paragraphs, 0, 3),
            'mood'=>cleanText((string)($rawItem['mood'] ?? 'Open & Grounded'), 54),
            'source'=>(string)($sourceSeeds[$sign['slug']]['source'] ?? 'Beyond Space original'),
            'source_url'=>(string)($sourceSeeds[$sign['slug']]['source_url'] ?? ''),
        ];
    }
    return $items;
}

function saveSpaceEvents(array $items, DateTimeImmutable $date): int
{
    $db = DailyStudio::db();
    $saved = 0;
    $select = $db->prepare("SELECT id FROM events WHERE channel_key='daily_space' AND content_type='daily_horoscope' AND title=? AND scheduled_at=? ORDER BY id DESC LIMIT 1");
    $insert = $db->prepare("INSERT INTO events(channel_key,title,content_type,content_json,scheduled_at,timezone,status,requires_approval,created_by,updated_at) VALUES('daily_space',?,?,?,?,?,'draft',1,?,CURRENT_TIMESTAMP)");
    $update = $db->prepare("UPDATE events SET content_json=?, timezone=?, status='draft', updated_at=CURRENT_TIMESTAMP WHERE id=?");
    foreach ($items as $item) {
        $title = 'Daily Horoscope: ' . (string)$item['sign'];
        $scheduledAt = $date->format('Y-m-d') . ' 08:00:00';
        $content = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $select->execute([$title, $scheduledAt]);
        $id = (int)$select->fetchColumn();
        if ($id > 0) {
            $update->execute([$content, 'America/Vancouver', $id]);
        } else {
            $insert->execute([$title, 'daily_horoscope', $content, $scheduledAt, 'America/Vancouver', (int)($_SESSION['user_id'] ?? 0)]);
        }
        $saved++;
    }
    return $saved;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        spaceJson(['ok'=>false, 'error'=>'POST required.'], 405);
    }
    if (!Auth::check()) {
        spaceJson(['ok'=>false, 'error'=>'Administrator access required.'], 403);
    }
    $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (
        empty($_SESSION['verse_generator_csrf'])
        || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)
    ) {
        spaceJson(['ok'=>false, 'error'=>'Reload the generator and try again.'], 419);
    }
    $input = json_decode((string)file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new RuntimeException('Invalid horoscope request.');
    $date = spaceDate((string)($input['publish_date'] ?? date('Y-m-d')));
    $theme = cleanText((string)($input['theme'] ?? ''), 90);
    $sourceMode = cleanText((string)($input['source_mode'] ?? 'astrology-com'), 40);
    $sourceSeeds = $sourceMode === 'astrology-com' ? astrologySourceSeeds() : [];
    $items = aiHoroscopes($date, $theme, $sourceSeeds);
    $provider = $items ? ($sourceSeeds ? 'openai-with-astrology-source' : 'openai') : 'local-fallback';
    if (!$items) $items = fallbackHoroscopes($date, $theme);
    $saved = !empty($input['save_drafts']) ? saveSpaceEvents($items, $date) : 0;
    spaceJson(['ok'=>true, 'provider'=>$provider, 'date'=>$date->format('Y-m-d'), 'items'=>$items, 'saved'=>$saved]);
} catch (Throwable $error) {
    spaceJson(['ok'=>false, 'error'=>$error->getMessage()], 500);
}
