<?php
declare(strict_types=1);
require_once __DIR__ . '/../../ai/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
$challenge = bin2hex(random_bytes(18));
$expires = time() + 180;
$_SESSION['needle_bot_guest_challenge'] = ['challenge' => $challenge, 'expires' => $expires, 'difficulty' => 16, 'used' => false];
echo json_encode(['challenge' => $challenge, 'expires' => $expires, 'difficulty' => 16], JSON_UNESCAPED_SLASHES);
