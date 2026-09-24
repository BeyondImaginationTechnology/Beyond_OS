<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'POST a translation request.']);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
$input = trim((string)($payload['input'] ?? ''));
$from = trim((string)($payload['from'] ?? 'english'));
$to = trim((string)($payload['to'] ?? 'french'));
if ($input === '') {
    http_response_code(422);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'Enter a phrase first.']);
    exit;
}

$endpoint = trim((string)getenv('BEYOND1_LLAMA_JAGUAR_URL'));
if ($endpoint === '' || !function_exists('curl_init')) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','status'=>'not_configured','message'=>'Beyond-1 Llama Jaguar is ready to connect, but the model endpoint is not configured yet.','request'=>['input'=>$input,'from'=>$from,'to'=>$to]]);
    exit;
}

$request = json_encode(['assistant'=>'Beyond-1 Llama Jaguar','task'=>'translation','input'=>$input,'source_language'=>$from,'target_language'=>$to,'return_format'=>'translation_pronunciation_context']);
$headers = ['Content-Type: application/json'];
$token = trim((string)getenv('BEYOND1_LLAMA_JAGUAR_TOKEN'));
if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
$curl = curl_init($endpoint);
curl_setopt_array($curl, [CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$request,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30]);
$response = curl_exec($curl);
$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$error = curl_error($curl);
curl_close($curl);
if ($response === false || $error !== '') {
    http_response_code(502);
    echo json_encode(['ok'=>false,'assistant'=>'Beyond-1 Llama Jaguar','message'=>'Beyond-1 Llama Jaguar could not reach the model service.']);
    exit;
}
http_response_code($status >= 200 && $status < 300 ? 200 : $status);
echo json_encode(['ok'=>$status >= 200 && $status < 300,'assistant'=>'Beyond-1 Llama Jaguar','response'=>json_decode($response, true) ?? $response]);
