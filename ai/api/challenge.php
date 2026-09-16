<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

// A short-lived, first-party proof-of-work challenge slows automated guest
// submissions without sending browser data to an external verification service.
$challenge = bin2hex(random_bytes(18));
$expires = time() + 180;
$_SESSION['jaguar_guest_challenge'] = ['challenge' => $challenge, 'expires' => $expires, 'difficulty' => 16, 'used' => false];
echo json_encode(['challenge' => $challenge, 'expires' => $expires, 'difficulty' => 16], JSON_UNESCAPED_SLASHES);
