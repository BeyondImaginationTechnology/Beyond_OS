<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/usage.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$signedIn = !empty($_SESSION['user_id']);
try {
    $identity = jaguar_usage_identity($signedIn);
    $usage = jaguar_usage_read(beyond_db(), $identity);
    echo json_encode(['usage' => jaguar_usage_public($usage)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    error_log('Jaguar monthly usage lookup failed: ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Jaguar usage is temporarily unavailable.']);
}
