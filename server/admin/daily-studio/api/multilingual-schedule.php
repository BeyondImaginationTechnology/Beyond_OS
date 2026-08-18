<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
function multilingualScheduleResponse(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$parsed || $parsed->format('Y-m-d') !== $date) multilingualScheduleResponse(['ok'=>false,'error'=>'Choose a valid schedule date.'], 422);
$file = dirname(__DIR__, 4) . '/beyond-french/data/multilingual-lessons.json';
$items = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
if (!is_array($items)) multilingualScheduleResponse(['ok'=>false,'error'=>'The multilingual schedule is unavailable.'], 500);
$scheduled = [];
foreach ($items as $item) { $itemDate=trim((string)($item['date']??'')); if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$itemDate)) $scheduled[$itemDate]=$item; }
ksort($scheduled); $dates=array_keys($scheduled); $previous=null; $next=null;
foreach ($dates as $scheduledDate) { if ($scheduledDate<$date) $previous=$scheduledDate; if ($scheduledDate>$date) { $next=$scheduledDate; break; } }
$last=$dates?(string)end($dates):date('Y-m-d'); $nextAvailable=(new DateTimeImmutable($last))->modify('+1 day')->format('Y-m-d');
multilingualScheduleResponse(['ok'=>true,'date'=>$date,'item'=>$scheduled[$date]??null,'navigation'=>['previous'=>$previous,'today'=>date('Y-m-d'),'next'=>$next,'first'=>$dates[0]??null,'last'=>$dates?(string)end($dates):null,'next_available'=>$nextAvailable],'counts'=>['scheduled'=>count($scheduled),'fully_prerecorded'=>count(array_filter($scheduled,static fn(array $item):bool=>count((array)($item['audio_urls']??[]))===5))]]);
