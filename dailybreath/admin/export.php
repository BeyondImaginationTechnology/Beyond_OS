<?php
require __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['dailybreath_admin'])) { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="dailybreath-subscribers.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id','name','email','status','created_at']);
function dailybreath_csv_cell(mixed $value): string
{
    $cell = (string)$value;
    return preg_match('/^[\x00-\x20]*[=+\-@]/u', $cell) ? "'".$cell : $cell;
}
foreach (db()->query("SELECT id,name,email,status,created_at FROM dailybreath_subscribers ORDER BY created_at DESC") as $row) {
    fputcsv($out, array_map('dailybreath_csv_cell', array_values($row)));
}
