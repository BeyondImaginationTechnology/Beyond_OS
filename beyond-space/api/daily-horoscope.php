<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=600');

$date = (new DateTimeImmutable('today', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$dbPath = beyond_private_file('db/daily-studio.sqlite', 'daily-studio.sqlite');
$items = [];

if (is_file($dbPath)) {
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $query = $db->prepare("SELECT content_json FROM events WHERE channel_key='daily_space' AND content_type='daily_horoscope' AND status='published' AND date(scheduled_at)=? ORDER BY id ASC");
        $query->execute([$date]);
        foreach ($query->fetchAll() as $row) {
            $content = json_decode((string)$row['content_json'], true);
            if (is_array($content) && !empty($content['sign'])) {
                $items[] = $content;
            }
        }
    } catch (Throwable $error) {
        // The public app uses its built-in reading set when Daily Studio is unavailable.
    }
}

if (!$items) {
    $signs = [
        ['sign'=>'Aries','symbol'=>'♈','mood'=>'Brave focus'], ['sign'=>'Taurus','symbol'=>'♉','mood'=>'Steady calm'],
        ['sign'=>'Gemini','symbol'=>'♊','mood'=>'Bright curiosity'], ['sign'=>'Cancer','symbol'=>'♋','mood'=>'Gentle renewal'],
        ['sign'=>'Leo','symbol'=>'♌','mood'=>'Creative confidence'], ['sign'=>'Virgo','symbol'=>'♍','mood'=>'Clear purpose'],
        ['sign'=>'Libra','symbol'=>'♎','mood'=>'Balanced perspective'], ['sign'=>'Scorpio','symbol'=>'♏','mood'=>'Quiet insight'],
        ['sign'=>'Sagittarius','symbol'=>'♐','mood'=>'Open adventure'], ['sign'=>'Capricorn','symbol'=>'♑','mood'=>'Practical momentum'],
        ['sign'=>'Aquarius','symbol'=>'♒','mood'=>'Fresh ideas'], ['sign'=>'Pisces','symbol'=>'♓','mood'=>'Imaginative calm'],
    ];
    $openings = [
        'Choose one small direction and give it your full attention.',
        'Leave room for a useful surprise before you lock in a plan.',
        'A calm conversation can reveal the detail you were missing.',
        'Protect your energy and make space for one meaningful next step.',
        'Let curiosity lead, then turn the best idea into a simple action.',
        'Notice what is already working and build from there.',
        'Make the next choice with patience, clarity, and a little wonder.',
    ];
    $closings = [
        'Keep it light: this is a prompt for reflection, not a prediction.',
        'Share the encouragement, then stay grounded in your own judgment.',
        'A brief pause can be enough to reset the rest of your day.',
        'Trust the practical step in front of you before chasing the perfect one.',
        'Let today be about progress you can actually feel.',
    ];
    $seed = array_sum(array_map('ord', str_split($date)));
    foreach ($signs as $index => $sign) {
        $items[] = $sign + [
            'date' => $date,
            'source' => 'Beyond Space original',
            'paragraphs' => [
                $openings[($seed + $index) % count($openings)],
                $closings[($seed + ($index * 3)) % count($closings)],
            ],
            'disclaimer' => 'For entertainment and personal reflection; not scientific or professional advice.',
        ];
    }
}

echo json_encode(['ok'=>true, 'date'=>$date, 'items'=>$items], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
