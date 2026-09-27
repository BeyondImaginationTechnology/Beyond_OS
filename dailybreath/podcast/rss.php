<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/ecosystem.php';
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../includes/web-app.php';

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function dailybreath_rss_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$origin = 'https://beyondimagination.co.technology';
$feedUrl = $origin . '/dailybreath/podcast/rss.xml';
$episodes = [];

try {
    $pdo = beyond_db();
    dailybreath_ensure_content_table($pdo);
    dailybreath_ensure_audio_table($pdo);
    $query = $pdo->prepare("SELECT * FROM dailybreath_daily_content WHERE tradition='bible' AND locale='en' AND status='published' AND publish_date<=? ORDER BY publish_date DESC LIMIT 100");
    $today = (new DateTimeImmutable('now', new DateTimeZone('America/Vancouver')))->format('Y-m-d');
    $query->execute([$today]);
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $content) {
        $audio = dailybreath_published_audio($pdo, $content);
        if (!$audio) continue;
        $path = parse_url((string)$audio['audio_url'], PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/dailybreath/assets/audio/')) continue;
        $file = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . rawurldecode($path);
        if (!is_file($file) || !is_readable($file)) continue;

        $date = (string)$content['publish_date'];
        $dateTime = new DateTimeImmutable($date . ' 12:00:00', new DateTimeZone('America/Vancouver'));
        $title = 'Verse of the Day — ' . $dateTime->format('F j, Y');
        $description = trim((string)$content['reference']) . ' (World English Bible): ' . trim((string)$content['passage']);
        if (trim((string)$content['reflection']) !== '') $description .= ' ' . trim((string)$content['reflection']);
        $episodes[] = [
            'title'=>$title,
            'description'=>$description,
            'guid'=>'dailybreath-verse-' . $date,
            'pubDate'=>$dateTime->format(DATE_RSS),
            'url'=>$origin . $path,
            'length'=>(int)filesize($file),
            'link'=>$origin . '/dailybreath/daily.php?date=' . rawurlencode($date) . '&tradition=bible&lang=en',
        ];
        if (count($episodes) >= 30) break;
    }
} catch (Throwable $exception) {
    error_log('Daily Breath podcast feed: ' . $exception->getMessage());
}

// Keep the existing public sample episode available until new approved audio is ready.
if (!$episodes) {
    $legacy = __DIR__ . '/episodes/2026-09-25-daily-verse.wav';
    if (is_file($legacy) && is_readable($legacy)) {
        $episodes[] = [
            'title'=>'Verse of the Day — September 25, 2026',
            'description'=>'Psalm 121:7 (World English Bible): The LORD will keep you from all evil. He will keep your soul.',
            'guid'=>'dailybreath-verse-2026-09-25',
            'pubDate'=>'Fri, 25 Sep 2026 17:06:00 +0000',
            'url'=>$origin . '/dailybreath/podcast/episodes/2026-09-25-daily-verse.wav',
            'length'=>(int)filesize($legacy),
            'link'=>$origin . '/dailybreath/',
            'type'=>'audio/wav',
            'duration'=>'00:00:30',
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd">
  <channel>
    <title>Daily Breath: Verse of the Day</title>
    <link><?=dailybreath_rss_e($origin)?>/dailybreath/</link>
    <description>A quiet daily reading from the World English Bible, with a brief moment to breathe and reflect.</description>
    <language>en-us</language>
    <copyright>Scripture text: World English Bible (public domain). Audio: Daily Breath.</copyright>
    <webMaster>support@beyondimagination.co.technology (Daily Breath)</webMaster>
    <managingEditor>support@beyondimagination.co.technology (Daily Breath)</managingEditor>
    <image><url><?=dailybreath_rss_e($origin)?>/dailybreath/assets/icons/dailybreath-mark-v2.png</url><title>Daily Breath: Verse of the Day</title><link><?=dailybreath_rss_e($origin)?>/dailybreath/</link></image>
    <atom:link href="<?=dailybreath_rss_e($feedUrl)?>" rel="self" type="application/rss+xml" />
    <itunes:author>Daily Breath</itunes:author>
    <itunes:summary>A quiet daily reading from the World English Bible, with a brief moment to breathe and reflect.</itunes:summary>
    <itunes:owner><itunes:name>Daily Breath</itunes:name><itunes:email>support@beyondimagination.co.technology</itunes:email></itunes:owner>
    <itunes:image href="<?=dailybreath_rss_e($origin)?>/dailybreath/assets/icons/dailybreath-mark-v2.png" />
    <itunes:category text="Religion &amp; Spirituality"><itunes:category text="Christianity" /></itunes:category>
    <itunes:explicit>false</itunes:explicit>
<?php foreach ($episodes as $episode): ?>
    <item>
      <title><?=dailybreath_rss_e($episode['title'])?></title>
      <link><?=dailybreath_rss_e($episode['link'])?></link>
      <description><?=dailybreath_rss_e($episode['description'])?></description>
      <guid isPermaLink="false"><?=dailybreath_rss_e($episode['guid'])?></guid>
      <pubDate><?=dailybreath_rss_e($episode['pubDate'])?></pubDate>
      <enclosure url="<?=dailybreath_rss_e($episode['url'])?>" length="<?=dailybreath_rss_e($episode['length'])?>" type="<?=dailybreath_rss_e($episode['type'] ?? 'audio/mpeg')?>" />
<?php if (isset($episode['duration'])): ?><itunes:duration><?=dailybreath_rss_e($episode['duration'])?></itunes:duration>
<?php endif; ?>      <itunes:explicit>false</itunes:explicit>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
