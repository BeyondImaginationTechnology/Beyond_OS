<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require __DIR__ . '/../../includes/admin-check.php';
require __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required.']); exit; }
$csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['verse_generator_csrf']) || !hash_equals((string)$_SESSION['verse_generator_csrf'], $csrf)) { http_response_code(419); echo json_encode(['error'=>'Reload the generator and try again.']); exit; }
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) { http_response_code(400); echo json_encode(['error'=>'Invalid request.']); exit; }

$date = (string)($input['publish_date'] ?? '');
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
$locale = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($input['locale'] ?? 'en')) ?: 'en';
$translation = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($input['translation_code'] ?? 'KJV')) ?: 'KJV';
$templateStyle = (string)($input['template_style'] ?? 'forest');
$backgroundAssets = [
  'advanced' => '/assets/img/verse-cannabis-living-sanctuary-v1.png',
  'forest' => '/assets/img/verse-cannabis-modern-aurora-v1.png',
  'botanical' => '/assets/img/verse-cannabis-heritage-botanical-v1.png',
  'olive' => '/assets/dailybreath-login-background.webp?template=olive-sanctuary',
];
$backgroundAsset = $backgroundAssets[$templateStyle] ?? $backgroundAssets['forest'];
$heading = mb_substr(trim((string)($input['heading'] ?? 'VERSE OF THE DAY')), 0, 100);
$verse = trim((string)($input['verse_text'] ?? ''));
$reference = mb_substr(trim((string)($input['scripture_reference'] ?? '')), 0, 180);
if (!$dateObject || $dateObject->format('Y-m-d') !== $date || $verse === '' || mb_strlen($verse) > 2000 || $reference === '') { http_response_code(422); echo json_encode(['error'=>'Date, verse text, or reference is invalid.']); exit; }

$values = [
  'publish_date'=>$date,'locale'=>$locale,'translation_code'=>$translation,'heading'=>$heading,
  'verse_text'=>$verse,'scripture_reference'=>$reference,
  'weekday_label'=>mb_substr(trim((string)($input['weekday_label'] ?? '')),0,30),
  'date_label'=>mb_substr(trim((string)($input['date_label'] ?? '')),0,50),
  'footer_message'=>mb_substr(trim((string)($input['footer_message'] ?? '')),0,180),
  'background_asset_url'=>$backgroundAsset,
  'show_footer'=>!empty($input['show_footer'])?1:0,'show_frame'=>!empty($input['show_frame'])?1:0,
  'status'=>'draft','created_by'=>(int)($_SESSION['user_id'] ?? 0) ?: null,
];
$columns = implode(',', array_keys($values));
$placeholders = ':' . implode(',:', array_keys($values));
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'mysql') {
  $updates = 'translation_code=VALUES(translation_code),heading=VALUES(heading),verse_text=VALUES(verse_text),scripture_reference=VALUES(scripture_reference),weekday_label=VALUES(weekday_label),date_label=VALUES(date_label),footer_message=VALUES(footer_message),background_asset_url=VALUES(background_asset_url),show_footer=VALUES(show_footer),show_frame=VALUES(show_frame),status=VALUES(status),updated_at=CURRENT_TIMESTAMP';
  $pdo->prepare("INSERT INTO verse_day_posts ($columns) VALUES ($placeholders) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),$updates")->execute($values);
  $id = (int)$pdo->lastInsertId();
} else {
  $updates = 'translation_code=excluded.translation_code,heading=excluded.heading,verse_text=excluded.verse_text,scripture_reference=excluded.scripture_reference,weekday_label=excluded.weekday_label,date_label=excluded.date_label,footer_message=excluded.footer_message,background_asset_url=excluded.background_asset_url,show_footer=excluded.show_footer,show_frame=excluded.show_frame,status=excluded.status,updated_at=CURRENT_TIMESTAMP';
  $pdo->prepare("INSERT INTO verse_day_posts ($columns) VALUES ($placeholders) ON CONFLICT(publish_date,locale) DO UPDATE SET $updates")->execute($values);
  $find=$pdo->prepare('SELECT id FROM verse_day_posts WHERE publish_date=? AND locale=?');$find->execute([$date,$locale]);$id=(int)$find->fetchColumn();
}
try { log_activity($pdo, (int)$_SESSION['user_id'], 'verse_generator_save'); } catch (Throwable $e) {}
echo json_encode(['ok'=>true,'id'=>$id,'status'=>'draft']);
